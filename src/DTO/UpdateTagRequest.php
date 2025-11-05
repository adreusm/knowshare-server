<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateTagRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 50, minMessage: 'Name must be at least {{ limit }} characters', maxMessage: 'Name cannot exceed {{ limit }} characters')]
        public ?string $name = null
    ) {
    }
}

