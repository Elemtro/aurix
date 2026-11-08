<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108124518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_phrase (id INT AUTO_INCREMENT NOT NULL, phrase_index INT NOT NULL, phrase VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX uniq_user_phrase_index (user_id, phrase_index), INDEX IDX_C719FD31A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_phrase ADD CONSTRAINT FK_C719FD31A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_phrase DROP FOREIGN KEY FK_C719FD31A76ED395');
        $this->addSql('DROP TABLE user_phrase');
    }
}
