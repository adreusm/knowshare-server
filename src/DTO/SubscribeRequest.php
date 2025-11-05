<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SubscribeRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Author ID cannot be blank')]
        #[Assert\Type(type: 'integer', message: 'Author ID must be an integer')]
        public ?int $author_id = null
    ) {
    }
}

