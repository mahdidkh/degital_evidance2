<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215175130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evidence (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evidance ADD case_work_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE evidance ADD CONSTRAINT FK_8303C0471497A6D0 FOREIGN KEY (case_work_id) REFERENCES case_work (id)');
        $this->addSql('CREATE INDEX IDX_8303C0471497A6D0 ON evidance (case_work_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE evidence');
        $this->addSql('ALTER TABLE evidance DROP FOREIGN KEY FK_8303C0471497A6D0');
        $this->addSql('DROP INDEX IDX_8303C0471497A6D0 ON evidance');
        $this->addSql('ALTER TABLE evidance DROP case_work_id');
    }
}
