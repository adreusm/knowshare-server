<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use App\Helper\FilterHelper;
use App\Helper\SortHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 */
class DomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domain::class);
    }

    /**
     * Find all domains for a specific user
     * @return Domain[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get query for paginated user domains
     */
    public function findByUserQuery(User $user, array $filters = [], ?string $sort = null): Query
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user);

        // Apply filters
        $allowedFilters = [
            'is_public' => 'd.is_public',
            'search' => 'd.name',
        ];

        // Handle search (search in name and description)
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('d.name', ':search'),
                $qb->expr()->like('d.description', ':search')
            ))
            ->setParameter('search', '%' . $searchTerm . '%');
            unset($filters['search']);
        }

        FilterHelper::applyFilters($qb, $filters, $allowedFilters);

        // Apply sorting
        $allowedSorts = [
            'created_at' => 'd.created_at',
            'updated_at' => 'd.updated_at',
            'name' => 'd.name',
        ];
        SortHelper::applySort($qb, $sort, $allowedSorts);

        return $qb->getQuery();
    }

    /**
     * Find a domain by ID and user
     */
    public function findOneByIdAndUser(int $id, User $user): ?Domain
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :id')
            ->andWhere('d.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}



