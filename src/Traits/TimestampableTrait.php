<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
trait TimestampableTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Used by the import command.
     *
     * @throws \DateMalformedStringException
     */
    public function setCreatedAtLegacy(string $date): self
    {
        $this->createdAt = new \DateTimeImmutable($date);

        return $this;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function setUpdatedAtLegacy(?string $date = null): self
    {
        $datetime = 'NULL' === $date || null === $date ? new \DateTimeImmutable() : new \DateTimeImmutable($date);
        $this->updatedAt = $datetime;

        return $this;
    }
}
