<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105173100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create domains, notes, tags, note_tags, and subscriptions tables';
    }

    public function up(Schema $schema): void
    {
        // Create domains table
        $this->addSql('CREATE TABLE domains (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            is_public BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )');

        // Create notes table
        $this->addSql('CREATE TABLE notes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            domain_id INTEGER NOT NULL REFERENCES domains(id) ON DELETE CASCADE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            access_type VARCHAR(20) NOT NULL DEFAULT \'public\',
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )');

        // Create tags table
        $this->addSql('CREATE TABLE tags (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            UNIQUE(user_id, name)
        )');

        // Create note_tags table (many-to-many)
        $this->addSql('CREATE TABLE note_tags (
            note_id INTEGER NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
            tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            PRIMARY KEY (note_id, tag_id)
        )');

        // Create subscriptions table
        $this->addSql('CREATE TABLE subscriptions (
            subscriber_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            author_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            PRIMARY KEY (subscriber_id, author_id)
        )');

        // Add indexes for better performance
        $this->addSql('CREATE INDEX idx_domains_user_id ON domains(user_id)');
        $this->addSql('CREATE INDEX idx_notes_user_id ON notes(user_id)');
        $this->addSql('CREATE INDEX idx_notes_domain_id ON notes(domain_id)');
        $this->addSql('CREATE INDEX idx_tags_user_id ON tags(user_id)');
        $this->addSql('CREATE INDEX idx_note_tags_note_id ON note_tags(note_id)');
        $this->addSql('CREATE INDEX idx_note_tags_tag_id ON note_tags(tag_id)');
        $this->addSql('CREATE INDEX idx_subscriptions_subscriber_id ON subscriptions(subscriber_id)');
        $this->addSql('CREATE INDEX idx_subscriptions_author_id ON subscriptions(author_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order (due to foreign key constraints)
        $this->addSql('DROP TABLE IF EXISTS subscriptions');
        $this->addSql('DROP TABLE IF EXISTS note_tags');
        $this->addSql('DROP TABLE IF EXISTS tags');
        $this->addSql('DROP TABLE IF EXISTS notes');
        $this->addSql('DROP TABLE IF EXISTS domains');
    }
}
