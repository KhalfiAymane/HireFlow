<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225215813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, resume VARCHAR(255) NOT NULL, cover_letter LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, candidate_id INT NOT NULL, offer_id INT NOT NULL, INDEX IDX_A45BDDC191BD8781 (candidate_id), INDEX IDX_A45BDDC153C674EE (offer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, skills LONGTEXT NOT NULL, location VARCHAR(100) DEFAULT NULL, salary VARCHAR(50) DEFAULT NULL, contract_type VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, recruiter_id INT NOT NULL, INDEX IDX_29D6873E156BE243 (recruiter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC191BD8781 FOREIGN KEY (candidate_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC153C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC191BD8781');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC153C674EE');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E156BE243');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE user');
    }
}
