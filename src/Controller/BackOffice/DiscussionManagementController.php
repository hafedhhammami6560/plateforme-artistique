<?php

namespace App\Controller\BackOffice;

use App\Entity\Discussion;
use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backoffice/discussions')]
#[IsGranted('ROLE_USER')]
class DiscussionManagementController extends AbstractController
{
    #[Route('/', name: 'app_backoffice_discussion_index')]
    public function index(Request $request, DiscussionRepository $repository): Response
    {
        $user = $this->getUser();
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        // Récupérer les discussions de l'utilisateur
        $discussions = $repository->findForUser($user, $status);

        // Filtrer par recherche si fourni
        if ($search) {
            $discussions = array_filter($discussions, function($discussion) use ($search) {
                return stripos($discussion->getProduct()->getTitle(), $search) !== false ||
                       stripos($discussion->getSubject(), $search) !== false;
            });
        }

        // Statistiques
        $stats = [
            'total' => count($repository->findForUser($user)),
            'active' => count($repository->findForUser($user, 'active')),
            'pending' => count($repository->findForUser($user, 'pending')),
            'closed' => count($repository->findForUser($user, 'closed')),
        ];

        return $this->render('backoffice/discussion/index.html.twig', [
            'discussions' => $discussions,
            'stats' => $stats,
            'current_status' => $status,
            'search' => $search,
        ]);
    }

    #[Route('/{id}/analytics', name: 'app_backoffice_discussion_analytics', requirements: ['id' => '\d+'])]
    public function analytics(Discussion $discussion): Response
    {
        $this->denyAccessUnlessGranted('DISCUSSION_VIEW', $discussion);

        // Analyser les métriques de la discussion
        $analytics = [
            'total_messages' => $discussion->getMessageCount(),
            'response_time' => $this->calculateAverageResponseTime($discussion),
            'activity_by_day' => $this->getActivityByDay($discussion),
            'participant_engagement' => [
                'artist' => count(array_filter($discussion->getMessages()->toArray(), 
                    fn($m) => $m->getSender() === $discussion->getArtist())),
                'publisher' => count(array_filter($discussion->getMessages()->toArray(), 
                    fn($m) => $m->getSender() === $discussion->getPublisher())),
            ],
            'contract_proposals' => count(array_filter($discussion->getMessages()->toArray(), 
                fn($m) => $m->isContractProposal())),
        ];

        return $this->render('backoffice/discussion/analytics.html.twig', [
            'discussion' => $discussion,
            'analytics' => $analytics,
        ]);
    }

    private function calculateAverageResponseTime(Discussion $discussion): ?float
    {
        $messages = $discussion->getMessages()->toArray();
        if (count($messages) < 2) {
            return null;
        }

        $responseTimes = [];
        for ($i = 1; $i < count($messages); $i++) {
            $diff = $messages[$i]->getSentAt()->getTimestamp() - $messages[$i-1]->getSentAt()->getTimestamp();
            $responseTimes[] = $diff;
        }

        return array_sum($responseTimes) / count($responseTimes) / 3600; // en heures
    }

    private function getActivityByDay(Discussion $discussion): array
    {
        $activity = [];
        foreach ($discussion->getMessages() as $message) {
            $day = $message->getSentAt()->format('Y-m-d');
            $activity[$day] = ($activity[$day] ?? 0) + 1;
        }
        ksort($activity);
        return $activity;
    }
}
