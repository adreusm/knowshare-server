<?php

namespace App\Interface;

use App\Entity\User;

interface SubscriptionServiceInterface
{
    public function subscribe(User $subscriber, User $author): void;

    public function unsubscribe(User $subscriber, User $author): void;

    public function isSubscribed(User $subscriber, User $author): bool;

    /**
     * @return User[]
     */
    public function getSubscribedAuthors(User $subscriber): array;

    /**
     * @return User[]
     */
    public function getSubscribers(User $author): array;
}

