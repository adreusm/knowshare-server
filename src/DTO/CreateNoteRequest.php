<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateNoteRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Domain ID cannot be blank')]
        #[Assert\Type(type: 'integer', message: 'Domain ID must be an integer')]
        public ?int $domain_id = null,

        #[Assert\NotBlank(message: 'Title cannot be blank')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Title must be at least {{ limit }} characters', maxMessage: 'Title cannot exceed {{ limit }} characters')]
        public ?string $title = null,

        #[Assert\NotBlank(message: 'Content cannot be blank')]
        public ?string $content = null,

        #[Assert\Choice(choices: ['public', 'subscribers', 'private'], message: 'Access type must be one of: public, subscribers, private')]
        public ?string $access_type = 'public',

        #[Assert\Type(type: 'array', message: 'Tag IDs must be an array')]
        #[Assert\All([
            new Assert\Type(type: 'integer', message: 'Each tag ID must be an integer')
        ])]
        public ?array $tag_ids = null
    ) {
    }
}
