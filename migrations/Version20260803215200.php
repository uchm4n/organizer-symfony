<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803215200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__items AS SELECT id, type, title, data, sort_order, deleted_at, workspace_id, parent_id FROM items');
        $this->addSql('DROP TABLE items');
        $this->addSql('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type INTEGER NOT NULL, title VARCHAR(255) NOT NULL, data CLOB DEFAULT NULL, sort_order INTEGER NOT NULL, deleted_at DATETIME DEFAULT NULL, workspace_id INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, CONSTRAINT FK_E11EE94D82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E11EE94D727ACA70 FOREIGN KEY (parent_id) REFERENCES items (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO items (id, type, title, data, sort_order, deleted_at, workspace_id, parent_id) SELECT id, type, title, data, sort_order, deleted_at, workspace_id, parent_id FROM __temp__items');
        $this->addSql('DROP TABLE __temp__items');
        $this->addSql('CREATE INDEX IDX_E11EE94D727ACA70 ON items (parent_id)');
        $this->addSql('CREATE INDEX IDX_E11EE94D82D40A1F ON items (workspace_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__items AS SELECT id, type, title, data, sort_order, deleted_at, workspace_id, parent_id FROM items');
        $this->addSql('DROP TABLE items');
        $this->addSql('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, data CLOB DEFAULT NULL, sort_order INTEGER NOT NULL, deleted_at DATETIME DEFAULT NULL, workspace_id INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, CONSTRAINT FK_E11EE94D82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E11EE94D727ACA70 FOREIGN KEY (parent_id) REFERENCES items (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO items (id, type, title, data, sort_order, deleted_at, workspace_id, parent_id) SELECT id, type, title, data, sort_order, deleted_at, workspace_id, parent_id FROM __temp__items');
        $this->addSql('DROP TABLE __temp__items');
        $this->addSql('CREATE INDEX IDX_E11EE94D82D40A1F ON items (workspace_id)');
        $this->addSql('CREATE INDEX IDX_E11EE94D727ACA70 ON items (parent_id)');
    }
}
