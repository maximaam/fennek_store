<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $itemNumber = null;

    #[ORM\Column(length: 255)]
    private string $titleDe;

    #[ORM\Column(length: 255)]
    private string $titleEn;

    #[ORM\Column(length: 255)]
    private string $titleDeSlug;

    #[ORM\Column(length: 255)]
    private string $titleEnSlug;

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionDe;

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEn;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $colors = [];

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sizes = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $price;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $topItem = false;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER', inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    /** @var Collection<int, MediaImage> */
    #[ORM\OneToMany(targetEntity: MediaImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    private Collection $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemNumber(): ?string
    {
        return $this->itemNumber;
    }

    public function setItemNumber(?string $itemNumber): static
    {
        $this->itemNumber = $itemNumber;

        return $this;
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

    public function getTitleEn(): string
    {
        return $this->titleEn;
    }

    public function setTitleEn(string $titleEn): static
    {
        $this->titleEn = $titleEn;

        return $this;
    }

    public function getTitleDeSlug(): string
    {
        return $this->titleDeSlug;
    }

    public function setTitleDeSlug(string $titleDeSlug): static
    {
        $this->titleDeSlug = $titleDeSlug;

        return $this;
    }

    public function getTitleEnSlug(): string
    {
        return $this->titleEnSlug;
    }

    public function setTitleEnSlug(string $titleEnSlug): static
    {
        $this->titleEnSlug = $titleEnSlug;

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

    public function getDescriptionEn(): string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(string $descriptionEn): static
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getColors(): array
    {
        return $this->colors;
    }

    /**
     * @param array<string> $colors
     */
    public function setColors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getSizes(): ?array
    {
        return $this->sizes;
    }

    /**
     * @param array<string>|null $sizes
     */
    public function setSizes(?array $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isTopItem(): bool
    {
        return $this->topItem;
    }

    public function setTopItem(bool $topItem): static
    {
        $this->topItem = $topItem;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

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
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(MediaImage $image): self
    {
        if ($this->images->removeElement($image) && $image->getProduct() === $this) {
            $image->setProduct(null);
        }

        return $this;
    }

    // ─────────────────────────────
    // Extra Entity Methods
    // ─────────────────────────────

    public function getTitle(string $locale): string
    {
        return match ($locale) {
            'en' => $this->titleEn,
            default => $this->titleDe,
        };
    }

    public function getTitleSlug(string $locale): string
    {
        return match ($locale) {
            'en' => $this->titleEnSlug,
            default => $this->titleDeSlug,
        };
    }

    public function getDescription(string $locale): string
    {
        return match ($locale) {
            'en' => $this->descriptionEn,
            default => $this->descriptionDe,
        };
    }
}
