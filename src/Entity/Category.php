<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    private ?string $nameDe = null;

    #[ORM\Column(length: 128)]
    private ?string $nameEn = null;

    #[ORM\Column(length: 128)]
    private ?string $aliasDe = null;

    #[ORM\Column(length: 128)]
    private ?string $aliasEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionDe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $products;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    public function setNameDe(string $nameDe): static
    {
        $this->nameDe = $nameDe;

        return $this;
    }

    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }

    public function setNameEn(string $nameEn): static
    {
        $this->nameEn = $nameEn;

        return $this;
    }

    public function getAliasDe(): ?string
    {
        return $this->aliasDe;
    }

    public function setAliasDe(string $aliasDe): static
    {
        $this->aliasDe = $aliasDe;

        return $this;
    }

    public function getAliasEn(): ?string
    {
        return $this->aliasEn;
    }

    public function setAliasEn(string $aliasEn): static
    {
        $this->aliasEn = $aliasEn;

        return $this;
    }

    public function getDescriptionDe(): ?string
    {
        return $this->descriptionDe;
    }

    public function setDescriptionDe(?string $descriptionDe): static
    {
        $this->descriptionDe = $descriptionDe;

        return $this;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(?string $descriptionEn): static
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(?Collection $children = null): self
    {
        $this->children = $children;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return null !== $this->getNameDe() ?: '';
    }

    public function getName(string $locale)
    {
        $method = __FUNCTION__.ucfirst($locale);

        return $this->$method();
    }

    public function getAlias(string $locale)
    {
        $method = __FUNCTION__.ucfirst($locale);

        return $this->$method();
    }

    public function getDescription(string $locale): string
    {
        $method = __FUNCTION__.ucfirst($locale);

        if (method_exists($this, $method)) {
            return $this->$method() ?? '';
        }

        return $this->getDescriptionEn() ?? '';
    }
}
