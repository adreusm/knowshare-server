<?php

namespace App\Repository;

use App\Entity\NoteTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NoteTag>
 */
class NoteTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteTag::class);
    }

    /**
     * Remove all tags from a note
     */
    public function removeTagsFromNote(int $noteId): void
    {
        $this->createQueryBuilder('nt')
            ->delete()
            ->andWhere('nt.note = :noteId')
            ->setParameter('noteId', $noteId)
            ->getQuery()
            ->execute();
    }
}
