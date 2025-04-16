<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416134018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chambre (id INT AUTO_INCREMENT NOT NULL, id_hebergement INT DEFAULT NULL, numero VARCHAR(50) NOT NULL, disponibilite TINYINT(1) DEFAULT NULL, prix DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, capacite INT DEFAULT NULL, equipements LONGTEXT DEFAULT NULL, vue VARCHAR(255) DEFAULT NULL, taille DOUBLE PRECISION DEFAULT NULL, url_3d VARCHAR(2000) DEFAULT NULL, INDEX IDX_C509E4FF5040106B (id_hebergement), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, id_post INT DEFAULT NULL, id_user INT DEFAULT NULL, date DATETIME DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, INDEX IDX_67F068BCD1AA708F (id_post), INDEX IDX_67F068BC6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hebergement (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, nom VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, pays VARCHAR(100) NOT NULL, note_moyenne DOUBLE PRECISION NOT NULL, services LONGTEXT NOT NULL, politique_annulation LONGTEXT NOT NULL, contact VARCHAR(255) NOT NULL, type_hebergement VARCHAR(50) NOT NULL, nb_chambres INT NOT NULL, INDEX IDX_4852DD9C6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, id_chambre INT DEFAULT NULL, url_image VARCHAR(255) NOT NULL, INDEX IDX_C53D045F1B944D8F (id_chambre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, titre VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, reduction DOUBLE PRECISION DEFAULT NULL, dateDebut DATETIME DEFAULT NULL, dateFin DATETIME DEFAULT NULL, imagePath VARCHAR(255) DEFAULT NULL, INDEX IDX_AF86866F6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, prix_total DOUBLE PRECISION DEFAULT NULL, date_validation DATETIME DEFAULT NULL, INDEX IDX_24CC0DF26B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, statut LONGTEXT DEFAULT \'non traitée\' NOT NULL, date DATETIME DEFAULT NULL, image LONGTEXT DEFAULT NULL, nbCommentaires INT DEFAULT NULL, nbReactions INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_5A8A6C8D6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reaction (id INT AUTO_INCREMENT NOT NULL, id_post INT DEFAULT NULL, id_user INT DEFAULT NULL, date DATETIME DEFAULT NULL, INDEX IDX_A4D707F7D1AA708F (id_post), INDEX IDX_A4D707F76B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, dateReclamation DATETIME DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, sujet VARCHAR(255) DEFAULT NULL, etat VARCHAR(50) DEFAULT NULL, INDEX IDX_CE6064046B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, id_panier INT DEFAULT NULL, id_voyage INT DEFAULT NULL, type_service LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) DEFAULT NULL, date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP, Etat VARCHAR(10) NOT NULL, nb_places INT DEFAULT NULL, nbJoursHebergement INT DEFAULT NULL, dateAffectation DATETIME DEFAULT CURRENT_TIMESTAMP, id_Transport INT DEFAULT NULL, id_Chambre INT DEFAULT NULL, INDEX IDX_42C849552FBB81F (id_panier), INDEX IDX_42C8495519AA3CB8 (id_voyage), INDEX IDX_42C84955646F1FAA (id_Transport), INDEX IDX_42C84955D4297413 (id_Chambre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, id_utilisateur INT DEFAULT NULL, note INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_794381C650EAE44 (id_utilisateur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, pointDepart VARCHAR(50) DEFAULT NULL, destination VARCHAR(50) DEFAULT NULL, dateDepart DATETIME DEFAULT NULL, duree INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, disponibilite TINYINT(1) DEFAULT 1 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transport (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_trajet INT DEFAULT NULL, nom VARCHAR(50) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, capacite INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, contact VARCHAR(20) DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, imagePath VARCHAR(255) DEFAULT NULL, INDEX IDX_66AB212E6B3CA4B (id_user), INDEX IDX_66AB212ED6C1C61 (id_trajet), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, numTel VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, role VARCHAR(20) NOT NULL, nom VARCHAR(40) DEFAULT NULL, prenom VARCHAR(50) DEFAULT NULL, nomagence VARCHAR(40) DEFAULT NULL, cin VARCHAR(40) DEFAULT NULL, typeAgence VARCHAR(50) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expiry DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voyage (id INT AUTO_INCREMENT NOT NULL, id_offre INT DEFAULT NULL, id_user INT DEFAULT NULL, pointDepart VARCHAR(50) DEFAULT NULL, dateDepart DATETIME DEFAULT NULL, dateRetour DATETIME DEFAULT NULL, destination VARCHAR(50) DEFAULT NULL, nbPlacesD INT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, prix DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, titre VARCHAR(50) DEFAULT NULL, pathImages LONGTEXT DEFAULT NULL, INDEX IDX_3F9D89554103C75F (id_offre), INDEX IDX_3F9D89556B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT FK_C509E4FF5040106B FOREIGN KEY (id_hebergement) REFERENCES hebergement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCD1AA708F FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9C6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F1B944D8F FOREIGN KEY (id_chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF26B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F7D1AA708F FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F76B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064046B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849552FBB81F FOREIGN KEY (id_panier) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955646F1FAA FOREIGN KEY (id_Transport) REFERENCES transport (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D4297413 FOREIGN KEY (id_Chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C650EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212E6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212ED6C1C61 FOREIGN KEY (id_trajet) REFERENCES trajet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89556B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY FK_C509E4FF5040106B');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCD1AA708F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6B3CA4B');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9C6B3CA4B');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F1B944D8F');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F6B3CA4B');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF26B3CA4B');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D6B3CA4B');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F7D1AA708F');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F76B3CA4B');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064046B3CA4B');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849552FBB81F');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955646F1FAA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D4297413');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C650EAE44');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212E6B3CA4B');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212ED6C1C61');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89556B3CA4B');
        $this->addSql('DROP TABLE chambre');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE hebergement');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE reaction');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE transport');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE voyage');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
