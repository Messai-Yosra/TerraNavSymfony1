<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250417193912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE reduction reduction DOUBLE PRECISION NOT NULL, CHANGE dateDebut dateDebut DATETIME NOT NULL, CHANGE dateFin dateFin DATETIME NOT NULL');
        $this->addSql('ALTER TABLE trajet CHANGE disponibilite disponibilite TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD banned DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE voyage CHANGE pointDepart pointDepart VARCHAR(50) NOT NULL, CHANGE dateDepart dateDepart DATETIME NOT NULL, CHANGE dateRetour dateRetour DATETIME NOT NULL, CHANGE destination destination VARCHAR(50) NOT NULL, CHANGE nbPlacesD nbPlacesD INT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE titre titre VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE reduction reduction DOUBLE PRECISION DEFAULT NULL, CHANGE dateDebut dateDebut DATETIME DEFAULT NULL, CHANGE dateFin dateFin DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE trajet CHANGE disponibilite disponibilite INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP banned');
        $this->addSql('ALTER TABLE voyage CHANGE pointDepart pointDepart VARCHAR(50) DEFAULT NULL, CHANGE dateDepart dateDepart DATETIME DEFAULT NULL, CHANGE dateRetour dateRetour DATETIME DEFAULT NULL, CHANGE destination destination VARCHAR(50) DEFAULT NULL, CHANGE nbPlacesD nbPlacesD INT DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE titre titre VARCHAR(50) DEFAULT NULL');
    }
}
