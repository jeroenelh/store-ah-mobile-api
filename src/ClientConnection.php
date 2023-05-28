<?php

namespace Microit\StoreAhApi;

use Exception;
use Microit\StoreBase\HttpClient;
use Microit\StoreBase\Traits\Singleton;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientExceptionInterface;

class ClientConnection extends HttpClient
{
    use Singleton;

    protected AHApiToken $ahApiToken;

    public function __construct()
    {
        parent::__construct('https://api.ah.nl/');
        $this->ahApiToken = new AHApiToken();

        //        $request = $this->createRequest('get', 'mobile-services/product/detail/v4/fir/wi233440');
        //        $request = $this->createRequest('get', 'mobile-services/bonuspage/v1/metadata');
        //        $request = $this->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        //        $request = $this->createRequest('get', '/mobile-services/product/search/v2');
        //        $request = $this->createRequest('get', '/mobile-services/product/search/v2?page=0&query=bier&sortOn=RELEVANCE');
        //        $request = $request->withBody(Stream::create(json_encode(['category' => 'Diepvries'])));

        //        var_dump($this->getJsonResponse($request));
    }

    /**
     * @param string $method
     * @param string $uri
     * @return RequestInterface
     * @throws ClientExceptionInterface
     */
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
     * @throws Exception
     */
    public function getJsonResponse(RequestInterface $request): object|array
    {
        $response = $this->getResponse($request);

        $contents = $response->getBody()->getContents();

        $jsonResponse = json_decode($contents);
        if (is_null($jsonResponse)) {
            throw new Exception("Can't convert to json");
        }

        if (! (is_object($jsonResponse) || is_array($jsonResponse))) {
            throw new Exception('Bad json response');
        }

        return $jsonResponse;
    }
}
