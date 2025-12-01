<?php

namespace App\Controller\Admin;

use App\Entity\Contract;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/contract')]
#[IsGranted('ROLE_ADMIN')]
class AdminContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContractRepository $contractRepository
    ) {
    }

    /**
     * Liste tous les contrats (vue admin)
     */
    #[Route('/', name: 'app_admin_contract_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statusFilter = $request->query->get('status');
        $searchQuery = $request->query->get('q');

        if ($searchQuery) {
            $contracts = $this->contractRepository->search($searchQuery);
        } elseif ($statusFilter) {
            $contracts = $this->contractRepository->findByStatus($statusFilter);
        } else {
            $contracts = $this->contractRepository->findAll();
        }

        $stats = $this->contractRepository->getGlobalStats();
        $stats['byStatus'] = $this->contractRepository->countByStatus();

        return $this->render('admin/contract/index.html.twig', [
            'contracts' => $contracts,
            'stats' => $stats,
            'currentFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Voir les détails d'un contrat (vue admin)
     */
    #[Route('/{id}', name: 'app_admin_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        return $this->render('admin/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    /**
     * Éditer un contrat (admin)
     */
    #[Route('/{id}/edit', name: 'app_admin_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract): Response
    {
        $form = $this->createForm(ContractType::class, $contract, [
            'show_discussion_field' => false,
            'show_status_field' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été mis à jour.');

            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('admin/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer un contrat (admin)
     */
    #[Route('/{id}/delete', name: 'app_admin_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contract->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($contract);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_contract_index');
    }

    /**
     * Changer le statut d'un contrat
     */
    #[Route('/{id}/status/{status}', name: 'app_admin_contract_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Contract $contract, string $status): Response
    {
        if ($this->isCsrfTokenValid('status' . $contract->getId(), $request->request->get('_token'))) {
            $validStatuses = [
                Contract::STATUS_DRAFT,
                Contract::STATUS_PROPOSED,
                Contract::STATUS_SIGNED,
                Contract::STATUS_ACTIVE,
                Contract::STATUS_TERMINATED
            ];

            if (in_array($status, $validStatuses)) {
                $contract->setStatus($status);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le statut du contrat a été modifié.');
            } else {
                $this->addFlash('error', 'Statut invalide.');
            }
        }

        return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
    }

    /**
     * Annuler un contrat
     */
    #[Route('/{id}/cancel', name: 'app_admin_contract_cancel', methods: ['POST'])]
    public function cancel(Request $request, Contract $contract): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $contract->getId(), $request->request->get('_token'))) {
            $contract->setStatus(Contract::STATUS_TERMINATED);
            $contract->setNotes($contract->getNotes() . "\n[ADMIN] Contrat annulé le " . date('Y-m-d H:i:s'));
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été annulé.');
        }

        return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
    }

    /**
     * Rapport sur les contrats expirés
     */
    #[Route('/reports/expired', name: 'app_admin_contract_report_expired', methods: ['GET'])]
    public function reportExpired(): Response
    {
        $expiredContracts = $this->contractRepository->findExpiredNotTerminated();

        return $this->render('admin/contract/report_expired.html.twig', [
            'contracts' => $expiredContracts,
        ]);
    }

    /**
     * Rapport sur les contrats par commission
     */
    #[Route('/reports/commission', name: 'app_admin_contract_report_commission', methods: ['GET'])]
    public function reportCommission(): Response
    {
        $commissionStats = $this->contractRepository->getCommissionStats();
        $averageCommission = $this->contractRepository->getAverageCommission();
        $highCommissionContracts = $this->contractRepository->findByMinCommission(20);

        return $this->render('admin/contract/report_commission.html.twig', [
            'commissionStats' => $commissionStats,
            'averageCommission' => $averageCommission,
            'highCommissionContracts' => $highCommissionContracts,
        ]);
    }
}
