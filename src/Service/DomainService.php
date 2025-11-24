<?php

namespace App\Service;

use App\DTO\CreateDomainRequest;
use App\DTO\UpdateDomainRequest;
use App\Entity\Domain;
use App\Entity\User;
use App\Helper\PaginationHelper;
use App\Interface\DomainServiceInterface;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;

class DomainService implements DomainServiceInterface
{
    public function __construct(
        private DomainRepository $domainRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createDomain(User $user, CreateDomainRequest $request): Domain
    {
        $domain = new Domain();
        $domain->setUser($user);
        $domain->setName($request->name);
        $domain->setDescription($request->description);
        $domain->setIsPublic($request->is_public ?? true);

        $this->entityManager->persist($domain);
        $this->entityManager->flush();

        return $domain;
    }

    public function updateDomain(Domain $domain, UpdateDomainRequest $request): Domain
    {
        if ($request->name !== null) {
            $domain->setName($request->name);
        }
        if ($request->description !== null) {
            $domain->setDescription($request->description);
        }
        if ($request->is_public !== null) {
            $domain->setIsPublic($request->is_public);
        }

        $this->entityManager->flush();

        return $domain;
    }

    public function deleteDomain(Domain $domain): void
    {
        $this->entityManager->remove($domain);
        $this->entityManager->flush();
    }

    public function getUserDomains(User $user): array
    {
        return $this->domainRepository->findByUser($user);
    }

    public function getUserDomainsPaginated(User $user, int $page = 1, int $limit = 20): array
    {
        $query = $this->domainRepository->findByUserQuery($user);
        return PaginationHelper::paginate($query, $page, $limit);
    }

    public function getDomainById(User $user, int $id): ?Domain
    {
        return $this->domainRepository->findOneByIdAndUser($id, $user);
    }
}



