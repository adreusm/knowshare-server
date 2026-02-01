<?php

namespace App\Service;

use App\DTO\CreateNoteRequest;
use App\DTO\UpdateNoteRequest;
use App\Entity\Domain;
use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;
use App\Helper\PaginationHelper;
use App\Interface\NoteServiceInterface;
use App\Repository\DomainRepository;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class NoteService implements NoteServiceInterface
{
    public function __construct(
        private NoteRepository $noteRepository,
        private DomainRepository $domainRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createNote(User $user, CreateNoteRequest $request): Note
    {
        $domain = $this->domainRepository->findOneByIdAndUser($request->domain_id, $user);
        if (!$domain) {
            throw new \RuntimeException('Domain not found');
        }

        $note = new Note();
        $note->setUser($user);
        $note->setDomain($domain);
        $note->setTitle($request->title);
        $note->setContent($request->content);
        $note->setAccessType($request->access_type ?? 'public');

        $this->entityManager->persist($note);

        // Add tags if provided
        if (!empty($request->tag_ids)) {
            $this->attachTagsToNote($note, $user, $request->tag_ids);
        }

        $this->entityManager->flush();

        return $note;
    }

    public function updateNote(Note $note, UpdateNoteRequest $request): Note
    {
        if ($request->domain_id !== null) {
            $domain = $this->domainRepository->findOneByIdAndUser($request->domain_id, $note->getUser());
            if (!$domain) {
                throw new \RuntimeException('Domain not found');
            }
            $note->setDomain($domain);
        }

        if ($request->title !== null) {
            $note->setTitle($request->title);
        }
        if ($request->content !== null) {
            $note->setContent($request->content);
        }
        if ($request->access_type !== null) {
            $note->setAccessType($request->access_type);
        }

        // Update tags if provided
        if ($request->tag_ids !== null) {
            $note->getTags()->clear();
            if (!empty($request->tag_ids)) {
                $this->attachTagsToNote($note, $note->getUser(), $request->tag_ids);
            }
        }

        $this->entityManager->flush();

        return $note;
    }

    public function deleteNote(Note $note): void
    {
        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    public function getUserNotes(User $user): array
    {
        return $this->noteRepository->findByUser($user);
    }

    public function getUserNotesPaginated(User $user, int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array
    {
        $query = $this->noteRepository->findByUserQuery($user, $filters, $sort);
        return PaginationHelper::paginate($query, $page, $limit);
    }

    public function getPublicFeed(int $limit = 50, int $offset = 0): array
    {
        return $this->noteRepository->findPublicNotes($limit, $offset);
    }

    public function getPublicFeedPaginated(int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array
    {
        $query = $this->noteRepository->findPublicNotesQuery($filters, $sort);
        return PaginationHelper::paginate($query, $page, $limit);
    }

    public function getSubscriberFeed(User $user, int $limit = 50, int $offset = 0): array
    {
        return $this->noteRepository->findSubscriberNotes($user, $limit, $offset);
    }

    public function getSubscriberFeedPaginated(User $user, int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array
    {
        $query = $this->noteRepository->findSubscriberNotesQuery($user, $filters, $sort);
        return PaginationHelper::paginate($query, $page, $limit);
    }

    public function getNotesByDomain(User $user, int $domainId): array
    {
        return $this->noteRepository->findByDomain($domainId, $user);
    }

    public function getNoteById(User $user, int $id): ?Note
    {
        return $this->noteRepository->findOneByIdAndUser($id, $user);
    }

    /**
     * Attach tags to a note
     * @param int[] $tagIds
     */
    private function attachTagsToNote(Note $note, User $user, array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            $tag = $this->tagRepository->findOneByIdAndUser($tagId, $user);
            if ($tag) {
                $note->addTag($tag);
            }
        }
    }
}
