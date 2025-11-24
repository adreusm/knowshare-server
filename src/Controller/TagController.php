<?php

namespace App\Controller;

use App\DTO\CreateTagRequest;
use App\DTO\UpdateTagRequest;
use App\Entity\Tag;
use App\Entity\User;
use App\Interface\TagServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/tags', name: 'api_tags_')]
#[OA\Tag(name: 'Tags', description: 'Tag management endpoints')]
class TagController extends AbstractController
{
    public function __construct(
        private TagServiceInterface $tagService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/tags',
        summary: 'Get all tags for current user',
        description: 'Returns a paginated list of all tags created by the authenticated user',
        security: [['bearer' => []]],
        tags: ['Tags']
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
        description: 'Page number'
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100),
        description: 'Number of items per page'
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of tags',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Grammar'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]
                    )
                ),
                new OA\Property(property: 'pagination', type: 'object',
                    properties: [
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'total_pages', type: 'integer'),
                    ]
                ),
            ]
        )
    )]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) ($request->query->get('page') ?? 1));
        $limit = max(1, min(100, (int) ($request->query->get('limit') ?? 20)));

        $result = $this->tagService->getUserTagsPaginated($user, $page, $limit);
        $data = array_map(fn(Tag $tag) => [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'created_at' => $tag->getCreatedAt()?->format('c'),
        ], $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/tags',
        summary: 'Create a new tag',
        description: 'Creates a new tag for the authenticated user',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 50, example: 'Grammar'),
                ]
            )
        ),
        tags: ['Tags']
    )]
    #[OA\Response(
        response: 201,
        description: 'Tag created successfully',
        content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Grammar'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                ]
        )
    )]
    #[OA\Response(response: 400, description: 'Validation error')]
    #[OA\Response(response: 409, description: 'Tag already exists')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $createRequest = new CreateTagRequest(
            name: $data['name'] ?? null
        );

        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tag = $this->tagService->createTag($user, $createRequest);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'created_at' => $tag->getCreatedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/tags/{id}',
        summary: 'Get a tag by ID',
        description: 'Returns a specific tag by ID',
        security: [['bearer' => []]],
        tags: ['Tags']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Tag ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Tag details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tag not found')]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tag = $this->tagService->getTagById($user, $id);
        if (!$tag) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'created_at' => $tag->getCreatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/tags/{id}',
        summary: 'Update a tag',
        description: 'Updates an existing tag',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 50),
                ]
            )
        ),
        tags: ['Tags']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Tag ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Tag updated successfully',
        content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tag not found')]
    #[OA\Response(response: 409, description: 'Tag with this name already exists')]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tag = $this->tagService->getTagById($user, $id);
        if (!$tag) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $updateRequest = new UpdateTagRequest(
            name: $data['name'] ?? null
        );

        $errors = $this->validator->validate($updateRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tag = $this->tagService->updateTag($tag, $updateRequest);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'created_at' => $tag->getCreatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/tags/{id}',
        summary: 'Delete a tag',
        description: 'Deletes a tag',
        security: [['bearer' => []]],
        tags: ['Tags']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Tag ID'
    )]
    #[OA\Response(response: 204, description: 'Tag deleted successfully')]
    #[OA\Response(response: 404, description: 'Tag not found')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tag = $this->tagService->getTagById($user, $id);
        if (!$tag) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        $this->tagService->deleteTag($tag);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

