<?php

namespace Microit\StoreAhApi;

use Microit\StoreBase\Traits\Singleton;
use Nyholm\Psr7\Stream;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class ClientConnection extends \Microit\StoreBase\HttpClient
{
    use Singleton;

    protected AHApiToken $AHApiToken;
    public function __construct()
    {
        parent::__construct('https://api.ah.nl/');
        $this->AHApiToken = new AHApiToken();

        //        $request = $this->createRequest('get', 'mobile-services/product/detail/v4/fir/wi233440');
        //        $request = $this->createRequest('get', 'mobile-services/bonuspage/v1/metadata');
        //        $request = $this->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        //        $request = $this->createRequest('get', '/mobile-services/product/search/v2');
        //        $request = $this->createRequest('get', '/mobile-services/product/search/v2?page=0&query=bier&sortOn=RELEVANCE');
        //        $request = $request->withBody(Stream::create(json_encode(['category' => 'Diepvries'])));

        //        var_dump($this->getJsonResponse($request));
    }

    public function createRequest(string $method, string $uri): RequestInterface
    {
        $request = parent::createRequest($method, $uri);
        $request = $request->withAddedHeader('content-type', 'application/json');
        $request = $request->withAddedHeader('user-agent', 'Appie/8.8.2 Model/phone Android/7.0-API24');
        $request = $request->withAddedHeader('client-name', 'appie-android');
        $request = $request->withAddedHeader('client-version', '8.12');
        $request = $request->withAddedHeader('x-application', 'AHWEBSHOP');
        $request = $request->withAddedHeader('authorization', 'Bearer '.$this->AHApiToken->getAccessToken());
        return $request;
    }

    /**
     * @param RequestInterface $request
     * @return object|array<array-key, mixed>
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getJsonResponse(RequestInterface $request): object|array
    {
        $response = $this->getResponse($request);

//        var_dump($response->getStatusCode());
        $contents = $response->getBody()->getContents();
//        var_dump($contents);

        $jsonResponse = json_decode($contents);
        if (is_null($jsonResponse)) {
            throw new \Exception("Can't convert to json");
        }

        if (!(is_object($jsonResponse) || is_array($jsonResponse))) {
            throw new \Exception("Bad json response");
        }

        return $jsonResponse;
    }
}
