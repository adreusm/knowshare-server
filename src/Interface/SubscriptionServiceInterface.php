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
     * Get paginated subscribed authors
     * @param array<string, mixed> $filters
     * @return array{items: User[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getSubscribedAuthorsPaginated(User $subscriber, int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array;

    /**
     * @return User[]
     */
    public function getSubscribers(User $author): array;

    /**
     * Get paginated subscribers
     * @param array<string, mixed> $filters
     * @return array{items: User[], pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public function getSubscribersPaginated(User $author, int $page = 1, int $limit = 20, array $filters = [], ?string $sort = null): array;
}



