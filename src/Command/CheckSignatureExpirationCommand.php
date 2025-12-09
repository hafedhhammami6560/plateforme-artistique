<?php

namespace App\Command;

use App\Service\ElectronicSignatureService;
use App\Repository\SignatureRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:signatures:check-expiration',
    description: 'Vérifie et marque les signatures expirées',
)]
class CheckSignatureExpirationCommand extends Command
{
    public function __construct(
        private SignatureRepository $signatureRepository,
        private ElectronicSignatureService $signatureService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des signatures expirées');

        // Récupérer toutes les signatures expirées
        $expiredSignatures = $this->signatureRepository->findExpiredSignatures();

        if (empty($expiredSignatures)) {
            $io->success('Aucune signature expirée trouvée.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d signature(s) expirée(s)', count($expiredSignatures)));

        $processed = 0;
        foreach ($expiredSignatures as $signature) {
            $contract = $signature->getContrat();
            
            try {
                // Vérifier l'expiration du contrat
                $hasExpired = $this->signatureService->checkSignatureExpiration($contract);
                
                if ($hasExpired) {
                    $io->writeln(sprintf(
                        '<comment>[EXPIRÉ]</comment> Signature #%d - Contrat %s - Expiré le %s',
                        $signature->getId(),
                        $contract->getNumeroContrat(),
                        $signature->getExpiresAt()->format('d/m/Y')
                    ));
                    $processed++;
                }
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Erreur lors du traitement de la signature #%d : %s',
                    $signature->getId(),
                    $e->getMessage()
                ));
            }
        }

        $io->success(sprintf('%d signature(s) marquée(s) comme expirée(s).', $processed));

        return Command::SUCCESS;
    }
}
