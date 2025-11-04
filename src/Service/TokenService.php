<?php

namespace App\Service;

use App\Entity\User;
use App\Interface\TokenServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenService implements TokenServiceInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function generateAccessToken(User $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function generateRefreshToken(User $user): string
    {
        return bin2hex(random_bytes(32));
    }
}

