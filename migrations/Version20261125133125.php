<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261125133125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amenity ADD COLUMN created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE amenity ADD COLUMN updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD COLUMN created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD COLUMN updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD COLUMN created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD COLUMN updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, author_id, listing_id FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, comment CLOB NOT NULL, created_at DATETIME DEFAULT NULL, author_id INTEGER NOT NULL, listing_id INTEGER NOT NULL, updated_at DATETIME DEFAULT NULL, CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C6D4619D1A FOREIGN KEY (listing_id) REFERENCES listing (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, author_id, listing_id) SELECT id, rating, comment, created_at, author_id, listing_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REVIEW_AUTHOR_LISTING ON review (author_id, listing_id)');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE INDEX IDX_794381C6D4619D1A ON review (listing_id)');
        $this->addSql('ALTER TABLE user ADD COLUMN created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__amenity AS SELECT id, name, slug FROM amenity');
        $this->addSql('DROP TABLE amenity');
        $this->addSql('CREATE TABLE amenity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO amenity (id, name, slug) SELECT id, name, slug FROM __temp__amenity');
        $this->addSql('DROP TABLE __temp__amenity');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AMENITY_SLUG ON amenity (slug)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__booking AS SELECT id, arrival_date, departure_date, guests, total_price, status, traveler_id, listing_id FROM booking');
        $this->addSql('DROP TABLE booking');
        $this->addSql('CREATE TABLE booking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, arrival_date DATE NOT NULL, departure_date DATE NOT NULL, guests INTEGER NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(255) NOT NULL, traveler_id INTEGER NOT NULL, listing_id INTEGER NOT NULL, CONSTRAINT FK_E00CEDDE59BBE8A3 FOREIGN KEY (traveler_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E00CEDDED4619D1A FOREIGN KEY (listing_id) REFERENCES listing (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO booking (id, arrival_date, departure_date, guests, total_price, status, traveler_id, listing_id) SELECT id, arrival_date, departure_date, guests, total_price, status, traveler_id, listing_id FROM __temp__booking');
        $this->addSql('DROP TABLE __temp__booking');
        $this->addSql('CREATE INDEX IDX_E00CEDDE59BBE8A3 ON booking (traveler_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDED4619D1A ON booking (listing_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__listing AS SELECT id, title, description, price_by_night, city, country, max_guests, host_id FROM listing');
        $this->addSql('DROP TABLE listing');
        $this->addSql('CREATE TABLE listing (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, price_by_night NUMERIC(10, 2) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(100) NOT NULL, max_guests INTEGER NOT NULL, host_id INTEGER NOT NULL, CONSTRAINT FK_CB0048D41FB8D185 FOREIGN KEY (host_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO listing (id, title, description, price_by_night, city, country, max_guests, host_id) SELECT id, title, description, price_by_night, city, country, max_guests, host_id FROM __temp__listing');
        $this->addSql('DROP TABLE __temp__listing');
        $this->addSql('CREATE INDEX IDX_CB0048D41FB8D185 ON listing (host_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, author_id, listing_id FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, comment CLOB NOT NULL, created_at DATETIME NOT NULL, author_id INTEGER NOT NULL, listing_id INTEGER NOT NULL, CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C6D4619D1A FOREIGN KEY (listing_id) REFERENCES listing (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, author_id, listing_id) SELECT id, rating, comment, created_at, author_id, listing_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REVIEW_AUTHOR_LISTING ON review (author_id, listing_id)');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE INDEX IDX_794381C6D4619D1A ON review (listing_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, first_name, last_name) SELECT id, email, roles, password, first_name, last_name FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
