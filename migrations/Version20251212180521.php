<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212180521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evidance DROP original_filename, DROP mime_type, DROP size, DROP extension');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evidance ADD original_filename VARCHAR(255) DEFAULT NULL, ADD mime_type VARCHAR(255) DEFAULT NULL, ADD size INT DEFAULT NULL, ADD extension VARCHAR(50) DEFAULT NULL');
    }
}
