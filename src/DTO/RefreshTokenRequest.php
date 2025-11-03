<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Refresh token cannot be blank')]
        public ?string $refreshToken = null
    ) {
    }
}

