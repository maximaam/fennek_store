<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    // Images stored as string, separated with -
    public const string IMG_SEPARATOR = '-';

    public const array COLORS = [
        'white', 'silver', 'gray', 'black',
        'beige', 'yellow', 'gold', 'orange', 'red',
        'pink', 'violet', 'fuchsia', 'purple',
        'lightblue', 'blue', 'darkblue',
        'green', 'lightgreen',
        'burlywood', 'brown', 'maroon', 'darkred',
    ];

    public const array SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $itemNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $titleDe = null;

    #[ORM\Column(length: 255)]
    private ?string $titleEn = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descriptionDe = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descriptionEn = null;

    /** @var array<string> $colors */
    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $colors = [];

    /** @var array<string>|null $sizes */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $sizes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column]
    private ?bool $topItem = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

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

    public function getTitleDe(): ?string
    {
        return $this->titleDe;
    }

    public function setTitleDe(string $titleDe): static
    {
        $this->titleDe = $titleDe;

        return $this;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function setTitleEn(string $titleEn): static
    {
        $this->titleEn = $titleEn;

        return $this;
    }

    public function getDescriptionDe(): ?string
    {
        return $this->descriptionDe;
    }

    public function setDescriptionDe(string $descriptionDe): static
    {
        $this->descriptionDe = $descriptionDe;

        return $this;
    }

    public function getDescriptionEn(): ?string
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isTopItem(): ?bool
    {
        return $this->topItem;
    }

    public function setTopItem(bool $topItem): static
    {
        $this->topItem = $topItem;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
