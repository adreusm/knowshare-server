<?php

namespace App\Service;

use App\DTO\CreateTagRequest;
use App\DTO\UpdateTagRequest;
use App\Entity\Tag;
use App\Entity\User;
use App\Helper\PaginationHelper;
use App\Interface\TagServiceInterface;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class TagService implements TagServiceInterface
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createTag(User $user, CreateTagRequest $request): Tag
    {
        // Check if tag with same name already exists for this user
        $existingTag = $this->tagRepository->findOneByNameAndUser($request->name, $user);
        if ($existingTag) {
            throw new \RuntimeException('Tag with this name already exists');
        }

        $tag = new Tag();
        $tag->setUser($user);
        $tag->setName($request->name);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    public function updateTag(Tag $tag, UpdateTagRequest $request): Tag
    {
        if ($request->name !== null) {
            // Check if tag with same name already exists for this user (excluding current tag)
            $existingTag = $this->tagRepository->findOneByNameAndUser($request->name, $tag->getUser());
            if ($existingTag && $existingTag->getId() !== $tag->getId()) {
                throw new \RuntimeException('Tag with this name already exists');
            }
            $tag->setName($request->name);
        }

        $this->entityManager->flush();

        return $tag;
    }

    public function deleteTag(Tag $tag): void
    {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    public function getUserTags(User $user): array
    {
        return $this->tagRepository->findByUser($user);
    }

    public function getUserTagsPaginated(User $user, int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array
    {
        $query = $this->tagRepository->findByUserQuery($user, $filters, $sort);
        return PaginationHelper::paginate($query, $page, $limit);
    }

    public function getTagById(User $user, int $id): ?Tag
    {
        return $this->tagRepository->findOneByIdAndUser($id, $user);
    }
}

