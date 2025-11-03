<?php

namespace App\DTO;

class AuthResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $id,
        public string $username,
        public string $email
    ) {
    }
}

