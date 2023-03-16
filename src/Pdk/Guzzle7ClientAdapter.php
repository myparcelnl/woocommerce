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

        $response     = $this->client->request(strtolower($httpMethod), $uri, $requestOptions);
        $responseBody = $response->getBody();

        $body = $responseBody->isReadable()
            ? $responseBody->getContents()
            : null;

        return new ClientResponse($body, $response->getStatusCode() ?? 500, $response->getHeaders());
    }
}
