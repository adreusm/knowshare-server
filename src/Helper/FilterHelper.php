<?php

namespace App\Helper;

use Doctrine\ORM\QueryBuilder;

class FilterHelper
{
    /**
     * Apply filters to query builder
     * @param QueryBuilder $qb
     * @param array<string, mixed> $filters
     * @param array<string, string> $allowedFields Mapping of filter keys to entity fields
     */
    public static function applyFilters(QueryBuilder $qb, array $filters, array $allowedFields): void
    {
        foreach ($filters as $key => $value) {
            if (!isset($allowedFields[$key]) || $value === null || $value === '') {
                continue;
            }

            $field = $allowedFields[$key];
            
            // Handle array values (IN clause)
            if (is_array($value)) {
                if (!empty($value)) {
                    $qb->andWhere($qb->expr()->in($field, ':filter_' . $key))
                       ->setParameter('filter_' . $key, $value);
                }
                continue;
            }

            // Handle range filters (from, to)
            if ($key === 'from_date' || $key === 'from') {
                $qb->andWhere($qb->expr()->gte($field, ':filter_' . $key))
                   ->setParameter('filter_' . $key, $value);
                continue;
            }

            if ($key === 'to_date' || $key === 'to') {
                $qb->andWhere($qb->expr()->lte($field, ':filter_' . $key))
                   ->setParameter('filter_' . $key, $value);
                continue;
            }

            // Handle LIKE search
            if (str_contains($key, 'search') || str_contains($key, 'query')) {
                $qb->andWhere($qb->expr()->like($field, ':filter_' . $key))
                   ->setParameter('filter_' . $key, '%' . $value . '%');
                continue;
            }

            // Default: exact match
            $qb->andWhere($qb->expr()->eq($field, ':filter_' . $key))
               ->setParameter('filter_' . $key, $value);
        }
    }
}

