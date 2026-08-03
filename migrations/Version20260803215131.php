<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803215131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, data CLOB DEFAULT NULL, sort_order INTEGER NOT NULL, deleted_at DATETIME DEFAULT NULL, workspace_id INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, CONSTRAINT FK_E11EE94D82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E11EE94D727ACA70 FOREIGN KEY (parent_id) REFERENCES items (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E11EE94D82D40A1F ON items (workspace_id)');
        $this->addSql('CREATE INDEX IDX_E11EE94D727ACA70 ON items (parent_id)');
        $this->addSql('CREATE TABLE personal_access_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, token VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_E63C2166A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E63C21665F37A13B ON personal_access_tokens (token)');
        $this->addSql('CREATE INDEX IDX_E63C2166A76ED395 ON personal_access_tokens (user_id)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, email_verified_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE workspaces (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, settings CLOB DEFAULT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_7FE8F3CBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FE8F3CBA76ED395 ON workspaces (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE items');
        $this->addSql('DROP TABLE personal_access_tokens');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE workspaces');
    }
}
