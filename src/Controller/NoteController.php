<?php

namespace App\Controller;

use App\DTO\CreateNoteRequest;
use App\DTO\UpdateNoteRequest;
use App\Entity\Note;
use App\Entity\User;
use App\Interface\NoteServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/notes', name: 'api_notes_')]
#[OA\Tag(name: 'Notes', description: 'Note management endpoints')]
class NoteController extends AbstractController
{
    public function __construct(
        private NoteServiceInterface $noteService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes',
        summary: 'Get all notes for current user',
        description: 'Returns a paginated list of notes created by the authenticated user',
        security: [['bearer' => []]],
        tags: ['Notes']
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
        description: 'Paginated list of notes',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'domain_id', type: 'integer'),
                        new OA\Property(property: 'domain_name', type: 'string'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'access_type', type: 'string'),
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'author', type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'username', type: 'string'),
                            ]
                        ),
                    ]
                )),
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
        $sort = $request->query->get('sort');

        $filters = $this->extractFilters($request, ['domain_id', 'access_type', 'tag_id']);

        $result = $this->noteService->getUserNotesPaginated($user, $page, $limit, $filters, $sort);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('/feed', name: 'public_feed', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes/feed',
        summary: 'Get public feed',
        description: 'Returns a paginated feed of public notes from all users. This endpoint is publicly accessible and does not require authentication.',
        tags: ['Notes']
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
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        schema: new OA\Schema(type: 'string', default: '-created_at', example: '-created_at'),
        description: 'Sort field and direction. Use "-" prefix for descending (e.g., "-created_at", "title")'
    )]
    #[OA\Parameter(
        name: 'domain_id',
        in: 'query',
        schema: new OA\Schema(type: 'integer'),
        description: 'Filter by domain ID'
    )]
    #[OA\Parameter(
        name: 'author_id',
        in: 'query',
        schema: new OA\Schema(type: 'integer'),
        description: 'Filter by author ID'
    )]
    #[OA\Parameter(
        name: 'tag_id',
        in: 'query',
        schema: new OA\Schema(type: 'integer'),
        description: 'Filter by tag ID'
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        description: 'Search in title and content'
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of public notes',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'domain_id', type: 'integer'),
                        new OA\Property(property: 'domain_name', type: 'string'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'access_type', type: 'string'),
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'author', type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'username', type: 'string'),
                            ]
                        ),
                    ]
                )),
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
    public function publicFeed(Request $request): JsonResponse
    {
        // Public feed is accessible without authentication
        $page = max(1, (int) ($request->query->get('page') ?? 1));
        $limit = max(1, min(100, (int) ($request->query->get('limit') ?? 20)));
        $sort = $request->query->get('sort');

        $filters = $this->extractFilters($request, ['domain_id', 'author_id', 'tag_id', 'search']);

        $result = $this->noteService->getPublicFeedPaginated($page, $limit, $filters, $sort);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('/feed/subscribers', name: 'subscriber_feed', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes/feed/subscribers',
        summary: 'Get subscriber feed',
        description: 'Returns a paginated feed of subscriber-only notes from authors the user is subscribed to',
        security: [['bearer' => []]],
        tags: ['Notes']
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
        description: 'Paginated list of subscriber-only notes',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'domain_id', type: 'integer'),
                        new OA\Property(property: 'domain_name', type: 'string'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'access_type', type: 'string'),
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'author', type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'username', type: 'string'),
                            ]
                        ),
                    ]
                )),
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
    public function subscriberFeed(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) ($request->query->get('page') ?? 1));
        $limit = max(1, min(100, (int) ($request->query->get('limit') ?? 20)));

        $result = $this->noteService->getSubscriberFeedPaginated($user, $page, $limit);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/notes',
        summary: 'Create a new note',
        description: 'Creates a new note in a domain',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['domain_id', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'domain_id', type: 'integer', example: 1),
                    new OA\Property(property: 'title', type: 'string', minLength: 1, maxLength: 255, example: 'Present Tense'),
                    new OA\Property(property: 'content', type: 'string', example: 'The present tense is used to describe...'),
                    new OA\Property(property: 'access_type', type: 'string', enum: ['public', 'subscribers', 'private'], example: 'public'),
                    new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
                ]
            )
        ),
        tags: ['Notes']
    )]
    #[OA\Response(
        response: 201,
        description: 'Note created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'domain_id', type: 'integer'),
                new OA\Property(property: 'domain_name', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'content', type: 'string'),
                new OA\Property(property: 'access_type', type: 'string'),
                new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                    ]
                )),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'author', type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'username', type: 'string'),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Validation error')]
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

        $createRequest = new CreateNoteRequest(
            domain_id: $data['domain_id'] ?? null,
            title: $data['title'] ?? null,
            content: $data['content'] ?? null,
            access_type: $data['access_type'] ?? 'public',
            tag_ids: $data['tag_ids'] ?? null
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
            $note = $this->noteService->createNote($user, $createRequest);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->serializeNote($note), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes/{id}',
        summary: 'Get a note by ID',
        description: 'Returns a specific note by ID',
        security: [['bearer' => []]],
        tags: ['Notes']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Note ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Note details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'domain_id', type: 'integer'),
                new OA\Property(property: 'domain_name', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'content', type: 'string'),
                new OA\Property(property: 'access_type', type: 'string'),
                new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                    ]
                )),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'author', type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'username', type: 'string'),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Note not found')]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $note = $this->noteService->getNoteById($user, $id);
        if (!$note) {
            return new JsonResponse(['error' => 'Note not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializeNote($note));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/notes/{id}',
        summary: 'Update a note',
        description: 'Updates an existing note',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'domain_id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string', minLength: 1, maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'access_type', type: 'string', enum: ['public', 'subscribers', 'private']),
                    new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        tags: ['Notes']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Note ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Note updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'domain_id', type: 'integer'),
                new OA\Property(property: 'domain_name', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'content', type: 'string'),
                new OA\Property(property: 'access_type', type: 'string'),
                new OA\Property(property: 'tags', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                    ]
                )),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'author', type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'username', type: 'string'),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Note not found')]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $note = $this->noteService->getNoteById($user, $id);
        if (!$note) {
            return new JsonResponse(['error' => 'Note not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $updateRequest = new UpdateNoteRequest(
            domain_id: $data['domain_id'] ?? null,
            title: $data['title'] ?? null,
            content: $data['content'] ?? null,
            access_type: $data['access_type'] ?? null,
            tag_ids: $data['tag_ids'] ?? null
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
            $note = $this->noteService->updateNote($note, $updateRequest);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->serializeNote($note));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/notes/{id}',
        summary: 'Delete a note',
        description: 'Deletes a note',
        security: [['bearer' => []]],
        tags: ['Notes']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Note ID'
    )]
    #[OA\Response(response: 204, description: 'Note deleted successfully')]
    #[OA\Response(response: 404, description: 'Note not found')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $note = $this->noteService->getNoteById($user, $id);
        if (!$note) {
            return new JsonResponse(['error' => 'Note not found'], Response::HTTP_NOT_FOUND);
        }

        $this->noteService->deleteNote($note);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeNote(Note $note): array
    {
        $tags = [];
        foreach ($note->getTags() as $tag) {
            $tags[] = [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }

        return [
            'id' => $note->getId(),
            'domain_id' => $note->getDomain()?->getId(),
            'domain_name' => $note->getDomain()?->getName(),
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
            'access_type' => $note->getAccessType(),
            'tags' => $tags,
            'created_at' => $note->getCreatedAt()?->format('c'),
            'updated_at' => $note->getUpdatedAt()?->format('c'),
            'author' => [
                'id' => $note->getUser()?->getId(),
                'username' => $note->getUser()?->getUsername(),
            ],
        ];
    }

    /**
     * Extract filters from request
     * @param array<string> $allowedFilters
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request, array $allowedFilters): array
    {
        $filters = [];
        foreach ($allowedFilters as $filterKey) {
            $value = $request->query->get($filterKey);
            if ($value !== null && $value !== '') {
                // Convert to integer if it looks like a number
                if (is_numeric($value) && !str_contains((string)$value, '.')) {
                    $filters[$filterKey] = (int) $value;
                } else {
                    $filters[$filterKey] = $value;
                }
            }
        }
        return $filters;
    }
}

