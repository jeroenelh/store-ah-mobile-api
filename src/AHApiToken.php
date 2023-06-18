<?php

namespace Microit\StoreAhWebApi;

use DateInterval;
use DateTimeImmutable;
use Exception;
use Microit\StoreBase\HttpClient;
use Nyholm\Psr7\Stream;
use Psr\Http\Client\ClientExceptionInterface;

class AHApiToken
{
    protected HttpClient $httpClient;

    protected string $accessToken = '';

    protected string $refreshToken = '';

    protected DateTimeImmutable $expireDate;

    /**
     * @throws ClientExceptionInterface
     */
    public function __construct()
    {
        $this->httpClient = new HttpClient('https://api.ah.nl/mobile-auth/v1/auth/token');
        $this->expireDate = new DateTimeImmutable();
        $this->renewToken();
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function renewToken(): void
    {
        $request = $this->httpClient->createRequest('post', 'anonymous');
        if (! empty($this->refreshToken)) {
            $request = $this->httpClient->createRequest('post', 'refresh');
        }

        $request = $request->withBody(Stream::create(json_encode([
            'clientId' => 'appie',
            'refreshToken' => $this->refreshToken ?: null,
        ])));

        $request = $request->withAddedHeader('content-type', 'application/json');
        $response = $this->httpClient->getResponse($request);

        $jsonResponse = json_decode($response->getBody()->getContents());
        assert(is_object($jsonResponse));

        $this->processTokenResponse($jsonResponse);
    }

    private function processTokenResponse(object $jsonResponse): void
    {
        assert(isset($jsonResponse->access_token));
        assert(isset($jsonResponse->refresh_token));
        assert(isset($jsonResponse->expires_in));

        $this->accessToken = (string) $jsonResponse->access_token;
        $this->refreshToken = (string) $jsonResponse->refresh_token;
        $interval = DateInterval::createFromDateString((int) $jsonResponse->expires_in.' seconds');
        $this->expireDate = (new DateTimeImmutable())->add($interval);
    }

    public function isValid(): bool
    {
        return $this->expireDate->getTimestamp() > time();
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getAccessToken(): string
    {
        if (! $this->isValid()) {
            $this->renewToken();
        }

        return $this->accessToken;
    }
}
