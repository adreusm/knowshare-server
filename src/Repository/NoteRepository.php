<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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
    public function findByUserQuery(User $user): Query
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.created_at', 'DESC')
            ->getQuery();
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
    public function findPublicNotesQuery(): Query
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('accessType', 'public')
            ->orderBy('n.created_at', 'DESC')
            ->getQuery();
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
    public function findSubscriberNotesQuery(User $subscriber): Query
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('App\Entity\Subscription', 's', 'WITH', 's.author = n.user')
            ->andWhere('s.subscriber = :subscriber')
            ->andWhere('n.access_type = :accessType')
            ->setParameter('subscriber', $subscriber)
            ->setParameter('accessType', 'subscribers')
            ->orderBy('n.created_at', 'DESC')
            ->getQuery();
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
