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
        description: 'Returns a list of all notes created by the authenticated user',
        security: [['bearer' => []]],
        tags: ['Notes']
    )]
    #[OA\Response(
        response: 200,
        description: 'List of notes',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(
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
        ))
    )]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $notes = $this->noteService->getUserNotes($user);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $notes);

        return new JsonResponse($data);
    }

    #[Route('/feed', name: 'public_feed', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes/feed',
        summary: 'Get public feed',
        description: 'Returns a feed of public notes from all users',
        security: [['bearer' => []]],
        tags: ['Notes']
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 50),
        description: 'Number of notes to return'
    )]
    #[OA\Parameter(
        name: 'offset',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 0),
        description: 'Number of notes to skip'
    )]
    #[OA\Response(
        response: 200,
        description: 'List of public notes',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(
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
        ))
    )]
    public function publicFeed(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = (int) ($request->query->get('limit') ?? 50);
        $offset = (int) ($request->query->get('offset') ?? 0);

        $notes = $this->noteService->getPublicFeed($limit, $offset);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $notes);

        return new JsonResponse($data);
    }

    #[Route('/feed/subscribers', name: 'subscriber_feed', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/notes/feed/subscribers',
        summary: 'Get subscriber feed',
        description: 'Returns a feed of subscriber-only notes from authors the user is subscribed to',
        security: [['bearer' => []]],
        tags: ['Notes']
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 50),
        description: 'Number of notes to return'
    )]
    #[OA\Parameter(
        name: 'offset',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 0),
        description: 'Number of notes to skip'
    )]
    #[OA\Response(
        response: 200,
        description: 'List of subscriber-only notes',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(
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
        ))
    )]
    public function subscriberFeed(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = (int) ($request->query->get('limit') ?? 50);
        $offset = (int) ($request->query->get('offset') ?? 0);

        $notes = $this->noteService->getSubscriberFeed($user, $limit, $offset);
        $data = array_map(fn(Note $note) => $this->serializeNote($note), $notes);

        return new JsonResponse($data);
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
}

