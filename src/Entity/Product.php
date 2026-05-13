<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\EntityHelper;
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
    #[ORM\OneToMany(targetEntity: MediaImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    /**
     * @var Collection<int, ProductTranslation>
     */
    #[ORM\OneToMany(targetEntity: ProductTranslation::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->translations = new ArrayCollection();
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

    /**
     * @return Collection<int, ProductTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setProduct($this);
        }

        return $this;
    }

    public function removeTranslation(ProductTranslation $translation): self
    {
        return $this;
    }

    public function getTranslationByLocale(string $locale): ProductTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        throw new \InvalidArgumentException(\sprintf('No translation found for locale "%s".', $locale));
    }

    public function getTitle(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getTitle();
    }

    public function getTitleDe(): string
    {
        return $this->getTitle(EntityHelper::LOCALE_DE);
    }

    public function getTitleEn(): string
    {
        return $this->getTitle(EntityHelper::LOCALE_EN);
    }

    public function getDescription(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getDescription();
    }

    public function getDescriptionDe(): string
    {
        return $this->getDescription(EntityHelper::LOCALE_DE);
    }

    public function getDescriptionEn(): string
    {
        return $this->getDescription(EntityHelper::LOCALE_EN);
    }

    public function getSlug(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getSlug();
    }

    public function getSlugDe(): string
    {
        return $this->getSlug(EntityHelper::LOCALE_DE);
    }

    public function getSlugEn(): string
    {
        return $this->getSlug(EntityHelper::LOCALE_EN);
    }
}
