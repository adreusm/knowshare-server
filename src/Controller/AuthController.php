<?php

namespace App\Controller;

use App\DTO\AuthResponse;
use App\DTO\LoginRequest;
use App\DTO\RefreshTokenRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Interface\AuthServiceInterface;
use App\Interface\RefreshTokenServiceInterface;
use App\Interface\TokenServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthServiceInterface $authService,
        private TokenServiceInterface $tokenService,
        private RefreshTokenServiceInterface $refreshTokenService,
        private SerializerInterface $serializer,
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

        // Generate tokens
        $accessToken = $this->tokenService->generateAccessToken($user);
        $refreshTokenValue = $this->tokenService->generateRefreshToken($user);
        
        // Store refresh token
        $expiresAt = $this->refreshTokenService->getExpirationDate();
        $this->refreshTokenService->createRefreshToken($user, $refreshTokenValue, $expiresAt);

        return new JsonResponse([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshTokenValue,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
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

        // Generate tokens
        $accessToken = $this->tokenService->generateAccessToken($user);
        $refreshTokenValue = $this->tokenService->generateRefreshToken($user);
        
        // Store refresh token
        $expiresAt = $this->refreshTokenService->getExpirationDate();
        $this->refreshTokenService->createRefreshToken($user, $refreshTokenValue, $expiresAt);

        return new JsonResponse([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshTokenValue,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ], Response::HTTP_OK);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $refreshTokenRequest = new RefreshTokenRequest(
            refreshToken: $data['refreshToken'] ?? null
        );

        $errors = $this->validator->validate($refreshTokenRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->refreshTokenService->findValidToken($refreshTokenRequest->refreshToken);

        if (!$refreshToken) {
            return new JsonResponse(
                ['error' => 'Invalid or expired refresh token'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = $refreshToken->getUser();

        // Revoke old refresh token
        $this->refreshTokenService->revokeToken($refreshToken);

        // Generate new tokens
        $accessToken = $this->tokenService->generateAccessToken($user);
        $newRefreshTokenValue = $this->tokenService->generateRefreshToken($user);
        
        // Store new refresh token
        $expiresAt = $this->refreshTokenService->getExpirationDate();
        $this->refreshTokenService->createRefreshToken($user, $newRefreshTokenValue, $expiresAt);

        return new JsonResponse([
            'accessToken' => $accessToken,
            'refreshToken' => $newRefreshTokenValue,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ], Response::HTTP_OK);
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
}

