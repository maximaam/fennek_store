<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryTranslationRepository::class)]
#[ORM\Index(name: 'idx_category_alias', columns: ['alias'])]
#[ORM\UniqueConstraint(
    name: 'uniq_category_alias_locale',
    columns: ['alias', 'locale']
)]
class CategoryTranslation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2)]
    private string $locale;

    #[ORM\Column(length: 64)]
    private string $name;

    #[ORM\Column(length: 128)]
    private string $alias;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

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
}
