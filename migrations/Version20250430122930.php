<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250430122930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY chambre_ibfk_1');
        $this->addSql('ALTER TABLE chambre ADD url_3d VARCHAR(2000) DEFAULT NULL, CHANGE id_hebergement id_hebergement INT DEFAULT NULL, CHANGE disponibilite disponibilite TINYINT(1) DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE equipements equipements LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_hebergement ON chambre');
        $this->addSql('CREATE INDEX IDX_C509E4FF5040106B ON chambre (id_hebergement)');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT chambre_ibfk_1 FOREIGN KEY (id_hebergement) REFERENCES hebergement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY commentaire_ibfk_2');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY commentaire_ibfk_1');
        $this->addSql('ALTER TABLE commentaire CHANGE id_post id_post INT DEFAULT NULL, CHANGE id_user id_user INT DEFAULT NULL, CHANGE date date DATETIME DEFAULT NULL, CHANGE contenu contenu LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_post ON commentaire');
        $this->addSql('CREATE INDEX IDX_67F068BCD1AA708F ON commentaire (id_post)');
        $this->addSql('DROP INDEX id_user ON commentaire');
        $this->addSql('CREATE INDEX IDX_67F068BC6B3CA4B ON commentaire (id_user)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT commentaire_ibfk_2 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT commentaire_ibfk_1 FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY hebergement_ibfk_1');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY hebergement_ibfk_1');
        $this->addSql('ALTER TABLE hebergement ADD type_hebergement VARCHAR(50) NOT NULL, ADD nb_chambres INT NOT NULL, DROP typeHebergement, DROP nbChambres, CHANGE id_user id_user INT DEFAULT NULL, CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE adresse adresse VARCHAR(255) NOT NULL, CHANGE ville ville VARCHAR(100) NOT NULL, CHANGE pays pays VARCHAR(100) NOT NULL, CHANGE note_moyenne note_moyenne DOUBLE PRECISION NOT NULL, CHANGE services services LONGTEXT NOT NULL, CHANGE politique_annulation politique_annulation LONGTEXT NOT NULL, CHANGE contact contact VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9C6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id)');
        $this->addSql('DROP INDEX id_user ON hebergement');
        $this->addSql('CREATE INDEX IDX_4852DD9C6B3CA4B ON hebergement (id_user)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT hebergement_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY image_ibfk_1');
        $this->addSql('ALTER TABLE image CHANGE id_chambre id_chambre INT DEFAULT NULL');
        $this->addSql('DROP INDEX id_chambre ON image');
        $this->addSql('CREATE INDEX IDX_C53D045F1B944D8F ON image (id_chambre)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT image_ibfk_1 FOREIGN KEY (id_chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY fk_offre_user');
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE reduction reduction DOUBLE PRECISION NOT NULL, CHANGE dateDebut dateDebut DATETIME NOT NULL, CHANGE dateFin dateFin DATETIME NOT NULL');
        $this->addSql('DROP INDEX fk_offre_user ON offre');
        $this->addSql('CREATE INDEX IDX_AF86866F6B3CA4B ON offre (id_user)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT fk_offre_user FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY panier_ibfk_1');
        $this->addSql('ALTER TABLE panier CHANGE id_user id_user INT DEFAULT NULL, CHANGE prix_total prix_total DOUBLE PRECISION DEFAULT NULL, CHANGE date_validation date_validation DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX id_user ON panier');
        $this->addSql('CREATE INDEX IDX_24CC0DF26B3CA4B ON panier (id_user)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT panier_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY post_ibfk_1');
        $this->addSql('ALTER TABLE post CHANGE id_user id_user INT DEFAULT NULL, CHANGE statut statut LONGTEXT DEFAULT \'non traitée\' NOT NULL, CHANGE date date DATETIME DEFAULT NULL, CHANGE image image LONGTEXT DEFAULT NULL, CHANGE nbCommentaires nbCommentaires INT DEFAULT NULL, CHANGE nbReactions nbReactions INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_user ON post');
        $this->addSql('CREATE INDEX IDX_5A8A6C8D6B3CA4B ON post (id_user)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT post_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY reaction_ibfk_1');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY reaction_ibfk_2');
        $this->addSql('ALTER TABLE reaction CHANGE id_post id_post INT DEFAULT NULL, CHANGE id_user id_user INT DEFAULT NULL, CHANGE date date DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX id_post ON reaction');
        $this->addSql('CREATE INDEX IDX_A4D707F7D1AA708F ON reaction (id_post)');
        $this->addSql('DROP INDEX id_user ON reaction');
        $this->addSql('CREATE INDEX IDX_A4D707F76B3CA4B ON reaction (id_user)');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT reaction_ibfk_1 FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT reaction_ibfk_2 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY reclamation_ibfk_1');
        $this->addSql('ALTER TABLE reclamation CHANGE id_user id_user INT DEFAULT NULL, CHANGE dateReclamation dateReclamation DATETIME DEFAULT NULL, CHANGE etat etat VARCHAR(50) DEFAULT NULL');
        $this->addSql('DROP INDEX id_user ON reclamation');
        $this->addSql('CREATE INDEX IDX_CE6064046B3CA4B ON reclamation (id_user)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT reclamation_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_4');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_chambre');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_4');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_1');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2');
        $this->addSql('ALTER TABLE reservation CHANGE id_panier id_panier INT DEFAULT NULL, CHANGE type_service type_service LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955646F1FAA FOREIGN KEY (id_Transport) REFERENCES transport (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX id_panier ON reservation');
        $this->addSql('CREATE INDEX IDX_42C849552FBB81F ON reservation (id_panier)');
        $this->addSql('DROP INDEX id_voyage ON reservation');
        $this->addSql('CREATE INDEX IDX_42C8495519AA3CB8 ON reservation (id_voyage)');
        $this->addSql('DROP INDEX id_transport ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955646F1FAA ON reservation (id_Transport)');
        $this->addSql('DROP INDEX fk_reservation_chambre ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955D4297413 ON reservation (id_Chambre)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_chambre FOREIGN KEY (id_Chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_4 FOREIGN KEY (id_Transport) REFERENCES transport (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_1 FOREIGN KEY (id_panier) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY review_ibfk_1');
        $this->addSql('ALTER TABLE review CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_utilisateur ON review');
        $this->addSql('CREATE INDEX IDX_794381C650EAE44 ON review (id_utilisateur)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT review_ibfk_1 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet CHANGE description description LONGTEXT DEFAULT NULL, CHANGE disponibilite disponibilite TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY transport_ibfk_2');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY transport_ibfk_2');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY transport_ibfk_1');
        $this->addSql('ALTER TABLE transport CHANGE id_user id_user INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212ED6C1C61 FOREIGN KEY (id_trajet) REFERENCES trajet (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX id_user ON transport');
        $this->addSql('CREATE INDEX IDX_66AB212E6B3CA4B ON transport (id_user)');
        $this->addSql('DROP INDEX id_trajet ON transport');
        $this->addSql('CREATE INDEX IDX_66AB212ED6C1C61 ON transport (id_trajet)');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT transport_ibfk_2 FOREIGN KEY (id_trajet) REFERENCES trajet (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT transport_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('DROP INDEX username ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expiry DATETIME DEFAULT NULL, ADD banned DATETIME DEFAULT NULL, CHANGE address address LONGTEXT DEFAULT NULL, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY voyage_ibfk_2');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY voyage_ibfk_1');
        $this->addSql('ALTER TABLE voyage CHANGE id_user id_user INT DEFAULT NULL, CHANGE pointDepart pointDepart VARCHAR(50) NOT NULL, CHANGE dateDepart dateDepart DATETIME NOT NULL, CHANGE dateRetour dateRetour DATETIME NOT NULL, CHANGE destination destination VARCHAR(50) NOT NULL, CHANGE nbPlacesD nbPlacesD INT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE titre titre VARCHAR(50) NOT NULL, CHANGE pathImages pathImages LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_offre ON voyage');
        $this->addSql('CREATE INDEX IDX_3F9D89554103C75F ON voyage (id_offre)');
        $this->addSql('DROP INDEX id_user ON voyage');
        $this->addSql('CREATE INDEX IDX_3F9D89556B3CA4B ON voyage (id_user)');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT voyage_ibfk_2 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT voyage_ibfk_1 FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY FK_C509E4FF5040106B');
        $this->addSql('ALTER TABLE chambre DROP url_3d, CHANGE id_hebergement id_hebergement INT NOT NULL, CHANGE disponibilite disponibilite TINYINT(1) DEFAULT 1, CHANGE prix prix NUMERIC(10, 2) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE equipements equipements TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_c509e4ff5040106b ON chambre');
        $this->addSql('CREATE INDEX id_hebergement ON chambre (id_hebergement)');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT FK_C509E4FF5040106B FOREIGN KEY (id_hebergement) REFERENCES hebergement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCD1AA708F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6B3CA4B');
        $this->addSql('ALTER TABLE commentaire CHANGE id_post id_post INT NOT NULL, CHANGE id_user id_user INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE contenu contenu TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_67f068bcd1aa708f ON commentaire');
        $this->addSql('CREATE INDEX id_post ON commentaire (id_post)');
        $this->addSql('DROP INDEX idx_67f068bc6b3ca4b ON commentaire');
        $this->addSql('CREATE INDEX id_user ON commentaire (id_user)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCD1AA708F FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9C6B3CA4B');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9C6B3CA4B');
        $this->addSql('ALTER TABLE hebergement ADD typeHebergement VARCHAR(50) DEFAULT NULL, ADD nbChambres INT DEFAULT NULL, DROP type_hebergement, DROP nb_chambres, CHANGE id_user id_user INT NOT NULL, CHANGE nom nom VARCHAR(50) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE ville ville VARCHAR(100) DEFAULT NULL, CHANGE pays pays VARCHAR(100) DEFAULT NULL, CHANGE note_moyenne note_moyenne DOUBLE PRECISION DEFAULT NULL, CHANGE services services TEXT DEFAULT NULL, CHANGE politique_annulation politique_annulation TEXT DEFAULT NULL, CHANGE contact contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT hebergement_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_4852dd9c6b3ca4b ON hebergement');
        $this->addSql('CREATE INDEX id_user ON hebergement (id_user)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9C6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F1B944D8F');
        $this->addSql('ALTER TABLE image CHANGE id_chambre id_chambre INT NOT NULL');
        $this->addSql('DROP INDEX idx_c53d045f1b944d8f ON image');
        $this->addSql('CREATE INDEX id_chambre ON image (id_chambre)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F1B944D8F FOREIGN KEY (id_chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F6B3CA4B');
        $this->addSql('ALTER TABLE offre CHANGE titre titre VARCHAR(50) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE reduction reduction NUMERIC(5, 2) DEFAULT NULL, CHANGE dateDebut dateDebut DATETIME DEFAULT NULL, CHANGE dateFin dateFin DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_af86866f6b3ca4b ON offre');
        $this->addSql('CREATE INDEX fk_offre_user ON offre (id_user)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF26B3CA4B');
        $this->addSql('ALTER TABLE panier CHANGE id_user id_user INT NOT NULL, CHANGE prix_total prix_total NUMERIC(10, 2) DEFAULT NULL, CHANGE date_validation date_validation DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX idx_24cc0df26b3ca4b ON panier');
        $this->addSql('CREATE INDEX id_user ON panier (id_user)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF26B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D6B3CA4B');
        $this->addSql('ALTER TABLE post CHANGE id_user id_user INT NOT NULL, CHANGE statut statut TEXT DEFAULT \'non traitée\', CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE image image TEXT DEFAULT NULL, CHANGE nbCommentaires nbCommentaires INT DEFAULT 0, CHANGE nbReactions nbReactions INT DEFAULT 0, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_5a8a6c8d6b3ca4b ON post');
        $this->addSql('CREATE INDEX id_user ON post (id_user)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F7D1AA708F');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_A4D707F76B3CA4B');
        $this->addSql('ALTER TABLE reaction CHANGE id_post id_post INT NOT NULL, CHANGE id_user id_user INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX idx_a4d707f76b3ca4b ON reaction');
        $this->addSql('CREATE INDEX id_user ON reaction (id_user)');
        $this->addSql('DROP INDEX idx_a4d707f7d1aa708f ON reaction');
        $this->addSql('CREATE INDEX id_post ON reaction (id_post)');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F7D1AA708F FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_A4D707F76B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064046B3CA4B');
        $this->addSql('ALTER TABLE reclamation CHANGE id_user id_user INT NOT NULL, CHANGE dateReclamation dateReclamation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE etat etat VARCHAR(50) DEFAULT \'non traité\'');
        $this->addSql('DROP INDEX idx_ce6064046b3ca4b ON reclamation');
        $this->addSql('CREATE INDEX id_user ON reclamation (id_user)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064046B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955646F1FAA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849552FBB81F');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955646F1FAA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D4297413');
        $this->addSql('ALTER TABLE reservation CHANGE id_panier id_panier INT NOT NULL, CHANGE type_service type_service TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_4 FOREIGN KEY (id_Transport) REFERENCES transport (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_42c84955d4297413 ON reservation');
        $this->addSql('CREATE INDEX fk_reservation_chambre ON reservation (id_Chambre)');
        $this->addSql('DROP INDEX idx_42c849552fbb81f ON reservation');
        $this->addSql('CREATE INDEX id_panier ON reservation (id_panier)');
        $this->addSql('DROP INDEX idx_42c8495519aa3cb8 ON reservation');
        $this->addSql('CREATE INDEX id_voyage ON reservation (id_voyage)');
        $this->addSql('DROP INDEX idx_42c84955646f1faa ON reservation');
        $this->addSql('CREATE INDEX id_Transport ON reservation (id_Transport)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849552FBB81F FOREIGN KEY (id_panier) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955646F1FAA FOREIGN KEY (id_Transport) REFERENCES transport (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D4297413 FOREIGN KEY (id_Chambre) REFERENCES chambre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C650EAE44');
        $this->addSql('ALTER TABLE review CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_794381c650eae44 ON review');
        $this->addSql('CREATE INDEX id_utilisateur ON review (id_utilisateur)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C650EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet CHANGE description description TEXT DEFAULT NULL, CHANGE disponibilite disponibilite TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212ED6C1C61');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212E6B3CA4B');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212ED6C1C61');
        $this->addSql('ALTER TABLE transport CHANGE id_user id_user INT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE prix prix NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT transport_ibfk_2 FOREIGN KEY (id_trajet) REFERENCES trajet (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_66ab212ed6c1c61 ON transport');
        $this->addSql('CREATE INDEX id_trajet ON transport (id_trajet)');
        $this->addSql('DROP INDEX idx_66ab212e6b3ca4b ON transport');
        $this->addSql('CREATE INDEX id_user ON transport (id_user)');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212E6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212ED6C1C61 FOREIGN KEY (id_trajet) REFERENCES trajet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur DROP reset_token, DROP reset_token_expiry, DROP banned, CHANGE address address TEXT DEFAULT NULL, CHANGE nom nom VARCHAR(40) DEFAULT NULL, CHANGE prenom prenom VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
        $this->addSql('CREATE UNIQUE INDEX username ON utilisateur (username)');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89554103C75F');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D89556B3CA4B');
        $this->addSql('ALTER TABLE voyage CHANGE id_user id_user INT NOT NULL, CHANGE pointDepart pointDepart VARCHAR(50) DEFAULT NULL, CHANGE dateDepart dateDepart DATETIME DEFAULT NULL, CHANGE dateRetour dateRetour DATETIME DEFAULT NULL, CHANGE destination destination VARCHAR(50) DEFAULT NULL, CHANGE nbPlacesD nbPlacesD INT DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE prix prix NUMERIC(10, 2) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE titre titre VARCHAR(50) DEFAULT NULL, CHANGE pathImages pathImages TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_3f9d89556b3ca4b ON voyage');
        $this->addSql('CREATE INDEX id_user ON voyage (id_user)');
        $this->addSql('DROP INDEX idx_3f9d89554103c75f ON voyage');
        $this->addSql('CREATE INDEX id_offre ON voyage (id_offre)');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89554103C75F FOREIGN KEY (id_offre) REFERENCES offre (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D89556B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }
}
