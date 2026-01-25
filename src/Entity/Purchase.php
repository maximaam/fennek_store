<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PayPalStatus;
use App\Repository\PurchaseRepository;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Purchase
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $orderId;

    #[ORM\Column(length: 32)]
    private PayPalStatus $status;

    /** @var array<int|string, mixed> $payload */
    #[ORM\Column]
    private array $payload = [];

    /** @var array<int|string, mixed> $product */
    #[ORM\Column]
    private array $product = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $shipped = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getStatus(): PayPalStatus
    {
        return $this->status;
    }

    public function setStatus(PayPalStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return array<int|string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /** @param  array<int|string, mixed> $payload */
    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    /** @return array<int|string, mixed>*/
    public function getProduct(): array
    {
        return $this->product;
    }

    /** @param array<int|string, mixed> $product */
    public function setProduct(array $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function isShipped(): bool
    {
        return $this->shipped;
    }

    public function setShipped(bool $shipped): static
    {
        $this->shipped = $shipped;

        return $this;
    }

    // ───────────────────────────────────────────────
    // Extra Entity Methods - For easyadmin rendering
    // ───────────────────────────────────────────────

    public function getTotalAmount(): int
    {
        return $this->getProduct()['totals']['total'];
    }

    public function getBuyerFullName(): ?string
    {
        $payer = $this->getPayload()['payer'] ?? null;
        if (null === $payer) {
            return null;
        }

        return \sprintf('%s %s', $payer['name']['given_name'], $payer['name']['surname']);
    }
}
