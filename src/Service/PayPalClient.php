<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PayPal\OrderCaptureDto;
use App\Dto\PayPal\OrderDto;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PayPalClient
{
    private const string ORDERS_ENDPOINT = '/v2/checkout/orders';
    private const string TOKEN_ENDPOINT = '/v1/oauth2/token';

    private ?string $accessToken = null;
    private ?int $accessTokenExpiresAt = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire('%env(PAYPAL_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(PAYPAL_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(PAYPAL_BASE_URL)%')]
        private readonly string $baseUrl,
    ) {
    }

    public function createOrder(int $amountCents): OrderDto
    {
        $data = $this->request(
            Request::METHOD_POST,
            self::ORDERS_ENDPOINT,
            [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format($amountCents / 100, 2, '.', ''),
                    ],
                ]],
            ]
        );

        return PayPalMapper::mapOrder($data);
    }

    public function captureOrder(string $orderId): OrderCaptureDto
    {
        $data = $this->request(
            Request::METHOD_POST,
            \sprintf('%s/%s/capture', self::ORDERS_ENDPOINT, $orderId),
        );

        return PayPalMapper::mapCapture($data);
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array|\stdClass|null $json = null): array
    {
        try {
            $response = $this->client->request(
                $method,
                $this->baseUrl.$uri,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->getAccessToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $json,
                ]
            );

            return $response->toArray(); // throws on 4xx/5xx
        } catch (HttpExceptionInterface $e) {
            throw new \RuntimeException(
                \sprintf('PayPal API error (%d): %s', $e->getResponse()->getStatusCode(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @throws TransportExceptionInterface|DecodingExceptionInterface
     */
    private function authenticate(): void
    {
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->baseUrl.self::TOKEN_ENDPOINT,
            [
                'auth_basic' => [$this->clientId, $this->clientSecret],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        $data = $response->toArray();
        $this->accessToken = $data['access_token'];
        $this->accessTokenExpiresAt = time() + $data['expires_in'] - 60; // safety margin
    }

    /**
     * @throws DecodingExceptionInterface|TransportExceptionInterface
     */
    private function getAccessToken(): ?string
    {
        if (
            null === $this->accessToken
            || null === $this->accessTokenExpiresAt
            || time() >= $this->accessTokenExpiresAt
        ) {
            $this->authenticate();
        }

        return $this->accessToken;
    }
}
