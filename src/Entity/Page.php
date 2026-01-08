<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PageRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Page
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $titleDe;

    #[ORM\Column(length: 255)]
    private string $aliasDe;

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionDe;

    #[ORM\Column(length: 255)]
    private string $titleEn;

    #[ORM\Column(length: 255)]
    private string $aliasEn;

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEn;

    /** @var Collection<int, MediaImage> */
    #[ORM\OneToMany(targetEntity: MediaImage::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitleDe(): string
    {
        return $this->titleDe;
    }

    public function setTitleDe(string $titleDe): static
    {
        $this->titleDe = $titleDe;

        return $this;
    }

    public function getAliasDe(): string
    {
        return $this->aliasDe;
    }

    public function setAliasDe(string $aliasDe): static
    {
        $this->aliasDe = $aliasDe;

        return $this;
    }

    public function getDescriptionDe(): string
    {
        return $this->descriptionDe;
    }

    public function setDescriptionDe(string $descriptionDe): static
    {
        $this->descriptionDe = $descriptionDe;

        return $this;
    }

    public function getTitleEn(): string
    {
        return $this->titleEn;
    }

    public function setTitleEn(string $titleEn): static
    {
        $this->titleEn = $titleEn;

        return $this;
    }

    public function getAliasEn(): string
    {
        return $this->aliasEn;
    }

    public function setAliasEn(string $aliasEn): static
    {
        $this->aliasEn = $aliasEn;

        return $this;
    }

    public function getDescriptionEn(): string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(string $descriptionEn): static
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    /** @return Collection<int, MediaImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(MediaImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setPage($this);
        }

        return $this;
    }

    public function removeImage(MediaImage $image): self
    {
        if ($this->images->removeElement($image) && $image->getPage() === $this) {
            $image->setPage(null);
        }

        return $this;
    }

    // ─────────────────────────────
    // Extra Entity Methods
    // ─────────────────────────────

    public function getTitle(string $locale): ?string
    {
        $key = __FUNCTION__.ucfirst($locale);

        return $this->$key();
    }

    public function getAlias(string $locale): ?string
    {
        $key = __FUNCTION__.ucfirst($locale);

        return $this->$key();
    }

    public function getDescription(string $locale): ?string
    {
        $key = __FUNCTION__.ucfirst($locale);

        return $this->$key();
    }
}
