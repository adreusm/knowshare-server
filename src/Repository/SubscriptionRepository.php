<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Check if subscription exists
     */
    public function subscriptionExists(User $subscriber, User $author): bool
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.subscriber)')
            ->andWhere('s.subscriber = :subscriber')
            ->andWhere('s.author = :author')
            ->setParameter('subscriber', $subscriber)
            ->setParameter('author', $author)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Find subscription
     */
    public function findSubscription(User $subscriber, User $author): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.subscriber = :subscriber')
            ->andWhere('s.author = :author')
            ->setParameter('subscriber', $subscriber)
            ->setParameter('author', $author)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all authors a user is subscribed to
     * @return User[]
     */
    public function findSubscribedAuthors(User $subscriber): array
    {
        return $this->createQueryBuilder('s')
            ->select('IDENTITY(s.author) as authorId')
            ->andWhere('s.subscriber = :subscriber')
            ->setParameter('subscriber', $subscriber)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all subscribers of an author
     * @return User[]
     */
    public function findSubscribers(User $author): array
    {
        return $this->createQueryBuilder('s')
            ->select('IDENTITY(s.subscriber) as subscriberId')
            ->andWhere('s.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getResult();
    }
}



