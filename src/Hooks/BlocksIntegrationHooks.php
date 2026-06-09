<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class BlocksIntegrationHooks implements WordPressHooksInterface
{
    /**
     * Avoid loading blocks multiple times. There's no concise way to get the block name from a metadata file without
     * copying a bunch of WordPress code.
     */
    private $loadedBlocks = false;

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->loadBlocks();
    }

    /**
     * @return void
     */
    private function loadBlocks(): void
    {
        if ($this->loadedBlocks) {
            return;
        }

        $appInfo = Pdk::getAppInfo();
        $blocks  = glob($appInfo->path . 'views/blocks/*', GLOB_ONLYDIR);

        foreach ($blocks as $block) {
            register_block_type($block);
        }

        $this->loadedBlocks = true;
    }
}
