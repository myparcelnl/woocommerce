<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\App\Api\Frontend\AbstractFrontendEndpointService;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;

class WcFrontendEndpointService extends AbstractFrontendEndpointService
{
    /**
     * Add a nonce to the request to authenticate the user.
     */
    public function __construct()
    {
        $this->headers['X-WP-Nonce'] = wp_create_nonce('wp_rest');
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(null, sprintf('%s/%s', Pdk::get('routeFrontend'), Pdk::get('routeFrontendMyParcel')));
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function getEndpoints(): Collection
    {
        return parent::getEndpoints()
            ->merge([
                /**
                 * Get checkout context
                 */
                //                PdkFrontendActions::FETCH_CHECKOUT_CONTEXT => [
                //                    'request' => FetchContextEndpointRequest::class,
                //                    'action'  => FetchCheckoutContextAction::class,
                //                ],
            ]);
    }
}
