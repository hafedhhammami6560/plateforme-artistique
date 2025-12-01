<?php

namespace App\Controller\Admin;

use App\Repository\ContractRepository;
use App\Repository\DiscussionRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private DiscussionRepository $discussionRepository,
        private ContractRepository $contractRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        // Récupérer les statistiques globales
        $discussionStats = [
            'total' => count($this->discussionRepository->findAll()),
            'active' => count($this->discussionRepository->findActiveDiscussions()),
            'byStatus' => $this->discussionRepository->countByStatus(),
            'conversionRate' => $this->discussionRepository->getConversionRate(),
        ];

        $contractStats = $this->contractRepository->getGlobalStats();
        $contractStats['byStatus'] = $this->contractRepository->countByStatus();
        $contractStats['commissionStats'] = $this->contractRepository->getCommissionStats();

        $messageStats = $this->messageRepository->getGlobalStats();

        $userStats = [
            'artists' => count($this->userRepository->findArtists()),
            'publishers' => count($this->userRepository->findPublishers()),
            'total' => count($this->userRepository->findAll()),
        ];

        // Récupérer les activités récentes
        $recentDiscussions = $this->discussionRepository->findRecent(5);
        $recentContracts = $this->contractRepository->findRecent(5);
        $recentMessages = $this->messageRepository->findRecent(10);

        // Contrats expirant bientôt
        $expiringContracts = $this->contractRepository->findExpiringContracts(30);

        // Discussions sans réponse
        $staleDiscussions = $this->discussionRepository->findStaleDiscussions(7);

        return $this->render('admin/dashboard/index.html.twig', [
            'discussionStats' => $discussionStats,
            'contractStats' => $contractStats,
            'messageStats' => $messageStats,
            'userStats' => $userStats,
            'recentDiscussions' => $recentDiscussions,
            'recentContracts' => $recentContracts,
            'recentMessages' => $recentMessages,
            'expiringContracts' => $expiringContracts,
            'staleDiscussions' => $staleDiscussions,
        ]);
    }
}
