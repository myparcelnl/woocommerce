<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use GuzzleHttp\Client;
use MyParcelNL\Pdk\Api\Adapter\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Response\ClientResponseInterface;
use MyParcelNL\Pdk\Api\Response\ClientResponse;

/**
 *
 */
class Guzzle7ClientAdapter implements ClientAdapterInterface
{
    private const DEFAULT_OPTIONS = [
        'exceptions' => false,
    ];

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
     * @return \MyParcelNL\Pdk\Api\Response\ClientResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest(string $httpMethod, string $uri, array $options = []): ClientResponseInterface
    {
        $arr = $options;
        $merged = self::DEFAULT_OPTIONS + $options;

        $clientRequest = $this->client->createRequest(
            $httpMethod,
            $uri,
            self::DEFAULT_OPTIONS + $options
        );

        $response   = $this->client->send($clientRequest);
        $statusCode = $response ? $response->getStatusCode() : 500;

        $body = $response
            ? $response
                ->getBody()
                ->getContents()
            : null;

        return new ClientResponse($body, $statusCode);
    }
}
