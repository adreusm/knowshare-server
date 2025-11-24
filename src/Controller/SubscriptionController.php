<?php

namespace App\Controller;

use App\DTO\SubscribeRequest;
use App\Entity\User;
use App\Interface\SubscriptionServiceInterface;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/subscriptions', name: 'api_subscriptions_')]
#[OA\Tag(name: 'Subscriptions', description: 'User subscription management endpoints')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/authors', name: 'subscribed_authors', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/subscriptions/authors',
        summary: 'Get subscribed authors',
        description: 'Returns a paginated list of authors the current user is subscribed to',
        security: [['bearer' => []]],
        tags: ['Subscriptions']
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
        description: 'Paginated list of subscribed authors',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
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
    public function subscribedAuthors(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) ($request->query->get('page') ?? 1));
        $limit = max(1, min(100, (int) ($request->query->get('limit') ?? 20)));

        $result = $this->subscriptionService->getSubscribedAuthorsPaginated($user, $page, $limit);
        $data = array_map(fn(User $author) => [
            'id' => $author->getId(),
            'username' => $author->getUsername(),
            'email' => $author->getEmail(),
        ], $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('/subscribers', name: 'subscribers', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/subscriptions/subscribers',
        summary: 'Get subscribers',
        description: 'Returns a paginated list of users subscribed to the current user',
        security: [['bearer' => []]],
        tags: ['Subscriptions']
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
        description: 'Paginated list of subscribers',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
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
    public function subscribers(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) ($request->query->get('page') ?? 1));
        $limit = max(1, min(100, (int) ($request->query->get('limit') ?? 20)));

        $result = $this->subscriptionService->getSubscribersPaginated($user, $page, $limit);
        $data = array_map(fn(User $subscriber) => [
            'id' => $subscriber->getId(),
            'username' => $subscriber->getUsername(),
            'email' => $subscriber->getEmail(),
        ], $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('', name: 'subscribe', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/subscriptions',
        summary: 'Subscribe to an author',
        description: 'Subscribes the current user to an author',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['author_id'],
                properties: [
                    new OA\Property(property: 'author_id', type: 'integer', example: 1),
                ]
            )
        ),
        tags: ['Subscriptions']
    )]
    #[OA\Response(response: 204, description: 'Successfully subscribed')]
    #[OA\Response(response: 400, description: 'Validation error')]
    #[OA\Response(response: 404, description: 'Author not found')]
    #[OA\Response(response: 409, description: 'Already subscribed or cannot subscribe to yourself')]
    public function subscribe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $subscribeRequest = new SubscribeRequest(
            author_id: $data['author_id'] ?? null
        );

        $errors = $this->validator->validate($subscribeRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $author = $this->userRepository->find($subscribeRequest->author_id);
        if (!$author) {
            return new JsonResponse(['error' => 'Author not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->subscriptionService->subscribe($user, $author);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{authorId}', name: 'unsubscribe', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/subscriptions/{authorId}',
        summary: 'Unsubscribe from an author',
        description: 'Unsubscribes the current user from an author',
        security: [['bearer' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'authorId',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Author ID'
    )]
    #[OA\Response(response: 204, description: 'Successfully unsubscribed')]
    #[OA\Response(response: 404, description: 'Author not found or not subscribed')]
    public function unsubscribe(int $authorId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $author = $this->userRepository->find($authorId);
        if (!$author) {
            return new JsonResponse(['error' => 'Author not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->subscriptionService->unsubscribe($user, $author);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}



