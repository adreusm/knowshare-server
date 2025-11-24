<?php

namespace App\Interface;

use App\DTO\CreateDomainRequest;
use App\DTO\UpdateDomainRequest;
use App\Entity\Domain;
use App\Entity\User;

interface DomainServiceInterface
{
    public function createDomain(User $user, CreateDomainRequest $request): Domain;

    public function updateDomain(Domain $domain, UpdateDomainRequest $request): Domain;

    public function deleteDomain(Domain $domain): void;

    /**
     * @return Domain[]
     */
    public function getUserDomains(User $user): array;

    /**
     * Get paginated user domains
     * @return array{items: Domain[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getUserDomainsPaginated(User $user, int $page = 1, int $limit = 20): array;

    public function getDomainById(User $user, int $id): ?Domain;
}



