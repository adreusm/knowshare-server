<?php

namespace App\Interface;

use App\DTO\CreateTagRequest;
use App\DTO\UpdateTagRequest;
use App\Entity\Tag;
use App\Entity\User;

interface TagServiceInterface
{
    public function createTag(User $user, CreateTagRequest $request): Tag;

    public function updateTag(Tag $tag, UpdateTagRequest $request): Tag;

    public function deleteTag(Tag $tag): void;

    /**
     * @return Tag[]
     */
    public function getUserTags(User $user): array;

    /**
     * Get paginated user tags
     * @return array{items: Tag[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getUserTagsPaginated(User $user, int $page = 1, int $limit = 20): array;

    public function getTagById(User $user, int $id): ?Tag;
}



