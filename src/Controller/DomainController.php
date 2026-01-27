<?php

namespace App\Controller;

use App\DTO\CreateDomainRequest;
use App\DTO\UpdateDomainRequest;
use App\Entity\Domain;
use App\Entity\User;
use App\Interface\DomainServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/domains', name: 'api_domains_')]
#[OA\Tag(name: 'Domains', description: 'Domain (subject area) management endpoints')]
class DomainController extends AbstractController
{
    public function __construct(
        private DomainServiceInterface $domainService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/domains',
        summary: 'Get all domains for current user',
        description: 'Returns a paginated list of domains (subject areas) created by the authenticated user',
        security: [['bearer' => []]],
        tags: ['Domains']
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
        description: 'Paginated list of domains',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'English Language'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Learning English grammar and vocabulary'),
                            new OA\Property(property: 'is_public', type: 'boolean', example: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
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
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])
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

        $filters = $this->extractFilters($request, ['is_public', 'search']);

        $result = $this->domainService->getUserDomainsPaginated($user, $page, $limit, $filters, $sort);
        $data = array_map(fn(Domain $domain) => [
            'id' => $domain->getId(),
            'name' => $domain->getName(),
            'description' => $domain->getDescription(),
            'is_public' => $domain->isPublic(),
            'created_at' => $domain->getCreatedAt()?->format('c'),
            'updated_at' => $domain->getUpdatedAt()?->format('c'),
        ], $result['items']);

        return new JsonResponse([
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/domains',
        summary: 'Create a new domain',
        description: 'Creates a new domain (subject area) for the authenticated user',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100, example: 'English Language'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Learning English grammar and vocabulary'),
                    new OA\Property(property: 'is_public', type: 'boolean', example: true),
                ]
            )
        ),
        tags: ['Domains']
    )]
    #[OA\Response(
        response: 201,
        description: 'Domain created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'English Language'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'is_public', type: 'boolean', example: true),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
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

        $createRequest = new CreateDomainRequest(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            is_public: $data['is_public'] ?? true
        );

        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $domain = $this->domainService->createDomain($user, $createRequest);

        return new JsonResponse([
            'id' => $domain->getId(),
            'name' => $domain->getName(),
            'description' => $domain->getDescription(),
            'is_public' => $domain->isPublic(),
            'created_at' => $domain->getCreatedAt()?->format('c'),
            'updated_at' => $domain->getUpdatedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/domains/{id}',
        summary: 'Get a domain by ID',
        description: 'Returns a specific domain by ID',
        security: [['bearer' => []]],
        tags: ['Domains']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Domain ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Domain details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'is_public', type: 'boolean'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Domain not found')]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $domain = $this->domainService->getDomainById($user, $id);
        if (!$domain) {
            return new JsonResponse(['error' => 'Domain not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $domain->getId(),
            'name' => $domain->getName(),
            'description' => $domain->getDescription(),
            'is_public' => $domain->isPublic(),
            'created_at' => $domain->getCreatedAt()?->format('c'),
            'updated_at' => $domain->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/domains/{id}',
        summary: 'Update a domain',
        description: 'Updates an existing domain',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'is_public', type: 'boolean'),
                ]
            )
        ),
        tags: ['Domains']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Domain ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'Domain updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'is_public', type: 'boolean'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Domain not found')]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $domain = $this->domainService->getDomainById($user, $id);
        if (!$domain) {
            return new JsonResponse(['error' => 'Domain not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $updateRequest = new UpdateDomainRequest(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            is_public: $data['is_public'] ?? null
        );

        $errors = $this->validator->validate($updateRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $domain = $this->domainService->updateDomain($domain, $updateRequest);

        return new JsonResponse([
            'id' => $domain->getId(),
            'name' => $domain->getName(),
            'description' => $domain->getDescription(),
            'is_public' => $domain->isPublic(),
            'created_at' => $domain->getCreatedAt()?->format('c'),
            'updated_at' => $domain->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/domains/{id}',
        summary: 'Delete a domain',
        description: 'Deletes a domain and all associated notes',
        security: [['bearer' => []]],
        tags: ['Domains']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Domain ID'
    )]
    #[OA\Response(response: 204, description: 'Domain deleted successfully')]
    #[OA\Response(response: 404, description: 'Domain not found')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $domain = $this->domainService->getDomainById($user, $id);
        if (!$domain) {
            return new JsonResponse(['error' => 'Domain not found'], Response::HTTP_NOT_FOUND);
        }

        $this->domainService->deleteDomain($domain);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
                if ($filterKey === 'is_public') {
                    $filters[$filterKey] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } else {
                    $filters[$filterKey] = $value;
                }
            }
        }
        return $filters;
    }
}

