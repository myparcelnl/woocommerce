<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\Account\Model\Account;
use MyParcelNL\Pdk\Account\Repository\AbstractAccountRepository;
use MyParcelNL\Pdk\Facade\Pdk;

class PdkAccountRepository extends AbstractAccountRepository
{
    /**
     * @return null|\MyParcelNL\Pdk\Account\Model\Account
     */
    public function getFromStorage(): ?Account
    {
        $account = get_option($this->getSettingKey(), null);

        return new Account($account);
    }

    /**
     * @param  null|\MyParcelNL\Pdk\Account\Model\Account $account
     *
     * @return \MyParcelNL\Pdk\Account\Model\Account
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function store(?Account $account): Account
    {
        $settingKey = $this->getSettingKey();

        if (! $account) {
            delete_option($settingKey);

            return $account;
        }

        update_option($settingKey, $account->toStorableArray());

        return $this->save('account', $account);
    }

    /**
     * @return string
     */
    private function getSettingKey(): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf('%s_account', $appInfo['name']);
    }
}
