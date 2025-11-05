<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
#[ORM\UniqueConstraint(name: 'user_tag_unique', columns: ['user_id', 'name'])]
#[ORM\HasLifecycleCallbacks]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\OneToMany(targetEntity: NoteTag::class, mappedBy: 'tag', orphanRemoval: true)]
    private Collection $noteTags;

    public function __construct()
    {
        $this->noteTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return Collection<int, NoteTag>
     */
    public function getNoteTags(): Collection
    {
        return $this->noteTags;
    }

    public function addNoteTag(NoteTag $noteTag): static
    {
        if (!$this->noteTags->contains($noteTag)) {
            $this->noteTags->add($noteTag);
            $noteTag->setTag($this);
        }
        return $this;
    }

    public function removeNoteTag(NoteTag $noteTag): static
    {
        if ($this->noteTags->removeElement($noteTag)) {
            if ($noteTag->getTag() === $this) {
                $noteTag->setTag(null);
            }
        }
        return $this;
    }

    #[ORM\PrePersist]
    public function setTimestampsOnCreate(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }
}

