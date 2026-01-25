<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PayPalClient2
{
    private const string ORDERS_ENDPOINT = '/v2/checkout/orders';
    private const string TOKEN_ENDPOINT = '/v1/oauth2/token';

    private ?string $accessToken = null;
    private ?int $accessTokenExpiresAt = null;

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
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

    /** @return array<string, mixed> */
    public function createOrder(int $amountCents): array
    {
        return $this->request(
            Request::METHOD_POST,
            self::ORDERS_ENDPOINT,
            [
                'intent' => 'CAPTURE',
                /*
                'application_context' => [
                    'return_url' => 'https://127.0.0.1:8001/de/catalogue/sommerwaren',
                    'cancel_url' => 'https://127.0.0.1:8001/de/catalogue/winterwaren',
                ],
                */
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format($amountCents / 100, 2, '.', ''),
                    ],
                ]],
            ]
        );
    }

    /** @return array<string, mixed> */
    public function captureOrder(string $orderId): array
    {
        /*
        $order = $this->request(
            'GET',
            "/v2/checkout/orders/{$orderId}"
        );
        */

        /*if (($order['intent'] ?? null) !== 'CAPTURE') {
            throw new \LogicException('Order intent is not CAPTURE');
        }

        if (($order['status'] ?? null) !== 'APPROVED') {
            throw new \LogicException(
                sprintf('Order not approved, status: %s', $order['status'] ?? 'unknown')
            );
        }*/

        return $this->request(
            Request::METHOD_POST,
            \sprintf('%s/%s/capture', self::ORDERS_ENDPOINT, $orderId)
        );
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
    private function getAccessToken(): string
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
