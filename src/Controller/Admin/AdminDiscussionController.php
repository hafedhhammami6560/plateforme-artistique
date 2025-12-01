<?php

namespace App\Controller\Admin;

use App\Entity\Discussion;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/discussion')]
#[IsGranted('ROLE_ADMIN')]
class AdminDiscussionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DiscussionRepository $discussionRepository
    ) {
    }

    /**
     * Liste toutes les discussions (vue admin)
     */
    #[Route('/', name: 'app_admin_discussion_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statusFilter = $request->query->get('status');
        $searchQuery = $request->query->get('q');

        if ($searchQuery) {
            $discussions = $this->discussionRepository->search($searchQuery);
        } elseif ($statusFilter) {
            $discussions = $this->discussionRepository->findByStatus($statusFilter);
        } else {
            $discussions = $this->discussionRepository->findAll();
        }

        $stats = $this->discussionRepository->countByStatus();

        return $this->render('admin/discussion/index.html.twig', [
            'discussions' => $discussions,
            'stats' => $stats,
            'currentFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Voir les détails d'une discussion (vue admin)
     */
    #[Route('/{id}', name: 'app_admin_discussion_show', methods: ['GET'])]
    public function show(Discussion $discussion): Response
    {
        return $this->render('admin/discussion/show.html.twig', [
            'discussion' => $discussion,
        ]);
    }

    /**
     * Éditer une discussion (admin)
     */
    #[Route('/{id}/edit', name: 'app_admin_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion): Response
    {
        $form = $this->createForm(DiscussionType::class, $discussion, [
            'show_initial_message' => false,
            'show_artist_field' => true,
            'show_publisher_field' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été mise à jour.');

            return $this->redirectToRoute('app_admin_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('admin/discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer une discussion (admin)
     */
    #[Route('/{id}/delete', name: 'app_admin_discussion_delete', methods: ['POST'])]
    public function delete(Request $request, Discussion $discussion): Response
    {
        if ($this->isCsrfTokenValid('delete' . $discussion->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($discussion);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été supprimée.');
        }

        return $this->redirectToRoute('app_admin_discussion_index');
    }

    /**
     * Changer le statut d'une discussion
     */
    #[Route('/{id}/status/{status}', name: 'app_admin_discussion_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Discussion $discussion, string $status): Response
    {
        if ($this->isCsrfTokenValid('status' . $discussion->getId(), $request->request->get('_token'))) {
            $validStatuses = [
                Discussion::STATUS_PENDING,
                Discussion::STATUS_ACTIVE,
                Discussion::STATUS_CLOSED,
                Discussion::STATUS_ARCHIVED
            ];

            if (in_array($status, $validStatuses)) {
                $discussion->setStatus($status);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le statut de la discussion a été modifié.');
            } else {
                $this->addFlash('error', 'Statut invalide.');
            }
        }

        return $this->redirectToRoute('app_admin_discussion_show', ['id' => $discussion->getId()]);
    }

    /**
     * Archiver une discussion
     */
    #[Route('/{id}/archive', name: 'app_admin_discussion_archive', methods: ['POST'])]
    public function archive(Request $request, Discussion $discussion): Response
    {
        if ($this->isCsrfTokenValid('archive' . $discussion->getId(), $request->request->get('_token'))) {
            $discussion->setStatus(Discussion::STATUS_ARCHIVED);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été archivée.');
        }

        return $this->redirectToRoute('app_admin_discussion_index');
    }
}
