<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockWc;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcClass;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcDateTime;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcProduct;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcSession;

/** @see \MyParcelNL\WooCommerce\bootPdk() */
const PEST = true;

class WC_Data { }

/** @see \WC_Cart */
class WC_Cart extends MockWcCart { }

/** @see \WC_Customer */
class WC_Customer extends MockWcCustomer { }

/** @see \WC_Order */
class WC_Order extends MockWcOrder { }

/** @see \WC_Order_Item */
class WC_Order_Item extends MockWcClass { }

/** @see \WC_Order_Item_Product */
class WC_Order_Item_Product extends WC_Order_Item { }

/** @see \WC_Product */
class WC_Product extends MockWcProduct { }

/** @see \WC_DateTime */
class WC_DateTime extends MockWcDateTime { }

/** @see \WC_Meta_Data */
class WC_Meta_Data extends MockWcClass { }

/** @see \WC */
class WC extends MockWc { }

/** @see \WC_Session */
class WC_Session extends MockWcSession { }
