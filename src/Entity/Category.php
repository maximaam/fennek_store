<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\EntityHelper;
use App\Repository\CategoryRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(unique: true, nullable: true)]
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

    /**
     * @var Collection<int, CategoryTranslation>
     */
    #[ORM\OneToMany(targetEntity: CategoryTranslation::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

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

    /**
     * @return Collection<int, CategoryTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(CategoryTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setCategory($this);
        }

        return $this;
    }

    public function removeTranslation(CategoryTranslation $translation): static
    {
        if ($this->translations->removeElement($translation) && $translation->getCategory() === $this) {
            // set the owning side to null (unless already changed
            $translation->setCategory(null);
        }

        return $this;
    }

    // | Extra methods | \\
    // ----------------- \\

    public function getTranslationByLocale(string $locale): ?CategoryTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return $this->getNameDe();
    }

    public function getNameDe(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)
            ?->getName();
    }

    public function getNameEn(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)
            ?->getName();
    }

    public function getAliasDe(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)
            ?->getAlias();
    }

    public function getAliasEn(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)
            ?->getAlias();
    }

    public function getDescriptionDe(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)
            ?->getDescription();
    }

    public function getDescriptionEn(): ?string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)
            ?->getDescription();
    }

    public function getName(string $locale): ?string
    {
        $method = __FUNCTION__.ucfirst($locale);

        return $this->$method();
    }

    public function getAlias(string $locale): ?string
    {
        $method = __FUNCTION__.ucfirst($locale);

        return $this->$method();
    }

    public function getDescription(string $locale): ?string
    {
        $method = __FUNCTION__.ucfirst($locale);

        return $this->$method();
    }
}
