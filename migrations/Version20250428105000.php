<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250428105000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Story table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS story (
            id INT AUTO_INCREMENT NOT NULL,
            id_user_id INT NOT NULL,
            media VARCHAR(255) DEFAULT NULL,
            text LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_STORY_USER FOREIGN KEY (id_user_id) REFERENCES utilisateur (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS story');
    }
}