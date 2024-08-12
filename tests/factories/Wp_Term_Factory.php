<?php

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WP_Term
 * @method WP_Term make()
 * @method $this withId(int $id)
 * @method $this withTermId(int $id)
 * @method $this withName(string $name)
 * @method $this withSlug(string $slug)
 * @method $this withTermGroup(string $termGroup)
 * @method $this withTermTaxonomyId(int $termTaxonomyId)
 * @method $this withTaxonomy(int $taxonomy)
 * @method $this withDescription(string $description)
 * @method $this withParent(int $parentId)
 * @method $this withCount(int $count)
 * @method $this withFilter(string $filter)
 */
final class Wp_Term_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WP_Term::class;
    }
}