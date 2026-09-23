<?php

namespace App\Command;

use App\Service\AppleClientSecretGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:apple:generate-client-secret',
    description: 'Génère le client_secret (JWT) pour "Se connecter avec Apple", à coller dans APPLE_CLIENT_SECRET.'
)]
class GenerateAppleClientSecretCommand extends Command
{
    public function __construct(private readonly AppleClientSecretGenerator $generator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $secret = $this->generator->generate();
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Secret généré (valable 6 mois). Colle-le dans APPLE_CLIENT_SECRET (.env.local) :');
        $output->writeln($secret);

        return Command::SUCCESS;
    }
}
