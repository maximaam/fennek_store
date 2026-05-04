<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\EntityHelper;
use App\Repository\PageRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Page implements \Stringable
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, MediaImage> */
    #[ORM\OneToMany(targetEntity: MediaImage::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    /**
     * @var Collection<int, PageTranslation>
     */
    #[ORM\OneToMany(targetEntity: PageTranslation::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
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

    /**
     * @return Collection<int, PageTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(PageTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setPage($this);
        }

        return $this;
    }

    public function removeTranslation(PageTranslation $translation): static
    {
        return $this;
    }

    // | Extra methods | \\
    // ----------------- \\

    public function getTranslationByLocale(string $locale): PageTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        throw new \InvalidArgumentException(\sprintf('No translation found for locale "%s".', $locale));
    }

    public function __toString(): string
    {
        return $this->getTitleDe();
    }

    public function getTitleDe(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)->getTitle();
    }

    public function getTitleEn(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)->getTitle();
    }

    public function getAliasDe(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)->getAlias();
    }

    public function getAliasEn(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)->getAlias();
    }

    public function getDescriptionDe(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_DE)->getDescription();
    }

    public function getDescriptionEn(): string
    {
        return $this->getTranslationByLocale(EntityHelper::LOCALE_EN)->getDescription();
    }

    public function getTitle(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getTitle();
    }

    public function getAlias(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getAlias();
    }

    public function getDescription(string $locale): string
    {
        return $this->getTranslationByLocale($locale)->getDescription();
    }
}
