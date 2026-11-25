<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125130030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, author_id, listing_id FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, comment CLOB NOT NULL, created_at DATETIME NOT NULL, author_id INTEGER NOT NULL, listing_id INTEGER NOT NULL, CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C6D4619D1A FOREIGN KEY (listing_id) REFERENCES listing (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, author_id, listing_id) SELECT id, rating, comment, created_at, author_id, listing_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6D4619D1A ON review (listing_id)');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REVIEW_AUTHOR_LISTING ON review (author_id, listing_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, author_id, listing_id FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, comment CLOB NOT NULL, created_at DATETIME NOT NULL, author_id INTEGER NOT NULL, listing_id INTEGER NOT NULL, CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C6D4619D1A FOREIGN KEY (listing_id) REFERENCES listing (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, author_id, listing_id) SELECT id, rating, comment, created_at, author_id, listing_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE INDEX IDX_794381C6D4619D1A ON review (listing_id)');
    }
}
