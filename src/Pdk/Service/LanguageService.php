<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Language\Service\AbstractLanguageService;

class LanguageService extends AbstractLanguageService
{
    /**
     * @param  string|null $language
     *
     * @return string
     */
    public function getIso2(string $language = null): string
    {
        return substr($language ?? $this->getLanguage(), 0, 2);
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return str_replace('_', '-', get_locale());
    }

    /**
     * @param  null|string $language
     *
     * @return string
     */
    protected function getFilePath(?string $language = null): string
    {
        return sprintf('%s/config/pdk/translations/%s.json', Pdk::getAppInfo()->path, $this->getIso2($language));
    }
}
