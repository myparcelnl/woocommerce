<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;

/**
 * @see /config/pdk.php
 */
class WordPressService implements WordPressServiceInterface
{
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return Pdk::get('wordPressVersion');
    }

    /**
     * Renders a set of rows as a table.
     *
     * @param  string[][] $rows
     *
     * @return void
     */
    public function renderTable(array $rows): void
    {
        printf(
            "<table>%s</table>",
            array_reduce($rows, static function (string $carry, array $row): string {
                return $carry . sprintf('<tr><th>%s</th><td>%s</td></tr>', $row[0], $row[1]);
            }, '')
        );
    }
}
