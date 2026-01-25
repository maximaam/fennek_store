<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PayPalClient
{
    public const string ENDPOINT = '/v2/checkout/orders';
    private string $accessToken;

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __construct(
        private readonly HttpClientInterface $client,

        #[Autowire('%env(PAYPAL_CLIENT_ID)%')]
        private readonly string $paypalClientId,
        #[Autowire('%env(PAYPAL_CLIENT_SECRET)%')]
        private readonly string $paypalClientSecret,
        #[Autowire('%env(PAYPAL_BASE_URL)%')]
        private readonly string $paypalBaseUrl,
    ) {
        $this->authenticate();
    }

    /** @return array<string, mixed> */
    public function createOrder(int $amountCents): array
    {
        return $this->request(
            Request::METHOD_POST,
            self::ENDPOINT,
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
            \sprintf('/v2/checkout/orders/%s/capture', $orderId),
        );
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @throws TransportExceptionInterface|\Exception|DecodingExceptionInterface
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array|\stdClass|null $json = null): array
    {
        $response = $this->client->request(
            $method,
            $this->paypalBaseUrl.$uri,
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $json ?? new \stdClass(),
            ]
        );

        return $response->toArray(false);
    }

    /**
     * @throws \Exception|DecodingExceptionInterface|TransportExceptionInterface
     */
    private function authenticate(): void
    {
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->paypalBaseUrl.'/v1/oauth2/token',
            [
                'auth_basic' => [$this->paypalClientId, $this->paypalClientSecret],
                'body' => ['grant_type' => 'client_credentials'],
            ]
        );

        $this->accessToken = $response->toArray()['access_token'];
    }

    /**
     * @throws DecodingExceptionInterface|TransportExceptionInterface
     */
    private function getAccessToken(): string
    {
        if (!isset($this->accessToken)) {
            $this->authenticate();
        }

        return $this->accessToken;
    }
}
