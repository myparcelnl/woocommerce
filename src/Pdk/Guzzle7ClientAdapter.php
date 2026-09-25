<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MyParcelNL\Pdk\Api\Contract\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Contract\ClientResponseInterface;
use MyParcelNL\Pdk\Api\Response\ClientResponse;

class Guzzle7ClientAdapter implements ClientAdapterInterface
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @param  \GuzzleHttp\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param  string $httpMethod
     * @param  string $uri
     * @param  array  $options
     *
     * @return \MyParcelNL\Pdk\Api\Contract\ClientResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest(string $httpMethod, string $uri, array $options = []): ClientResponseInterface
    {
        $requestOptions = array_filter([
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS     => $options['headers'] ?? null,
            RequestOptions::BODY        => $options['body'] ?? null,
        ], static function ($value) {
            return $value !== null;
        });

        // Guzzle 7.11 deprecated non-uppercase HTTP methods. Passing a lowercase method makes it
        // call trigger_deprecation(), which is fatal when another plugin claimed the composer
        // file hash of symfony/deprecation-contracts before us.
        $response     = $this->client->request(strtoupper($httpMethod), $uri, $requestOptions);
        $responseBody = $response->getBody();

        $body = $responseBody->isReadable()
            ? $responseBody->getContents()
            : null;

        return new ClientResponse($body, $response->getStatusCode(), $response->getHeaders());
    }
}
