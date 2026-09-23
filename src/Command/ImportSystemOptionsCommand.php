<?php

namespace App\Command;

use App\Entity\SystemOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import:system-options',
    description: 'Importe les systèmes de paris (Trixie, Patent...) depuis config/vault_data/system_options.php'
)]
class ImportSystemOptionsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = include __DIR__ . '/../../config/vault_data/system_options.php';

        // Table de correspondance manuelle pour les systèmes spéciaux (nombre de sélections requis)
        $specialMatches = [
            'Trixie' => 3, 'Patent' => 3, 'Yankee' => 4, 'Lucky 15' => 4,
            'Canadian (Super Yankee)' => 5, 'Lucky 31' => 5, 'Heinz' => 6,
            'Lucky 63' => 6, 'Super Heinz' => 7, 'Goliath' => 8,
        ];

        foreach ($data as $systems) {
            foreach ($systems as $item) {
                $label = trim($item['name']);
                $value = mb_substr(trim($item['description']), 0, 255);

                $matches = 0;
                if (preg_match('/(\d+)\/(\d+)/', $label, $m)) {
                    $matches = (int) $m[2]; // "2/5" -> matches = 5
                } elseif (isset($specialMatches[$label])) {
                    $matches = $specialMatches[$label];
                }

                $existing = $this->em->getRepository(SystemOption::class)->findOneBy(['label' => $label]);

                if ($existing) {
                    $existing->setMatches($matches);
                    $existing->setValue($value);
                    $output->writeln("Mis à jour : {$label}");
                } else {
                    $system = new SystemOption();
                    $system->setLabel($label);
                    $system->setValue($value);
                    $system->setMatches($matches);
                    $this->em->persist($system);
                    $output->writeln("Importé : {$label}");
                }
            }
        }

        $this->em->flush();
        $output->writeln('Import terminé.');

        return Command::SUCCESS;
    }
}
