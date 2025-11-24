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

    public function getTagById(User $user, int $id): ?Tag;
}



