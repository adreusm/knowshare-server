<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Interface\RefreshTokenServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenService implements RefreshTokenServiceInterface
{
    private const REFRESH_TOKEN_LIFETIME = '+30 days';

    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createRefreshToken(User $user, string $token, \DateTimeImmutable $expiresAt): RefreshToken
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setToken($token);
        $refreshToken->setUser($user);
        $refreshToken->setExpiresAt($expiresAt);

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }

    public function findValidToken(string $token): ?RefreshToken
    {
        return $this->refreshTokenRepository->findValidToken($token);
    }

    public function revokeToken(RefreshToken $refreshToken): void
    {
        $refreshToken->setIsRevoked(true);
        $this->entityManager->flush();
    }

    public function revokeAllUserTokens(User $user): void
    {
        $this->refreshTokenRepository->revokeUserTokens($user);
        $this->entityManager->flush();
    }

    public function deleteExpiredTokens(): int
    {
        return $this->refreshTokenRepository->deleteExpiredTokens();
    }

    public function getExpirationDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify(self::REFRESH_TOKEN_LIFETIME);
    }
}

