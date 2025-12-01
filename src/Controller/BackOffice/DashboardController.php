<?php

namespace App\Controller\BackOffice;

use App\Repository\ContractRepository;
use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backoffice')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_backoffice_dashboard')]
    public function index(
        DiscussionRepository $discussionRepository,
        ContractRepository $contractRepository
    ): Response {
        $user = $this->getUser();

        // Statistiques pour l'utilisateur connecté
        $stats = [
            'total_discussions' => count($discussionRepository->findForUser($user)),
            'active_discussions' => count($discussionRepository->findForUser($user, 'active')),
            'pending_discussions' => count($discussionRepository->findForUser($user, 'pending')),
            'total_contracts' => count($contractRepository->findForUser($user)),
            'active_contracts' => count($contractRepository->findForUser($user, 'active')),
            'signed_contracts' => count($contractRepository->findForUser($user, 'signed')),
            'draft_contracts' => count($contractRepository->findForUser($user, 'draft')),
        ];

        // Dernières discussions
        $recentDiscussions = array_slice($discussionRepository->findForUser($user, null, 'recent'), 0, 5);

        // Derniers contrats
        $recentContracts = array_slice($contractRepository->findForUser($user), 0, 5);

        return $this->render('backoffice/dashboard/index.html.twig', [
            'stats' => $stats,
            'recent_discussions' => $recentDiscussions,
            'recent_contracts' => $recentContracts,
        ]);
    }
}
