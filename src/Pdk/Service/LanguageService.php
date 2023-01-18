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
        return get_locale();
    }

    /**
     * @param  null|string $language
     *
     * @return string
     */
    protected function getFilePath(?string $language = null): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf('%s/config/pdk/translations/%s.json', $appInfo['path'], $this->getIso2($language));
    }
}
