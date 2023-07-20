<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Storage;

use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\Service\WordPressService;

final class WpMetaStorage implements StorageInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WordPressService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\WordPressService $service
     */
    public function __construct(WordPressService $service)
    {
        $this->service = $service;
    }

    /**
     * @param  string $storageKey
     *
     * @return void
     */
    public function delete(string $storageKey): void
    {
        [$postId, $metaKey] = $this->parseStorageKey($storageKey);

        delete_post_meta($postId, $metaKey);
    }

    /**
     * @param  \WC_Data|\WP_Post|int|string $input
     * @param  string                       $metaKey
     *
     * @return void
     */
    public function deleteByPost($input, string $metaKey): void
    {
        $this->delete($this->createStorageKey($input, $metaKey));
    }

    /**
     * @param  string $storageKey
     *
     * @return mixed
     */
    public function get(string $storageKey)
    {
        [$postId, $metaKey] = $this->parseStorageKey($storageKey);

        return get_post_meta($postId, $metaKey, true);
    }

    /**
     * @param         $input
     * @param  string $metaKey
     *
     * @return mixed
     */
    public function getForPost($input, string $metaKey)
    {
        return $this->get($this->createStorageKey($input, $metaKey));
    }

    /**
     * @param  string $storageKey
     *
     * @return bool
     */
    public function has(string $storageKey): bool
    {
        return $this->get($storageKey) !== null;
    }

    /**
     * @param  \WC_Data|\WP_Post|int|string $input
     * @param  string                       $metaKey
     *
     * @return bool
     */
    public function postHas($input, string $metaKey)
    {
        return $this->has($this->createStorageKey($input, $metaKey));
    }

    /**
     * @param  string $storageKey
     * @param         $value
     *
     * @return void
     */
    public function set(string $storageKey, $value): void
    {
        [$postId, $metaKey] = $this->parseStorageKey($storageKey);

        update_post_meta($postId, $metaKey, $value);
    }

    /**
     * @param  \WC_Data|\WP_Post|int|string $input
     * @param  string                       $metaKey
     * @param  mixed                        $value
     *
     * @return void
     */
    public function updateByPost($input, string $metaKey, $value): void
    {
        $this->set($this->createStorageKey($input, $metaKey), $value);
    }

    /**
     * @param         $input
     * @param  string $metaKey
     *
     * @return string
     */
    protected function createStorageKey($input, string $metaKey): string
    {
        return sprintf('%s:%s', $this->service->getPostId($input), $metaKey);
    }

    /**
     * @param  string $storageKey
     *
     * @return string[]
     */
    protected function parseStorageKey(string $storageKey): array
    {
        return explode(':', $storageKey) ?: [];
    }
}
