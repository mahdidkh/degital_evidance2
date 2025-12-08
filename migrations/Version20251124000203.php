<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124000203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Tables already exist from previous migrations, this is a no-op
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD9B3964F8');
        $this->addSql('ALTER TABLE investigateur_case_work DROP FOREIGN KEY FK_FD665FD1497A6D0');
        $this->addSql('DROP TABLE case_work');
        $this->addSql('DROP TABLE chain_of_coady');
        $this->addSql('DROP TABLE evidance');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE investigateur_case_work');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
