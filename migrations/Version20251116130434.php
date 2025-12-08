<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116130434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE investigateur_case_work (investigateur_id INT NOT NULL, case_work_id INT NOT NULL, INDEX IDX_FD665FD9B3964F8 (investigateur_id), INDEX IDX_FD665FD1497A6D0 (case_work_id), PRIMARY KEY(investigateur_id, case_work_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE investigateur_case_work ADD CONSTRAINT FK_FD665FD9B3964F8 FOREIGN KEY (investigateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE investigateur_case_work ADD CONSTRAINT FK_FD665FD1497A6D0 FOREIGN KEY (case_work_id) REFERENCES case_work (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD9B3964F8');
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD1497A6D0');
        $this->addSql('DROP TABLE investigateur_case_work');
    }
}
