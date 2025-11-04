<?php

namespace App\Controller;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Interface\AuthServiceInterface;
use App\Interface\RefreshTokenServiceInterface;
use App\Interface\TokenServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/v1/auth', name: 'api_auth_')]
#[OA\Tag(name: 'Authentication', description: 'User authentication and registration endpoints')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthServiceInterface $authService,
        private TokenServiceInterface $tokenService,
        private RefreshTokenServiceInterface $refreshTokenService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Register a new user',
        description: 'Creates a new user account and returns the user ID',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', minLength: 3, maxLength: 255, example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6, example: 'password123'),
                ]
            )
        ),
        tags: ['Authentication']
    )]
    #[OA\Response(
        response: 201,
        description: 'User successfully registered',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'object', example: ['username' => 'Username cannot be blank']),
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'User already exists',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'User with this email already exists'),
            ]
        )
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $registerRequest = new RegisterRequest(
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null
        );

        $errors = $this->validator->validate($registerRequest);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->authService->register($registerRequest);
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT
            );
        }

        return new JsonResponse([
            'id' => $user->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login user',
        description: 'Authenticates user and returns access token. Refresh token is set in HTTP-only cookie.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'User successfully authenticated',
        headers: [
            new OA\Header(
                header: 'Set-Cookie',
                description: 'HTTP-only cookie containing refresh token',
                schema: new OA\Schema(type: 'string', example: 'refresh_token=abc123...; HttpOnly; Path=/')
            ),
        ],
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'accessToken', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'object', example: ['email' => 'Email cannot be blank']),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid credentials'),
            ]
        )
    )]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $loginRequest = new LoginRequest(
            email: $data['email'] ?? null,
            password: $data['password'] ?? null
        );

        $errors = $this->validator->validate($loginRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->login($loginRequest);

        if (!$user) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->createAuthResponse($user, Response::HTTP_OK);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Refresh access token',
        description: 'Refreshes the access token using refresh token from HTTP-only cookie',
        tags: ['Authentication']
    )]

    #[OA\Response(
        response: 200,
        description: 'Tokens successfully refreshed',
        headers: [
            new OA\Header(
                header: 'Set-Cookie',
                description: 'HTTP-only cookie containing new refresh token',
                schema: new OA\Schema(type: 'string', example: 'refresh_token=abc123...; HttpOnly; Path=/')
            ),
        ],
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'accessToken', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            ]
        )
    )]

    #[OA\Response(
        response: 401,
        description: 'Invalid or expired refresh token, or refresh token not found in cookie',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid or expired refresh token'),
            ]
        )
    )]

    public function refresh(Request $request): JsonResponse
    {
        $refreshTokenValue = $request->cookies->get('refresh_token');

        if (!$refreshTokenValue) {
            return new JsonResponse(
                ['error' => 'Refresh token not found'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $refreshToken = $this->refreshTokenService->findValidToken($refreshTokenValue);

        if (!$refreshToken) {
            return new JsonResponse(
                ['error' => 'Invalid or expired refresh token'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = $refreshToken->getUser();

        $this->refreshTokenService->revokeToken($refreshToken);

        return $this->createAuthResponse($user, Response::HTTP_OK);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get current user information',
        description: 'Returns information about the currently authenticated user',
        security: [['bearer' => []]],
        tags: ['Authentication']
    )]

    #[OA\Response(
        response: 200,
        description: 'User information',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            ]
        )
    )]

    #[OA\Response(
        response: 401,
        description: 'Unauthorized - Invalid or missing token',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Unauthorized'),
            ]
        )
    )]

    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Unauthorized'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ], Response::HTTP_OK);
    }

    private function createAuthResponse(User $user, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $accessToken = $this->tokenService->generateAccessToken($user);
        $refreshTokenValue = $this->tokenService->generateRefreshToken($user);
        
        $expiresAt = $this->refreshTokenService->getExpirationDate();
        $this->refreshTokenService->createRefreshToken($user, $refreshTokenValue, $expiresAt);

        $cookie = Cookie::create('refresh_token', $refreshTokenValue)
            ->withExpires($expiresAt)
            ->withHttpOnly(true)
            ->withSecure($this->getParameter('app.cookie.secure')) // false in development
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withPath('/');

        $response = new JsonResponse([
            'accessToken' => $accessToken,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ], $statusCode);
        
        $response->headers->setCookie($cookie);
        
        return $response;
    }
}


