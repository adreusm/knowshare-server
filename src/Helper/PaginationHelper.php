<?php

namespace App\Helper;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationHelper
{
    /**
     * Paginate a query
     * @return array{items: array, pagination: array{page: int, limit: int, total: int, total_pages: int}}
     */
    public static function paginate(Query $query, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit)); // Limit between 1 and 100

        // Set pagination parameters on the query
        $query
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Create paginator - it will use the query with pagination parameters
        // The second parameter (true) enables fetch-join collection optimization
        $paginator = new Paginator($query, true);
        
        // Get total count (this executes a COUNT query)
        $total = count($paginator);
        
        // Get paginated results
        $items = iterator_to_array($paginator);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }
}

