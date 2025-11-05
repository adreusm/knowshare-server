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

    public function getDomainById(User $user, int $id): ?Domain;
}

