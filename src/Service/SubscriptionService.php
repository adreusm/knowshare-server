<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Interface\SubscriptionServiceInterface;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function subscribe(User $subscriber, User $author): void
    {
        if ($subscriber->getId() === $author->getId()) {
            throw new \RuntimeException('Cannot subscribe to yourself');
        }

        if ($this->subscriptionRepository->subscriptionExists($subscriber, $author)) {
            throw new \RuntimeException('Already subscribed to this user');
        }

        $subscription = new Subscription();
        $subscription->setSubscriber($subscriber);
        $subscription->setAuthor($author);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    public function unsubscribe(User $subscriber, User $author): void
    {
        $subscription = $this->subscriptionRepository->findSubscription($subscriber, $author);
        if (!$subscription) {
            throw new \RuntimeException('Subscription not found');
        }

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();
    }

    public function isSubscribed(User $subscriber, User $author): bool
    {
        return $this->subscriptionRepository->subscriptionExists($subscriber, $author);
    }

    public function getSubscribedAuthors(User $subscriber): array
    {
        $authorIds = $this->subscriptionRepository->findSubscribedAuthors($subscriber);
        $authors = [];
        foreach ($authorIds as $row) {
            $authorId = is_array($row) ? $row['authorId'] : $row;
            $author = $this->userRepository->find($authorId);
            if ($author) {
                $authors[] = $author;
            }
        }
        return $authors;
    }

    public function getSubscribers(User $author): array
    {
        $subscriberIds = $this->subscriptionRepository->findSubscribers($author);
        $subscribers = [];
        foreach ($subscriberIds as $row) {
            $subscriberId = is_array($row) ? $row['subscriberId'] : $row;
            $subscriber = $this->userRepository->find($subscriberId);
            if ($subscriber) {
                $subscribers[] = $subscriber;
            }
        }
        return $subscribers;
    }
}

