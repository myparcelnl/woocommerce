<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use GuzzleHttp\Client;
use MyParcelNL\Pdk\Api\Contract\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Contract\ClientResponseInterface;
use MyParcelNL\Pdk\Api\Response\ClientResponse;

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
     * @return \MyParcelNL\Pdk\Api\Contract\ClientResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest(string $httpMethod, string $uri, array $options = []): ClientResponseInterface
    {
        $response = $this->client->request(
            $httpMethod,
            $uri,
            self::DEFAULT_OPTIONS + $options
        );

        $statusCode = $response->getStatusCode() ?? 500;

        $body = $response
            ->getBody()
            ->getContents();

        return new ClientResponse($body, $statusCode);
    }
}
