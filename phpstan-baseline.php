<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Hooks/CartFeesHooks.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Hooks/CheckoutScriptHooks.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newCheckoutSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newDpdSettings is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newExportDefaultsSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newGeneralSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property MyParcelNL\\\\WooCommerce\\\\Migration\\\\Migration4_0_0\\:\\:\\$newPostnlSettings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Migration4_0_0.php',
];
$ignoreErrors[] = [
	// identifier: arguments.count
	'message' => '#^Method MyParcelNL\\\\Pdk\\\\Base\\\\Contract\\\\WeightServiceInterface\\:\\:convertToGrams\\(\\) invoked with 1 parameter, 2 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration/Pdk/SettingsMigration.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.variable
	'message' => '#^Variable \\$deliveryOptionsData on left side of \\?\\? is never defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Hooks/PdkCheckoutPlaceOrderHooks.php',
];
$ignoreErrors[] = [
	// identifier: empty.property
	'message' => '#^Property WooCommerce\\:\\:\\$cart \\(WC_Cart\\) in empty\\(\\) is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Hooks/PdkFrontendEndpointHooks.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property MyParcelNL\\\\Pdk\\\\App\\\\Order\\\\Model\\\\PdkOrder\\:\\:\\$shipments \\(MyParcelNL\\\\Pdk\\\\Shipment\\\\Collection\\\\ShipmentCollection\\|null\\) does not accept MyParcelNL\\\\Pdk\\\\Base\\\\Support\\\\Collection\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdk/Plugin/Repository/PdkOrderRepository.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
