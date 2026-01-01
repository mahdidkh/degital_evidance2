<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251228175325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, target_user_id INT DEFAULT NULL, event_type VARCHAR(100) NOT NULL, event_description LONGTEXT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, severity VARCHAR(20) DEFAULT \'info\' NOT NULL, INDEX IDX_F6E1C0F5A76ED395 (user_id), INDEX IDX_F6E1C0F56C066AFE (target_user_id), INDEX idx_audit_created_at (created_at), INDEX idx_audit_event_type (event_type), INDEX idx_audit_severity (severity), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE case_work (id INT AUTO_INCREMENT NOT NULL, assigned_team_id INT DEFAULT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, priority VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D256B23323F46021 (assigned_team_id), INDEX IDX_D256B233B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chain_of_custody (id INT AUTO_INCREMENT NOT NULL, evidence_id INT DEFAULT NULL, user_id INT DEFAULT NULL, action VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date_update DATE NOT NULL, new_hash VARCHAR(255) NOT NULL, previos_hash VARCHAR(255) NOT NULL, INDEX IDX_90DA1918B528FC11 (evidence_id), INDEX IDX_90DA1918A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evidence (id INT AUTO_INCREMENT NOT NULL, case_work_id INT DEFAULT NULL, uploaded_by_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, file_hash VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) DEFAULT NULL, remarque LONGTEXT DEFAULT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C6157101497A6D0 (case_work_id), INDEX IDX_C615710A2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, supervisor_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C4E0A61F19E9AC5F (supervisor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_investigateur (team_id INT NOT NULL, investigateur_id INT NOT NULL, INDEX IDX_1BF1F0C7296CD8AE (team_id), INDEX IDX_1BF1F0C79B3964F8 (investigateur_id), PRIMARY KEY(team_id, investigateur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, supervisor_id INT DEFAULT NULL, cin VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, role VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, tel VARCHAR(255) NOT NULL, is_email_auth_enabled TINYINT(1) NOT NULL, email_auth_recipient VARCHAR(255) DEFAULT NULL, email_auth_code VARCHAR(255) DEFAULT NULL, invitation_token VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, employer_id VARCHAR(255) DEFAULT NULL, expert_area VARCHAR(255) DEFAULT NULL, team_scoop VARCHAR(255) DEFAULT NULL, escalation VARCHAR(255) DEFAULT NULL, admin_domain VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64919E9AC5F (supervisor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE investigateur_case_work (investigateur_id INT NOT NULL, case_work_id INT NOT NULL, INDEX IDX_FD665FD9B3964F8 (investigateur_id), INDEX IDX_FD665FD1497A6D0 (case_work_id), PRIMARY KEY(investigateur_id, case_work_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F56C066AFE FOREIGN KEY (target_user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE case_work ADD CONSTRAINT FK_D256B23323F46021 FOREIGN KEY (assigned_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE case_work ADD CONSTRAINT FK_D256B233B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chain_of_custody ADD CONSTRAINT FK_90DA1918B528FC11 FOREIGN KEY (evidence_id) REFERENCES evidence (id)');
        $this->addSql('ALTER TABLE chain_of_custody ADD CONSTRAINT FK_90DA1918A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE evidence ADD CONSTRAINT FK_C6157101497A6D0 FOREIGN KEY (case_work_id) REFERENCES case_work (id)');
        $this->addSql('ALTER TABLE evidence ADD CONSTRAINT FK_C615710A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F19E9AC5F FOREIGN KEY (supervisor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE team_investigateur ADD CONSTRAINT FK_1BF1F0C7296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_investigateur ADD CONSTRAINT FK_1BF1F0C79B3964F8 FOREIGN KEY (investigateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64919E9AC5F FOREIGN KEY (supervisor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE investigateur_case_work ADD CONSTRAINT FK_FD665FD9B3964F8 FOREIGN KEY (investigateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE investigateur_case_work ADD CONSTRAINT FK_FD665FD1497A6D0 FOREIGN KEY (case_work_id) REFERENCES case_work (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F56C066AFE');
        $this->addSql('ALTER TABLE case_work DROP FOREIGN KEY FK_D256B23323F46021');
        $this->addSql('ALTER TABLE case_work DROP FOREIGN KEY FK_D256B233B03A8386');
        $this->addSql('ALTER TABLE chain_of_custody DROP FOREIGN KEY FK_90DA1918B528FC11');
        $this->addSql('ALTER TABLE chain_of_custody DROP FOREIGN KEY FK_90DA1918A76ED395');
        $this->addSql('ALTER TABLE evidence DROP FOREIGN KEY FK_C6157101497A6D0');
        $this->addSql('ALTER TABLE evidence DROP FOREIGN KEY FK_C615710A2B28FE8');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F19E9AC5F');
        $this->addSql('ALTER TABLE team_investigateur DROP FOREIGN KEY FK_1BF1F0C7296CD8AE');
        $this->addSql('ALTER TABLE team_investigateur DROP FOREIGN KEY FK_1BF1F0C79B3964F8');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64919E9AC5F');
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD9B3964F8');
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD1497A6D0');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE case_work');
        $this->addSql('DROP TABLE chain_of_custody');
        $this->addSql('DROP TABLE evidence');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_investigateur');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE investigateur_case_work');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
