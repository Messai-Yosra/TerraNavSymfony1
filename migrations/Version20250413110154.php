<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413110154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE dateDebut dateDebut DATETIME NOT NULL, CHANGE dateFin dateFin DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE dateDebut dateDebut DATETIME DEFAULT NULL, CHANGE dateFin dateFin DATETIME DEFAULT NULL');
    }
}
