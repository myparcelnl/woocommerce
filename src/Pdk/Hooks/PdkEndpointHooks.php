<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Endpoint\EndpointRegistry;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Exposes PDK endpoint handlers via the WordPress REST API.
 *
 * Provides direct access to PDK endpoints (e.g., GetDeliveryOptionsEndpoint) for external
 * applications through standard WordPress REST API routes. Supports API versioning via
 * headers and uses WordPress's built-in authentication mechanisms (nonce, JWT, Basic Auth).
 */
final class PdkEndpointHooks implements WordPressHooksInterface
{
    use UsesPdkRequestConverter;

    /**
     * @return void
     */
    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerEndpointRoutes']);
        add_filter('woocommerce_rest_is_request_to_rest_api', [$this, 'isRequestToWooCommerceRestApi'], 10, 1);
    }

    /**
     * Handles delivery options requests by converting between WordPress and PDK formats.
     *
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function processDeliveryOptionsRequest(WP_REST_Request $request): WP_REST_Response
    {
        // Convert WordPress request to Symfony request
        $convertedRequest = $this->convertRequest($request);

        $handler = Pdk::get(EndpointRegistry::DELIVERY_OPTIONS);
        /**
         * @var \Symfony\Component\HttpFoundation\JsonResponse $response
         */
        $response = $handler->handle($convertedRequest);

        // Convert Symfony response to WordPress response
        return new WP_REST_Response(
            \json_decode($response->getContent()),
            $response->getStatusCode(),
            // Include relevant headers for versioning and response format as per ADR-0011
            [
                'Content-Type' => $response->headers->get('Content-Type'),
                'Accept'       => $response->headers->get('Accept'),
            ]
        );
    }

    /**
     * Registers all PDK endpoint routes with WordPress REST API.
     *
     * Routes are registered under the plugin namespace defined by PdkBootstrapper::PLUGIN_NAMESPACE
     * (e.g., /wp-json/myparcelcom/delivery-options). Uses WordPress default authentication - no custom
     * permission callback needed.
     *
     * @return void
     */
    public function registerEndpointRoutes(): void
    {
        // Register delivery options endpoint
        register_rest_route(
            PdkBootstrapper::PLUGIN_NAMESPACE,
            'delivery-options',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'processDeliveryOptionsRequest'],
                'permission_callback' => [$this, 'checkDeliveryOptionsPermission'],
            ]
        );
    }

    public function checkDeliveryOptionsPermission()
    {
        // Check if the user has permission to view orders (required for delivery options endpoint)
        if (! \current_user_can('read_private_shop_orders')) {
            return new \WP_Error(
                'woocommerce_rest_cannot_view',
                __('Sorry, you cannot view orders.', PdkBootstrapper::PLUGIN_NAMESPACE),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Callback for the `woocommerce_rest_is_request_to_rest_api` filter.
     *
     * Ensures that our custom PDK endpoints are treated as WooCommerce REST API requests and include WooCommerce authentication.
     *
     * @param mixed $is_rest_api_request Whether the current request is a WooCommerce REST API request.
     * @return bool
     */
    public function isRequestToWooCommerceRestApi($is_rest_api_request): bool
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return $is_rest_api_request;
        }

        $rest_prefix = trailingslashit(rest_get_url_prefix());

        if (false !== strpos($_SERVER['REQUEST_URI'], $rest_prefix . PdkBootstrapper::PLUGIN_NAMESPACE)) {
            return true;
        }

        return $is_rest_api_request;
    }
}
