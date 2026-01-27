<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use App\Helper\FilterHelper;
use App\Helper\SortHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Find all notes for a specific user
     * @return Note[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get query for paginated user notes
     */
    public function findByUserQuery(User $user, array $filters = [], ?string $sort = null): Query
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user);

        // Handle tag filter separately (requires join)
        $tagFilter = null;
        if (isset($filters['tag_id'])) {
            $tagFilter = $filters['tag_id'];
            $qb->innerJoin('n.noteTags', 'nt')
               ->innerJoin('nt.tag', 't');
            unset($filters['tag_id']);
        }

        // Apply filters
        $allowedFilters = [
            'domain_id' => 'n.domain',
            'access_type' => 'n.access_type',
        ];

        FilterHelper::applyFilters($qb, $filters, $allowedFilters);

        // Apply tag filter after join
        if ($tagFilter !== null) {
            $qb->andWhere('t.id = :tag_id')
               ->setParameter('tag_id', $tagFilter);
        }

        // Apply sorting
        $allowedSorts = [
            'created_at' => 'n.created_at',
            'updated_at' => 'n.updated_at',
            'title' => 'n.title',
        ];
        SortHelper::applySort($qb, $sort, $allowedSorts);

        return $qb->getQuery();
    }

    /**
     * Find a note by ID and user
     */
    public function findOneByIdAndUser(int $id, User $user): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.id = :id')
            ->andWhere('n.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find public notes for feed
     * @return Note[]
     */
    public function findPublicNotes(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('accessType', 'public')
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get query for paginated public notes
     */
    public function findPublicNotesQuery(array $filters = [], ?string $sort = null): Query
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('accessType', 'public');

        // Handle tag filter separately (requires join)
        $tagFilter = null;
        if (isset($filters['tag_id'])) {
            $tagFilter = $filters['tag_id'];
            $qb->innerJoin('n.noteTags', 'nt')
               ->innerJoin('nt.tag', 't');
            unset($filters['tag_id']);
        }

        // Handle search (search in title and content)
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('n.title', ':search'),
                $qb->expr()->like('n.content', ':search')
            ))
            ->setParameter('search', '%' . $searchTerm . '%');
            unset($filters['search']);
        }

        // Apply filters
        $allowedFilters = [
            'domain_id' => 'n.domain',
            'author_id' => 'n.user',
        ];

        FilterHelper::applyFilters($qb, $filters, $allowedFilters);

        // Apply tag filter after join
        if ($tagFilter !== null) {
            $qb->andWhere('t.id = :tag_id')
               ->setParameter('tag_id', $tagFilter);
        }

        // Apply sorting
        $allowedSorts = [
            'created_at' => 'n.created_at',
            'updated_at' => 'n.updated_at',
            'title' => 'n.title',
        ];
        SortHelper::applySort($qb, $sort, $allowedSorts);

        return $qb->getQuery();
    }

    /**
     * Find subscriber-only notes for a specific subscriber
     * @return Note[]
     */
    public function findSubscriberNotes(User $subscriber, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('App\Entity\Subscription', 's', 'WITH', 's.author = n.user')
            ->andWhere('s.subscriber = :subscriber')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('subscriber', $subscriber)
            ->setParameter('accessType', 'subscribers')
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get query for paginated subscriber notes
     */
    public function findSubscriberNotesQuery(User $subscriber, array $filters = [], ?string $sort = null): Query
    {
        $qb = $this->createQueryBuilder('n')
            ->innerJoin('App\Entity\Subscription', 's', 'WITH', 's.author = n.user')
            ->andWhere('s.subscriber = :subscriber')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('subscriber', $subscriber)
            ->setParameter('accessType', 'subscribers');

        // Handle tag filter separately (requires join)
        $tagFilter = null;
        if (isset($filters['tag_id'])) {
            $tagFilter = $filters['tag_id'];
            $qb->innerJoin('n.noteTags', 'nt')
               ->innerJoin('nt.tag', 't');
            unset($filters['tag_id']);
        }

        // Apply filters
        $allowedFilters = [
            'domain_id' => 'n.domain',
            'author_id' => 'n.user',
        ];

        FilterHelper::applyFilters($qb, $filters, $allowedFilters);

        // Apply tag filter after join
        if ($tagFilter !== null) {
            $qb->andWhere('t.id = :tag_id')
               ->setParameter('tag_id', $tagFilter);
        }

        // Apply sorting
        $allowedSorts = [
            'created_at' => 'n.created_at',
            'updated_at' => 'n.updated_at',
            'title' => 'n.title',
        ];
        SortHelper::applySort($qb, $sort, $allowedSorts);

        return $qb->getQuery();
    }

    /**
     * Find notes by domain
     * @return Note[]
     */
    public function findByDomain(int $domainId, User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.domain = :domainId')
            ->andWhere('n.user = :user')
            ->setParameter('domainId', $domainId)
            ->setParameter('user', $user)
            ->orderBy('n.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
