<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeImmutable $updatedAt;

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
