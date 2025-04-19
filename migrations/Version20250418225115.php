<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418225115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trajet CHANGE disponibilite disponibilite TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD banned DATETIME DEFAULT NULL, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trajet CHANGE disponibilite disponibilite INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP banned, CHANGE nom nom VARCHAR(40) DEFAULT NULL, CHANGE prenom prenom VARCHAR(50) DEFAULT NULL');
    }
}
