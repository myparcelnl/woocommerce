<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use MyParcelNL\Pdk\Facade\Pdk;

abstract class AbstractBlocksIntegration implements IntegrationInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param  string $blockName
     */
    public function __construct(string $blockName)
    {
        $appInfo = Pdk::getAppInfo();

        $this->name = "$appInfo->name-$blockName";
    }

    /**
     * @return string[]
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    final public function get_editor_script_handles(): array
    {
        return [sprintf('%s-block-view-script', $this->get_name())];
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    final public function get_name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    final public function get_script_data(): array
    {
        return $this->getScriptData();
    }

    /**
     * @return string[]
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    final public function get_script_handles(): array
    {
        return ["{$this->get_name()}-block-view-script", "{$this->get_name()}-block-editor-script"];
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
    }

    protected function getScriptData(): array
    {
        return [];
    }
}
