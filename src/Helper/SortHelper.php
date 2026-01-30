<?php

namespace App\Helper;

use Doctrine\ORM\QueryBuilder;

class SortHelper
{
    /**
     * Apply sorting to query builder
     * @param QueryBuilder $qb
     * @param string|null $sortField Field to sort by (e.g., "created_at", "-title")
     * @param array<string, string> $allowedFields Mapping of sort keys to entity fields
     * @param string $defaultSort Default sort field
     * @param string $defaultDirection Default sort direction (ASC or DESC)
     */
    public static function applySort(
        QueryBuilder $qb,
        ?string $sortField,
        array $allowedFields,
        string $defaultSort = 'created_at',
        string $defaultDirection = 'DESC'
    ): void {
        // Parse sort field (format: "field" or "-field" for descending)
        $direction = 'ASC';
        $field = $sortField;

        if ($sortField && str_starts_with($sortField, '-')) {
            $direction = 'DESC';
            $field = substr($sortField, 1);
        }

        // Validate field and resolve to DQL field (with entity alias)
        if (!$field || !isset($allowedFields[$field])) {
            $field = $defaultSort;
            $direction = $defaultDirection;
        }
        $dqlField = $allowedFields[$field] ?? $field;

        $qb->orderBy($dqlField, $direction);
    }
}

