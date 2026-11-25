<?php

namespace App\Command;

use App\Service\ListingImporter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-listings',
    description: 'Importe des logements depuis un fichier JSON Lines (un logement par ligne)',
)]
final class ImportListingsCommand
{
    public function __construct(
        private readonly ListingImporter $importer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Fichier à importer, absolu ou relatif au projet')]
        string $file = 'data/listings.jsonl',
    ): int {
        $path = str_starts_with($file, '/') ? $file : $this->projectDir.'/'.$file;
        $io->title('Import des logements depuis '.$file);

        $io->progressStart();
        try {
            $report = $this->importer->importFile($path, static fn () => $io->progressAdvance());
        } catch (\InvalidArgumentException $e) {
            $io->progressFinish();
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        $io->progressFinish();

        $io->table(['Résultat', 'Nombre'], [
            ['Logements créés', $report->created],
            ['Logements déjà présents (ignorés)', $report->skipped],
            ['Hôtes créés', $report->createdHosts],
            ['Équipements créés', $report->createdAmenities],
            ['Lignes en erreur', \count($report->errors)],
        ]);

        if ([] !== $report->errors) {
            $io->warning('Certaines lignes n\'ont pas été importées :');
            $io->table(['Ligne', 'Erreur'], array_map(null, array_keys($report->errors), $report->errors));
        }

        $io->success(\sprintf('%d logement(s) importé(s).', $report->created));

        return Command::SUCCESS;
    }
}
