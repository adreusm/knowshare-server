<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260130120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles column to users table (ROLE_USER, ROLE_ADMIN)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" ADD COLUMN roles JSONB NOT NULL DEFAULT \'["ROLE_USER"]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" DROP COLUMN roles');
    }
}
