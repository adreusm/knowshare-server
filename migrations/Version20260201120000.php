<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactor note_tags: remove created_at column (pivot table for ManyToMany Note-Tag).
 * Doctrine now manages the many-to-many relation; no separate NoteTag entity.
 */
final class Version20260201120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop created_at from note_tags (ManyToMany pivot table)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note_tags DROP COLUMN created_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note_tags ADD COLUMN created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()');
    }
}
