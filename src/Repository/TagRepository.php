<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\User;
use App\Helper\FilterHelper;
use App\Helper\SortHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Find all tags for a specific user
     * @return Tag[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get query for paginated user tags
     */
    public function findByUserQuery(User $user, array $filters = [], ?string $sort = null): Query
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user);

        // Apply filters
        $allowedFilters = [
            'search' => 't.name',
        ];

        FilterHelper::applyFilters($qb, $filters, $allowedFilters);

        // Apply sorting
        $allowedSorts = [
            'created_at' => 't.created_at',
            'name' => 't.name',
        ];
        SortHelper::applySort($qb, $sort, $allowedSorts);

        return $qb->getQuery();
    }

    /**
     * Find a tag by name and user
     */
    public function findOneByNameAndUser(string $name, User $user): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name = :name')
            ->andWhere('t.user = :user')
            ->setParameter('name', $name)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a tag by ID and user
     */
    public function findOneByIdAndUser(int $id, User $user): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->andWhere('t.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}



