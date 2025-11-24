<?php

namespace App\Interface;

use App\DTO\CreateNoteRequest;
use App\DTO\UpdateNoteRequest;
use App\Entity\Note;
use App\Entity\User;

interface NoteServiceInterface
{
    public function createNote(User $user, CreateNoteRequest $request): Note;

    public function updateNote(Note $note, UpdateNoteRequest $request): Note;

    public function deleteNote(Note $note): void;

    /**
     * @return Note[]
     */
    public function getUserNotes(User $user): array;

    /**
     * Get paginated user notes
     * @return array{items: Note[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getUserNotesPaginated(User $user, int $page = 1, int $limit = 20): array;

    /**
     * @return Note[]
     */
    public function getPublicFeed(int $limit = 50, int $offset = 0): array;

    /**
     * Get paginated public feed
     * @return array{items: Note[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getPublicFeedPaginated(int $page = 1, int $limit = 20): array;

    /**
     * @return Note[]
     */
    public function getSubscriberFeed(User $user, int $limit = 50, int $offset = 0): array;

    /**
     * Get paginated subscriber feed
     * @return array{items: Note[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getSubscriberFeedPaginated(User $user, int $page = 1, int $limit = 20): array;

    /**
     * @return Note[]
     */
    public function getNotesByDomain(User $user, int $domainId): array;

    public function getNoteById(User $user, int $id): ?Note;
}
