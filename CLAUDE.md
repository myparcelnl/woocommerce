# MyParcel WooCommerce Plugin

## Repository Ecosystem

This plugin is part of a multi-repo ecosystem. Understanding the relationships is critical.

### Repositories

All repositories below are expected to be present on the developer's system. Paths vary per setup — check `composer.json` (path repos) and `package.json` (portal links) for the local linked paths, or ask the developer. If either PDK (PHP or JS) is not linked locally, prompt the developer to run `pdk-dev-on` first.

| Repo | Purpose |
|------|---------|
| **myparcelnl/pdk** | Shared PHP framework (models, services, migrations, carrier logic). Linked as path dependency in `composer.json` during development. |
| **myparcelnl/sdk** | Generated API client (OpenAPI), carrier constants, API types. Installed via composer (`vendor/myparcelnl/sdk`). |
| **js-pdk** | Shared JS/Vue framework for admin + checkout frontend. Linked via `portal:` in `package.json`. |
| **myparcelnl-woocommerce** (this repo) | WooCommerce plugin — thin adapter layer on top of PDK. |

The PDK is the source of truth for models, carrier definitions, delivery options, settings, and business logic. The plugins implement platform-specific adapters (storage, hooks, rendering, cron).

### Carrier Identifier Formats

The codebase has two carrier name formats in transition:

- **Legacy (lowercase)**: `"postnl"`, `"dhlforyou"`, `"dhlparcelconnect"` — used by the delivery options API endpoint, JS/Vue checkout app, and `_myparcel_delivery_options` meta
- **New V2 (SCREAMING_SNAKE_CASE)**: `"POSTNL"`, `"DHL_FOR_YOU"`, `"DHL_PARCEL_CONNECT"` — from `RefCapabilitiesSharedCarrierV2` constants in the SDK

The PDK `DeliveryOptions` constructor normalizes legacy names on input. The `Carrier::CARRIER_NAME_TO_LEGACY_MAP` constant maps new-to-legacy; `array_flip()` gives the reverse.

Old carrier data may also contain a `:contractId` suffix (e.g. `"postnl:1"`) from the legacy `Carrier::getExternalIdentifierAttribute()`.

## Plugin Architecture

### Data Storage

WooCommerce order meta (via `WC_Order::get_meta()` / `update_meta_data()`), works with both HPOS and legacy post meta:

| Meta Key | Content | Written by |
|----------|---------|------------|
| `_myparcelcom_order_data` | PDK order data (deliveryOptions, exported, apiIdentifier) | `PdkOrderRepository::update()` |
| `_myparcelcom_order_shipments` | Serialized `ShipmentCollection` array | `PdkOrderRepository::update()` |
| `_myparcel_delivery_options` | Legacy-format delivery options for external system compat | `LegacyDeliveryOptionsAdapter` |
| `_myparcelcom_order_notes` | PDK order notes | `WcOrderNoteRepository` |
| `_myparcelcom_version` | Plugin version the resource was last saved with | `PdkOrderRepository` |
| `_myparcelcom_migrated` | Array of migration versions applied to this resource | `AbstractMigration::getMigrationMeta()` |

The `_myparcelcom` prefix comes from `PdkBootstrapper::PLUGIN_NAMESPACE` = `"myparcelcom"`.

### Settings Storage

Plugin settings are stored in `wp_options` with key prefix `_myparcelcom_` (from `settingKeyPrefix` in bootstrapper). Carrier settings are stored under `_myparcelcom_carrier` with carrier name as sub-key (e.g. `POSTNL`, `DHL_FOR_YOU`).

### Migration System

Migrations are registered in `WcMigrationService` and run by the PDK `InstallerService`. A migration runs when its `getVersion()` is greater than the stored `settingKeyInstalledVersion`.

For long-running migrations, use **chunked cron jobs**:
1. `up()` queries affected records and splits into chunks
2. Each chunk is scheduled via `CronServiceInterface::schedule()` with a unique action name
3. The action name is registered in `WcPdkBootstrapper` (e.g. `migrateAction_6_1_0_Orders`)
4. The callback is hooked in `ScheduledMigrationHooks` via `add_action()`

### Key Files

- `src/Pdk/WcPdkBootstrapper.php` — meta keys, cron actions, settings keys
- `src/Pdk/Plugin/Repository/PdkOrderRepository.php` — order CRUD, reads/writes all order meta
- `src/Hooks/ScheduledMigrationHooks.php` — registers cron callbacks for async migrations
- `src/Pdk/Plugin/Installer/WcMigrationService.php` — migration class registry
- `src/Adapter/LegacyDeliveryOptionsAdapter.php` — writes `_myparcel_delivery_options` in legacy format
- `src/Hooks/CheckoutScriptHooks.php` — checkout rendering, delivery options widget
- `src/Pdk/Service/WcFrontendRenderService.php` — renders delivery options wrapper div with context

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

- The plugin runs inside a Docker-based WordPress setup. Use `docker compose exec php wp ...` from the docker-wordpress project root for WP-CLI commands.
- PDK (PHP) is linked locally as a path dependency in `composer.json` — check the `repositories` section for the local path.
- JS-PDK apps are linked via `portal:` in `package.json` — check `devDependencies` for the local paths.

### Building JS (nx cache gotcha)

Editing the shared `views/frontend/common` package does **not** invalidate nx's build cache for
dependent bundles (e.g. `frontend-checkout-core`), so a plain `yarn build` can silently replay a
stale bundle and your change won't reach the browser. Build with `--skip-nx-cache` (or run
`npx nx reset` first). Verify a change landed with `grep` on the `dist/*.iife.js` / `dist/*.js`
(the dev build keeps variable names; the production build minifies).

### Running Tests

Always run PHP tests via Docker to use the correct PHP version:

```bash
cd ~/projects/docker-wordpress && PHP_VERSION=7.4 docker compose run --rm php bash -c 'cd /var/www/html/wp-content/plugins/woocommerce-myparcel && ./vendor/bin/pest <args>'
```

Before running tests, install composer dependencies inside the container:

```bash
cd ~/projects/docker-wordpress && docker compose run --rm php bash -c "cd /var/www/html/wp-content/plugins/woocommerce-myparcel && composer install --ignore-platform-req=ext-gd"
```

Never run `./vendor/bin/pest` or `composer install` directly on the host — the host PHP version may differ from the Docker container.
