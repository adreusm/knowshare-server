<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
#[ORM\HasLifecycleCallbacks]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Domain $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    private string $access_type = 'public'; // 'public', 'subscribers', 'private'

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\OneToMany(targetEntity: NoteTag::class, mappedBy: 'note', orphanRemoval: true)]
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

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getAccessType(): string
    {
        return $this->access_type;
    }

    public function setAccessType(string $access_type): static
    {
        $this->access_type = $access_type;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
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
            $noteTag->setNote($this);
        }
        return $this;
    }

    public function removeNoteTag(NoteTag $noteTag): static
    {
        if ($this->noteTags->removeElement($noteTag)) {
            if ($noteTag->getNote() === $this) {
                $noteTag->setNote(null);
            }
        }
        return $this;
    }

    /**
     * Get tags associated with this note
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        $tags = new ArrayCollection();
        foreach ($this->noteTags as $noteTag) {
            $tags->add($noteTag->getTag());
        }
        return $tags;
    }

    #[ORM\PrePersist]
    public function setTimestampsOnCreate(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setTimestampsOnUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }
}
