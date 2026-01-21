<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PayPalStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $orderId;

    #[ORM\Column(length: 32)]
    private PayPalStatus $status;

    #[ORM\Column]
    private array $payload = [];

    #[ORM\Column]
    private array $product = [];

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

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getProduct(): array
    {
        return $this->product;
    }

    public function setProduct(array $product): static
    {
        $this->product = $product;

        return $this;
    }
}
