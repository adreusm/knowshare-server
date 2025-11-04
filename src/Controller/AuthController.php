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

#[Route('/api/auth', name: 'api_auth_')]
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
            ->withSecure(false) // Set to true in production with HTTPS
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


