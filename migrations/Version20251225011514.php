<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225011514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Step 1: Add new columns as nullable first to handle existing records
        $this->addSql('ALTER TABLE case_work ADD assigned_team_id INT DEFAULT NULL, ADD created_by_id INT DEFAULT NULL, ADD priority VARCHAR(50) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Step 2: Update existing records with default values
        $this->addSql('UPDATE case_work SET priority = \'medium\' WHERE priority IS NULL');
        $this->addSql('UPDATE case_work SET created_at = NOW() WHERE created_at IS NULL');
        
        // Step 3: For created_by_id, set it to the first supervisor if exists (adjust as needed)
        $this->addSql('UPDATE case_work SET created_by_id = (SELECT id FROM user WHERE type = \'supervisor\' LIMIT 1) WHERE created_by_id IS NULL');
        
        // Step 4: Now make created_by_id and priority NOT NULL
        $this->addSql('ALTER TABLE case_work MODIFY created_by_id INT NOT NULL, MODIFY priority VARCHAR(50) NOT NULL, MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Step 5: Add foreign key constraints
        $this->addSql('ALTER TABLE case_work ADD CONSTRAINT FK_D256B23323F46021 FOREIGN KEY (assigned_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE case_work ADD CONSTRAINT FK_D256B233B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D256B23323F46021 ON case_work (assigned_team_id)');
        $this->addSql('CREATE INDEX IDX_D256B233B03A8386 ON case_work (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE case_work DROP FOREIGN KEY FK_D256B23323F46021');
        $this->addSql('ALTER TABLE case_work DROP FOREIGN KEY FK_D256B233B03A8386');
        $this->addSql('DROP INDEX IDX_D256B23323F46021 ON case_work');
        $this->addSql('DROP INDEX IDX_D256B233B03A8386 ON case_work');
        $this->addSql('ALTER TABLE case_work DROP assigned_team_id, DROP created_by_id, DROP priority, DROP created_at, DROP updated_at');
    }
}
