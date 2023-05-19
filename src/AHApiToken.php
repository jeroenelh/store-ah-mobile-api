<?php

namespace Microit\StoreAhApi;

use Microit\StoreBase\HttpClient;
use Nyholm\Psr7\Stream;

class AHApiToken
{
    protected HttpClient $httpClient;

    protected string $accessToken = '';

    protected string $refreshToken = '';

    protected \DateTimeImmutable $expireDate;

    public function __construct()
    {
        $this->httpClient = new HttpClient('https://api.ah.nl/mobile-auth/v1/auth/token');
        $this->expireDate = new \DateTimeImmutable();
        $this->renewToken();
    }

    /**
     * @return void
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Exception
     */
    public function renewToken(): void
    {
        if (!empty($this->refreshToken)) {
            $request = $this->httpClient->createRequest('post', 'refresh');
        } else {
            $request = $this->httpClient->createRequest('post', 'anonymous');
        }

        $request = $request->withBody(Stream::create(json_encode([
            'clientId' => 'appie',
            'refreshToken' => $this->refreshToken ?: null,
        ])));

        $request = $request->withAddedHeader('content-type', 'application/json');
        $response = $this->httpClient->getResponse($request);

        $jsonResponse = json_decode($response->getBody()->getContents());
        if (is_null($jsonResponse)) {
            throw new \Exception('Bad response');
        }

        if (!is_object($jsonResponse)) {
            throw new \Exception("Can't convert json to object");
        }

        if (!isset($jsonResponse->access_token) || !isset($jsonResponse->refresh_token) || !isset($jsonResponse->expires_in)) {
            throw new \Exception('Bad response');
        }

        $this->accessToken = (string) $jsonResponse->access_token;
        $this->refreshToken = (string) $jsonResponse->refresh_token;
        $interval = \DateInterval::createFromDateString((int) $jsonResponse->expires_in.' seconds');
        $this->expireDate = (new \DateTimeImmutable())->add($interval);
    }

    public function isValid(): bool
    {
        return $this->expireDate->getTimestamp() > time();
    }

    public function getAccessToken(): string
    {
        if (!$this->isValid()) {
            $this->renewToken();
        }

        return $this->accessToken;
    }
}
