<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\Account\Repository\AbstractAccountRepository;
use MyParcelNL\Sdk\src\Model\Account\Account;

class PdkAccountRepository extends AbstractAccountRepository
{
    public function store(Account $account = null): Account
    {
        return $this->save('account', $account);
    }
}
