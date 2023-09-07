<?php

/**
 * @see https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {

    // Factories

    override(\MyParcelNL\WooCommerce\Tests\wpFactory(), map(['' => '@_Factory']));
    override(\MyParcelNL\WooCommerce\Tests\Factory\WpFactoryFactory::create(), map(['' => '@_Factory']));
}
