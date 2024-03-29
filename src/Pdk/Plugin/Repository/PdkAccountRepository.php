<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\Account\Model\Account;
use MyParcelNL\Pdk\App\Account\Repository\AbstractPdkAccountRepository;
use MyParcelNL\Pdk\Facade\Pdk;

class PdkAccountRepository extends AbstractPdkAccountRepository
{
    /**
     * @return null|\MyParcelNL\Pdk\Account\Model\Account
     */
    public function getFromStorage(): ?Account
    {
        $account = get_option($this->getSettingKey(), null);

        return $account ? new Account($account) : null;
    }

    /**
     * @param  null|\MyParcelNL\Pdk\Account\Model\Account $account
     *
     * @return \MyParcelNL\Pdk\Account\Model\Account
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function store(?Account $account): ?Account
    {
        $settingKey = $this->getSettingKey();

        $this->save('account', $account);

        if (! $account) {
            delete_option($settingKey);

            return $account;
        }

        update_option($settingKey, $account->toStorableArray());

        return $account;
    }

    /**
     * @return string
     */
    private function getSettingKey(): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf('_%s_data_account', $appInfo->name);
    }
}
