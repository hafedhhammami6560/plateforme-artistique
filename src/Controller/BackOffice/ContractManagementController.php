<?php

namespace App\Controller\BackOffice;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backoffice/contracts')]
#[IsGranted('ROLE_USER')]
class ContractManagementController extends AbstractController
{
    #[Route('/', name: 'app_backoffice_contract_index')]
    public function index(Request $request, ContractRepository $repository): Response
    {
        $user = $this->getUser();
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        // Récupérer les contrats de l'utilisateur
        $contracts = $repository->findForUser($user, $status);

        // Filtrer par recherche si fourni
        if ($search) {
            $contracts = array_filter($contracts, function($contract) use ($search) {
                return stripos($contract->getReferenceNumber(), $search) !== false ||
                       stripos($contract->getTerms(), $search) !== false;
            });
        }

        // Statistiques pour l'utilisateur
        $stats = [
            'total' => count($repository->findForUser($user)),
            'active' => count($repository->findForUser($user, 'active')),
            'signed' => count($repository->findForUser($user, 'signed')),
            'draft' => count($repository->findForUser($user, 'draft')),
            'total_commission' => $this->calculateTotalCommission($user, $repository),
        ];

        return $this->render('backoffice/contract/index.html.twig', [
            'contracts' => $contracts,
            'stats' => $stats,
            'current_status' => $status,
            'search' => $search,
        ]);
    }

    #[Route('/{id}/financial-report', name: 'app_backoffice_contract_financial_report', requirements: ['id' => '\d+'])]
    public function financialReport(Contract $contract): Response
    {
        $this->denyAccessUnlessGranted('CONTRACT_VIEW', $contract);

        // Calculer les données financières
        $financialData = [
            'commission_rate' => $contract->getCommissionRate(),
            'start_date' => $contract->getStartDate(),
            'end_date' => $contract->getEndDate(),
            'duration_days' => $contract->getStartDate() && $contract->getEndDate() 
                ? $contract->getStartDate()->diff($contract->getEndDate())->days 
                : null,
            'status' => $contract->getStatus(),
            'is_active' => $contract->isActive(),
            'days_until_expiry' => $contract->getEndDate() 
                ? (new \DateTime())->diff($contract->getEndDate())->days 
                : null,
        ];

        return $this->render('backoffice/contract/financial_report.html.twig', [
            'contract' => $contract,
            'financial_data' => $financialData,
        ]);
    }

    #[Route('/reports', name: 'app_backoffice_contract_reports')]
    public function reports(ContractRepository $repository): Response
    {
        $user = $this->getUser();

        // Rapport global pour l'utilisateur
        $userContracts = $repository->findForUser($user);
        
        $reports = [
            'total_contracts' => count($userContracts),
            'active_contracts' => count(array_filter($userContracts, fn($c) => $c->isActive())),
            'average_commission' => $this->calculateAverageCommission($userContracts),
            'contracts_by_status' => $this->groupByStatus($userContracts),
            'expiring_soon' => $repository->findExpiringContracts(30),
            'recent_signed' => array_slice(
                array_filter($userContracts, fn($c) => $c->getSignedBy() !== null),
                0, 5
            ),
        ];

        return $this->render('backoffice/contract/reports.html.twig', [
            'reports' => $reports,
        ]);
    }

    private function calculateTotalCommission(mixed $user, ContractRepository $repository): float
    {
        $contracts = $repository->findForUser($user);
        $total = 0;
        foreach ($contracts as $contract) {
            $total += $contract->getCommissionRate();
        }
        return count($contracts) > 0 ? $total / count($contracts) : 0;
    }

    private function calculateAverageCommission(array $contracts): float
    {
        if (empty($contracts)) {
            return 0;
        }
        $total = array_sum(array_map(fn($c) => $c->getCommissionRate(), $contracts));
        return $total / count($contracts);
    }

    private function groupByStatus(array $contracts): array
    {
        $grouped = [];
        foreach ($contracts as $contract) {
            $status = $contract->getStatus();
            $grouped[$status] = ($grouped[$status] ?? 0) + 1;
        }
        return $grouped;
    }
}
