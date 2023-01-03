<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Language\Service\AbstractLanguageService;

class LanguageService extends AbstractLanguageService
{
    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return get_locale();
    }

    /**
     * @param  null|string $language
     *
     * @return string
     */
    protected function getFilePath(?string $language = null): string
    {
        $lang = $language ?? $this->getLanguage();
        $iso2 = substr($lang, 0, 2);

        return sprintf('%s/config/pdk/translations/%s.json', Pdk::get('pluginPath'), $iso2);
    }
}
