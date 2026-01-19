<?php

declare(strict_types=1);

use MyParcelNL\Sdk\Support\Str;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWpFactory;

/**
 * @template T of \WP_Term
 * @method $this withId(int $id)
 * @method $this withSlug(string $slug)
 * @method $this withTermGroup(int $termGroup)
 * @method $this withTermTaxonomyId(int $termTaxonomyId)
 * @method $this withTaxonomy(string $taxonomy)
 * @method $this withDescription(string $description)
 * @method $this withParent(int $parent)
 * @method $this withCount(int $count)
 * @method $this withFilter(string $filter)
 */
class WP_Term_Factory extends AbstractWpFactory
{
    public function getClass(): string
    {
        return WP_Term::class;
    }

    /**
     * @return T
     */
    public function store()
    {
        $instance = parent::store();

        wp_cache_add((string) $instance->get_id(), $instance, 'terms');

        return $instance;
    }

    /**
     * @param  string $name
     *
     * @return $this
     */
    public function withName(string $name): self
    {
        return $this
            ->with(['name' => $name])
            ->withSlug(Str::kebab($name));
    }
}
