<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Gives each test a fresh, empty test database.
 */
trait ResetDatabaseTrait
{
    private function resetDatabase(EntityManagerInterface $em): void
    {
        $connection = $em->getConnection();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        // Foreign keys are paused while dropping, otherwise SQLite refuses
        // to drop tables that still have linked rows.
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $schemaTool->dropSchema($metadata);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $schemaTool->createSchema($metadata);
    }
}
