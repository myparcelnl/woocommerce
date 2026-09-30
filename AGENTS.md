# MyParcel WooCommerce Plugin

The WooCommerce plugin for MyParcel. It is a thin adapter layer on top of the PDK: it implements the WooCommerce-specific storage, hooks, rendering and cron.

## MyParcel stack

This repository is one part of the MyParcel plugin stack:

| Repository | Role |
| --- | --- |
| [myparcelnl/sdk](https://github.com/myparcelnl/sdk) | PHP client generated from the MyParcel API OpenAPI spec. Source of carriers, delivery types, package types and API types. |
| [myparcelnl/pdk](https://github.com/myparcelnl/pdk) | PHP Plugin Development Kit. Business logic, models, settings, migrations and API calls shared by all plugins. |
| [myparcelnl/js-pdk](https://github.com/myparcelnl/js-pdk) | JS Plugin Development Kit. Admin UI and checkout scripts that the plugins build on. |
| [myparcelnl/delivery-options](https://github.com/myparcelnl/delivery-options) | Checkout widget in which the consumer picks a delivery or pickup option. |
| [myparcelnl/woocommerce](https://github.com/myparcelnl/woocommerce) **(this repository)** | WooCommerce plugin. Thin adapter on top of the PDK. |
| [myparcelnl/prestashop](https://github.com/myparcelnl/prestashop) | PrestaShop module. Thin adapter on top of the PDK. |

How they connect:

- A plugin bootstraps the PDK and implements the platform adapters (storage, hooks, rendering, cron). Behaviour that all plugins share goes in the PDK, not in one plugin.
- The PDK renders its context as JSON in an HTML attribute (`data-pdk-context` for the admin). The js-pdk apps read it and call PDK endpoint actions (registered in the PDK's `config/actions.php`) for more data.
- The delivery options widget is not bundled into the plugins. The PDK builds jsdelivr CDN URLs for `myparcel.js`, `myparcel.lib.js` and `style.css` (the PDK's `config/pdk-dependencies.php`), and the plugin loads them at runtime. In js-pdk, the `@myparcel-dev/delivery-options` npm dependency supplies only types and constants.
- The widget and the js-pdk admin get carrier capabilities by POSTing to the PDK `proxyCapabilities` action (`PdkCapabilitiesActions`).
- Carriers, delivery types and package types come from the SDK. Do not add definitions of them to the PDK or the plugins.

For local development, `pdk-dev-on` links local checkouts of the PDK and js-pdk into a plugin. It adds a composer `path` repository to `composer.json` and yarn `portal:` links to `package.json`. Never commit these changes. Suggest it only when the task needs changes in the PDK or js-pdk, or needs to test such changes in a plugin. MyParcel employees can find the setup in [Local PDK in PDK plugin](https://myparcelnl.atlassian.net/wiki/spaces/IG/pages/319422473).

The delivery options widget has no link command. To test a local build, serve `apps/delivery-options/dist` from a delivery-options checkout and override `deliveryOptionsCdnUrlJs`, `deliveryOptionsCdnUrlJsLib` and `deliveryOptionsCdnUrlCss` in the plugin's `config/pdk.php`. Never commit these overrides. This works only locally: there is no way to give a tester a plugin build with a custom widget version.

To find the local checkouts of the other repositories, read `.claude/int-branch.json` in a parent directory. Its `repos` list holds their paths. If there is none and the task needs another repository, tell the user once that the `int-branch` skill of the `external-integrations-basekit` plugin offers to write this file on its first run.

## MyParcel employees: agent plugin

If no skills from the `external-integrations-basekit` plugin are available in this session, tell the user once, at the start, that the plugin is missing and give these commands:

```
/plugin marketplace add mypadev/ai-plugins
/plugin install external-integrations-basekit@myparcel-claude-code-marketplace
```

The marketplace is private, so only MyParcel employees can install it.

## Plugin Architecture

### Data Storage

WooCommerce order meta (via `WC_Order::get_meta()` / `update_meta_data()`), works with both HPOS and legacy post meta:

| Meta Key | Content |
|----------|---------|
| `_myparcelcom_order_data` | PDK order data (deliveryOptions, exported, apiIdentifier) |
| `_myparcelcom_order_shipments` | Serialized `ShipmentCollection` array |
| `_myparcel_delivery_options` | Legacy-format delivery options, kept for external systems that still read it |
| `_myparcelcom_order_notes` | PDK order notes |
| `_myparcelcom_version` | Plugin version the resource was last saved with |
| `_myparcelcom_migrated` | Versions of the versioned migrations that already processed this order or product. Separate from the site-wide `applied_migrations` setting |

### Settings Storage

Plugin settings are stored in `wp_options` with key prefix `_myparcelcom_` (from `settingKeyPrefix` in bootstrapper). Carrier settings are stored under `_myparcelcom_carrier` with carrier name as sub-key (e.g. `POSTNL`, `DHL_FOR_YOU`).

### Migration System

Migrations are registered in `WcMigrationService` (class migrations) or auto-discovered as timestamped files in `src/Migration/`, and run by the PDK `InstallerService`. A migration runs once: its identity (the class name, or the file name for a timestamped migration) is recorded in the `applied_migrations` setting, independent of the plugin version.

For long-running migrations, use **chunked cron jobs**:
1. `up()` queries affected records and splits into chunks
2. Each chunk is scheduled via `CronServiceInterface::schedule()` with a unique action name
3. The action name is registered in `WcPdkBootstrapper`, next to the existing migration actions
4. The callback is hooked in `ScheduledMigrationHooks` via `add_action()`

### Checkout Flow

1. `CheckoutScriptHooks::loadDeliveryOptionsScripts()` checks `shouldShowDeliveryOptions()` (requires non-virtual products + setting enabled)
2. `WcFrontendRenderService::renderDeliveryOptions()` creates a context bag and renders `<div id="mypa-delivery-options-wrapper" data-context="...">`
3. JS (`checkout-core.iife.js`) finds the wrapper, parses context, initializes the delivery options widget. `views/frontend/checkout-delivery-options/src/utils/init.ts` wires the capabilities-proxy URL the V7 widget needs to resolve carriers/options at runtime
4. User selections are posted as `myparcelcom_checkout_data` (JSON with legacy carrier names)
5. `CartFeesHooks` processes this on AJAX cart updates; `PdkCheckoutPlaceOrderHooks` saves on order placement
6. Both create `new DeliveryOptions(json_decode(...))` — the constructor normalizes legacy carrier names

### Blocks checkout: delivery-option fee gotchas (hard-won — don't relearn)

The live delivery-option fee in the **blocks** checkout uses `extensionCartUpdate`
(`views/blocks/delivery-options/src/frontend.tsx`). Its response applies the **entire** server cart
back into `wc/store/cart` (`receiveCart`), so a badly-timed push corrupts other cart state. There is
**no** WC Blocks alternative that puts a fee in the grand total (checkout filters and totals
slot/fills are display-only). These invariants must hold — each fixes a real bug we hit:

- **Never push at order placement.** Forcing `extensionCartUpdate` there reverts the chosen shipping
  method on the resulting order. The order POST already carries the selection in its `extensions`
  payload; the server primes the fee from it in `CartFeesHooks::stashBlocksCheckoutSelection` (hooked
  on `woocommerce_store_api_checkout_update_customer_from_request`, which runs *before*
  `OrderController::update_order_from_cart` recalculates fees). Do **not** read `php://input` in the
  fee calc (`woocommerce_cart_calculate_fees`) — it corrupts the order build (duplicate lines, 500s).
- **The push must block the place-order flow while in flight.** An `extensionCartUpdate` overlapping
  the order POST duplicates the order's line items/fees, and an in-flight request can't be cancelled.
  Wrap the push in `dispatch(CHECKOUT_STORE_KEY).__internalIncrementCalculating()` /
  `__internalDecrementCalculating()` (the `isCalculating` the place-order flow already waits on).
- **Dedupe widget re-emits on a fee-relevant key** (ignore the volatile `date`) held **module-level**
  so it survives the remount Blocks does on every shipping/pickup toggle; otherwise the toggle
  re-emit fires a spurious push that races the shipping commit.

**Known WooCommerce core bug (not ours):** rapidly toggling "Afhalen in de winkel" ↔ "Verzenden" then
ordering can place the order on the *previous* method (WC fails to commit the final local-pickup
toggle server-side). Reproduces with all our checkout JS disabled. Normal single toggles are fine;
treat rapid back-and-forth as an upstream stress-case.

## Development Environment

The supported WordPress range is declared once, in `readme.txt` (`Requires at least`). Before treating a WordPress version guard as dead code, check the `@since` lines in core against that minimum.

- The plugin runs inside a Docker-based WordPress setup. Use `docker compose exec php wp ...` from the docker-wordpress project root for WP-CLI commands.

### Building JS (nx cache gotcha)

Editing the shared `views/frontend/common` package does **not** invalidate nx's build cache for
dependent bundles (e.g. `frontend-checkout-core`), so a plain `yarn build` can silently replay a
stale bundle and your change won't reach the browser. Build with `--skip-nx-cache` (or run
`npx nx reset` first). Verify a change landed with `grep` on the `dist/*.iife.js` / `dist/*.js`
(the dev build keeps variable names; the production build minifies).

### Running Tests

Run the PHP tests with the plugin's own Docker services, from the plugin root:

```bash
docker compose run --rm php    # composer install
docker compose run --rm test   # pest
```

Never run `./vendor/bin/pest` or `composer install` directly on the host — the host PHP version may differ from the Docker container.
