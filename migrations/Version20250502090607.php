<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250502090607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB39514AA5C');
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB379F37AE5');
        $this->addSql('ALTER TABLE reactions DROP type, CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB39514AA5C FOREIGN KEY (id_post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB379F37AE5 FOREIGN KEY (id_user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE utilisateur ADD has_facial_auth TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB379F37AE5');
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB39514AA5C');
        $this->addSql('ALTER TABLE reactions ADD type VARCHAR(20) NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB379F37AE5 FOREIGN KEY (id_user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB39514AA5C FOREIGN KEY (id_post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur DROP has_facial_auth');
    }
}
