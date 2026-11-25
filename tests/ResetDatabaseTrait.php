<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait ResetDatabaseTrait
{
    private function resetDatabase(EntityManagerInterface $em): void
    {
        $connection = $em->getConnection();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $schemaTool->dropSchema($metadata);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $schemaTool->createSchema($metadata);
    }
}
