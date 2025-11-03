<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Username cannot be blank')]
        #[Assert\Length(min: 3, max: 255, minMessage: 'Username must be at least {{ limit }} characters', maxMessage: 'Username cannot exceed {{ limit }} characters')]
        public ?string $username = null,

        #[Assert\NotBlank(message: 'Email cannot be blank')]
        #[Assert\Email(message: 'Email is not valid')]
        public ?string $email = null,

        #[Assert\NotBlank(message: 'Password cannot be blank')]
        #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters')]
        public ?string $password = null
    ) {
    }
}

