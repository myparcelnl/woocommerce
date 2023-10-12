<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Callback expects 1 parameter, \\$accepted_args is set to 3\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Hooks/AutomaticOrderExportHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Hooks/CartFeesHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Hooks/CheckoutScriptHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Callback expects 1 parameter, \\$accepted_args is set to 2\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Hooks/SeparateAddressFieldsHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Callback expects 1 parameter, \\$accepted_args is set to 2\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Hooks/TaxFieldsHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newCheckoutSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newDpdSettings is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newExportDefaultsSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newGeneralSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newPostnlSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration5_0_0\\:\\:\\$migrations \\(array\\<class\\-string\\<MyParcelNL\\\\Pdk\\\\App\\\\Installer\\\\Contract\\\\MigrationInterface\\>\\>\\) does not accept array\\<int, MyParcelNL\\\\WooCommerce\\\\Migration\\\\Pdk\\\\OrdersMigration\\|MyParcelNL\\\\WooCommerce\\\\Migration\\\\Pdk\\\\ProductSettingsMigration\\|MyParcelNL\\\\WooCommerce\\\\Migration\\\\Pdk\\\\SettingsMigration\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration5_0_0.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MyParcelNL\\\\Pdk\\\\Base\\\\Contract\\\\WeightServiceInterface\\:\\:convertToGrams\\(\\) invoked with 1 parameter, 2 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Pdk/SettingsMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Guzzle7ClientAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$deliveryOptionsData on left side of \\?\\? is never defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Hooks/PdkCheckoutPlaceOrderHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Property WooCommerce\\:\\:\\$cart \\(WC_Cart\\) in empty\\(\\) is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Hooks/PdkFrontendEndpointHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return with type void is incompatible with native type WP_REST_Response\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Hooks/PdkWebhookHooks.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\Pdk\\\\App\\\\Order\\\\Model\\\\PdkOrder\\:\\:\\$shipments \\(MyParcelNL\\\\Pdk\\\\Shipment\\\\Collection\\\\ShipmentCollection\\|null\\) does not accept MyParcelNL\\\\Pdk\\\\Base\\\\Support\\\\Collection\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Plugin/Repository/PdkOrderRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return with type mixed is not subtype of native type MyParcelNL\\\\Pdk\\\\App\\\\Cart\\\\Model\\\\PdkCart\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Plugin/Repository/WcCartRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property WooCommerce\\:\\:\\$shipping\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Plugin/WcShippingMethodRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Pdk\\\\Product\\\\Repository\\\\WcPdkProductRepository\\:\\:\\$weightService \\(MyParcelNL\\\\WooCommerce\\\\Pdk\\\\Service\\\\WcWeightService\\) does not accept MyParcelNL\\\\Pdk\\\\Base\\\\Contract\\\\WeightServiceInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Product/Repository/WcPdkProductRepository.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
