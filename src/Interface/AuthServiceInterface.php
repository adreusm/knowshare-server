<?php

namespace App\Interface;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;

interface AuthServiceInterface
{
    public function register(RegisterRequest $registerRequest): User;

    public function login(LoginRequest $loginRequest): ?User;

    public function validateCredentials(User $user, string $password): bool;
}

