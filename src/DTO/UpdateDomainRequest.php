<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateDomainRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 100, minMessage: 'Name must be at least {{ limit }} characters', maxMessage: 'Name cannot exceed {{ limit }} characters')]
        public ?string $name = null,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed {{ limit }} characters')]
        public ?string $description = null,

        #[Assert\Type(type: 'bool', message: 'is_public must be a boolean')]
        public ?bool $is_public = null
    ) {
    }
}

