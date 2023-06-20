<?php

namespace Microit\StoreAhMobileApi;

use Microit\StoreBase\Exceptions\InvalidResponseException;
use Microit\StoreBase\Traits\Singleton;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

class HttpClient extends \Microit\StoreBase\HttpClient
{
    use Singleton;

    protected AHApiToken $ahApiToken;

    public function __construct()
    {
        parent::__construct('https://api.ah.nl/');
        $this->ahApiToken = new AHApiToken();
    }

    public function createRequest(string $method, string $uri): RequestInterface
    {
        $request = parent::createRequest($method, $uri);
        $request = $request->withAddedHeader('content-type', 'application/json');
        $request = $request->withAddedHeader('user-agent', 'Appie/8.8.2 Model/phone Android/7.0-API24');
        $request = $request->withAddedHeader('client-name', 'appie-android');
        $request = $request->withAddedHeader('client-version', '8.12');
        $request = $request->withAddedHeader('x-application', 'AHWEBSHOP');

        return $request->withAddedHeader('authorization', 'Bearer '.$this->ahApiToken->getAccessToken());
    }

    /**
     * @param RequestInterface $request
     * @return object|array<array-key, mixed>
     * @throws ClientExceptionInterface
     * @throws InvalidResponseException
     */
    public function getJsonResponse(RequestInterface $request): object|array
    {
        $response = $this->getResponse($request);

        $contents = $response->getBody()->getContents();

        $jsonResponse = json_decode($contents);
        if (! (is_object($jsonResponse) || is_array($jsonResponse))) {
            throw new InvalidResponseException("Can't convert to json");
        }

        return $jsonResponse;
    }
}
