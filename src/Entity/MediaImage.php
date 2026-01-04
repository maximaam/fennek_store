<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MediaImageOwner;
use App\Repository\MediaImageRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MediaImageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class MediaImage
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'media_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Product $product = null;

    #[ORM\Column(enumType: MediaImageOwner::class)]
    private MediaImageOwner $owner;

    #[ORM\OneToOne(mappedBy: 'image')]
    private ?Page $page = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setImageFile(?File $file): static
    {
        $this->imageFile = $file;
        if ($file instanceof File) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getOwner(): MediaImageOwner
    {
        return $this->owner;
    }

    public function setOwner(MediaImageOwner $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        // unset the owning side of the relation if necessary
        if (null === $page && null !== $this->page) {
            $this->page->setImage(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $page && $page->getImage() !== $this) {
            $page->setImage($this);
        }

        $this->page = $page;

        return $this;
    }
}
