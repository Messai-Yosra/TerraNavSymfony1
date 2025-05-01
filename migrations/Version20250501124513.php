<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501124513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reactions (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, id_post_id INT NOT NULL, date DATETIME NOT NULL, INDEX IDX_38737FB379F37AE5 (id_user_id), INDEX IDX_38737FB39514AA5C (id_post_id), UNIQUE INDEX unique_user_post (id_user_id, id_post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE story (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, media VARCHAR(255) DEFAULT NULL, text LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_EB56043879F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB379F37AE5 FOREIGN KEY (id_user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reactions ADD CONSTRAINT FK_38737FB39514AA5C FOREIGN KEY (id_post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE story ADD CONSTRAINT FK_EB56043879F37AE5 FOREIGN KEY (id_user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F76B3CA4B');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F7D1AA708F');
        $this->addSql('DROP TABLE reaction');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reaction (id INT AUTO_INCREMENT NOT NULL, id_post INT DEFAULT NULL, id_user INT DEFAULT NULL, date DATETIME DEFAULT NULL, INDEX IDX_A4D707F7D1AA708F (id_post), INDEX IDX_A4D707F76B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F76B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F7D1AA708F FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB379F37AE5');
        $this->addSql('ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB39514AA5C');
        $this->addSql('ALTER TABLE story DROP FOREIGN KEY FK_EB56043879F37AE5');
        $this->addSql('DROP TABLE reactions');
        $this->addSql('DROP TABLE story');
    }
}
