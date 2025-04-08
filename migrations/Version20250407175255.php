<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407175255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F6B3CA4B');
        $this->addSql('ALTER TABLE offre CHANGE reduction reduction DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F6B3CA4B');
        $this->addSql('ALTER TABLE offre CHANGE reduction reduction NUMERIC(5, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE CASCADE');
    }
}
