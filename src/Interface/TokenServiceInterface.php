<?php

namespace App\Interface;

use App\Entity\User;

interface TokenServiceInterface
{
    public function generateAccessToken(User $user): string;

    public function generateRefreshToken(User $user): string;
}

