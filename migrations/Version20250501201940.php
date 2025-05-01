<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501201940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chambre_stats (id INT AUTO_INCREMENT NOT NULL, chambre_id INT NOT NULL, date DATE NOT NULL, occupancy_rate DOUBLE PRECISION NOT NULL, average_price DOUBLE PRECISION NOT NULL, revenue DOUBLE PRECISION NOT NULL, satisfaction_score DOUBLE PRECISION NOT NULL, INDEX IDX_E835AC49B177F54 (chambre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chambre_stats ADD CONSTRAINT FK_E835AC49B177F54 FOREIGN KEY (chambre_id) REFERENCES chambre (id)');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chambre_stats DROP FOREIGN KEY FK_E835AC49B177F54');
        $this->addSql('DROP TABLE chambre_stats');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE CASCADE');
    }
}
