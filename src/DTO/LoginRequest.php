<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be blank')]
        #[Assert\Email(message: 'Email is not valid')]
        public ?string $email = null,

        #[Assert\NotBlank(message: 'Password cannot be blank')]
        public ?string $password = null
    ) {
    }
}

