<?php

namespace App\Entity;

use App\Repository\NoteTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteTagRepository::class)]
#[ORM\Table(name: 'note_tags')]
#[ORM\UniqueConstraint(name: 'note_tag_unique', columns: ['note_id', 'tag_id'])]
#[ORM\HasLifecycleCallbacks]
class NoteTag
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'noteTags')]
    #[ORM\JoinColumn(name: 'note_id', nullable: false, onDelete: 'CASCADE')]
    private ?Note $note = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'noteTags')]
    #[ORM\JoinColumn(name: 'tag_id', nullable: false, onDelete: 'CASCADE')]
    private ?Tag $tag = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): static
    {
        $this->tag = $tag;
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

    #[ORM\PrePersist]
    public function setTimestampsOnCreate(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
