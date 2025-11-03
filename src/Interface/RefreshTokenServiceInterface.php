<?php

namespace App\Interface;

use App\Entity\RefreshToken;
use App\Entity\User;

interface RefreshTokenServiceInterface
{
    public function createRefreshToken(User $user, string $token, \DateTimeImmutable $expiresAt): RefreshToken;

    public function findValidToken(string $token): ?RefreshToken;

    public function revokeToken(RefreshToken $refreshToken): void;

    public function revokeAllUserTokens(User $user): void;

    public function deleteExpiredTokens(): int;

    public function getExpirationDate(): \DateTimeImmutable;
}

