<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124151151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE amenity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AMENITY_SLUG ON amenity (slug)');
        $this->addSql('CREATE TABLE listing_amenity (listing_id INTEGER NOT NULL, amenity_id INTEGER NOT NULL, PRIMARY KEY (listing_id, amenity_id), FOREIGN KEY (listing_id) REFERENCES listing (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (amenity_id) REFERENCES amenity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B45E022CD4619D1A ON listing_amenity (listing_id)');
        $this->addSql('CREATE INDEX IDX_B45E022C9F9F1305 ON listing_amenity (amenity_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE amenity');
        $this->addSql('DROP TABLE listing_amenity');
    }
}
