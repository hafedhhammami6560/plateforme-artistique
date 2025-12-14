<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\User;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use App\Service\DiscussionService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/discussion')]
class DiscussionController extends AbstractController
{
    public function __construct(
        private DiscussionService $discussionService,
        private PermissionService $permissionService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/', name: 'app_discussion_index', methods: ['GET'])]
    public function index(Request $request, DiscussionRepository $repo, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Get filter parameters
        $search = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        $statutFilter = $request->query->get('statut', '');
        $sortBy = $request->query->get('sort', 'date_desc');

        // Only show discussions where user is participant (not admin dashboard)
        $discussions = $repo->createQueryBuilder('d')
            ->where('(d.initiateur = :user AND d.hiddenByInitiateur = false) OR (d.destinataire = :user AND d.hiddenByDestinataire = false)')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statutFilter' => $statutFilter,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/new', name: 'app_discussion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Check if user can create discussions
        if (!$this->permissionService->canCreateDiscussion($user)) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de créer des discussions.');
            return $this->redirectToRoute('app_discussion_index');
        }

        $discussion = new Discussion();

        $form = $this->createForm(DiscussionType::class, $discussion, [
            'user' => $user,
            'permission_service' => $this->permissionService
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $messageInitial = $form->get('messageInitial')->getData();
                $destinataire = $discussion->getDestinataire();

                // Déterminer automatiquement le type selon l'initiateur et le destinataire
                $type = $this->permissionService->determineDiscussionType($user, $destinataire);
                $discussion->setType($type);

                // Verify user can create this type
                if (!$this->permissionService->canCreateDiscussionType($user, $type)) {
                    $this->addFlash('error', 'Vous n\'avez pas la permission de créer ce type de discussion.');
                    return $this->render('discussion/new.html.twig', [
                        'discussion' => $discussion,
                        'form' => $form->createView(),
                    ]);
                }

                // Create discussion based on type
                if ($type === Discussion::TYPE_PUBLICATION_RIGHTS) {
                    $project = $discussion->getproject();
                    if (!$project) {
                        $this->addFlash('error', 'Un project est requis pour les discussions de type Publication Rights.');
                        return $this->render('discussion/new.html.twig', [
                            'discussion' => $discussion,
                            'form' => $form->createView(),
                        ]);
                    }
                    $discussion = $this->discussionService->creerDiscussionTypeA(
                        $user,
                        $destinataire,
                        $project,
                        $discussion->getTitre(),
                        $messageInitial
                    );
                } else {
                    $discussion = $this->discussionService->creerDiscussionTypeB(
                        $user,
                        $destinataire,
                        $discussion->getTitre(),
                        $messageInitial
                    );
                }

                $this->addFlash('success', 'Discussion créée avec succès !');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la discussion : ' . $e->getMessage());
            }
        }

        return $this->render('discussion/new.html.twig', [
            'discussion' => $discussion,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_discussion_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Discussion $discussion, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Only allow user to view if they are participant
        $canView = $discussion->getInitiateur()->getId() === $user->getId() || $discussion->getDestinataire()->getId() === $user->getId();
        if (!$canView) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette discussion.');
            return $this->redirectToRoute('app_discussion_index');
        }

        // Handle message submission
        if ($request->isMethod('POST')) {
            $messageContent = $request->request->get('message');

            if ($messageContent) {
                try {
                    if (!$this->permissionService->canSendMessage($user, $discussion)) {
                        $this->addFlash('error', 'Vous ne pouvez pas envoyer de message dans cette discussion.');
                    } else {
                        $this->discussionService->ajouterMessage($discussion, $user, $messageContent);
                        $this->addFlash('success', 'Message envoyé avec succès !');
                        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi du message : ' . $e->getMessage());
                }
            }
        }

        // Mark messages as read
        try {
            $this->discussionService->marquerMessagesLus($discussion, $user);
        } catch (\Exception $e) {
            // Silent fail for read marking
        }

        // Récupérer tous les brouillons de contrats liés à cette discussion
        $contratRepo = $this->em->getRepository(\App\Entity\Contrat::class);
        $brouillons = $contratRepo->findBy(
            ['discussionOrigine' => $discussion],
            ['createdAt' => 'DESC']
        );

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'user' => $user,
            'brouillons' => $brouillons,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Only allow user to edit if they are participant
        $canEdit = $discussion->getInitiateur()->getId() === $user->getId() || $discussion->getDestinataire()->getId() === $user->getId();
        if (!$canEdit) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette discussion.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        $form = $this->createForm(DiscussionType::class, $discussion, [
            'user' => $user,
            'permission_service' => $this->permissionService
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $discussion->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();

                $this->addFlash('success', 'Discussion modifiée avec succès !');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/terminer', name: 'app_discussion_terminer', methods: ['POST'])]
    public function terminer(Request $request, Discussion $discussion, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Check if user can terminate this discussion (must be participant)
        if ($discussion->getInitiateur()->getId() !== $user->getId() &&
            $discussion->getDestinataire()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de terminer cette discussion.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        // Verify CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('terminer' . $discussion->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        try {
            $discussion->setStatut(Discussion::STATUT_TERMINEE);
            $this->em->flush();

            $this->addFlash('success', 'Discussion terminée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la fermeture de la discussion : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/hide', name: 'app_discussion_hide', methods: ['POST'])]
    public function hide(Request $request, Discussion $discussion, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier que l'utilisateur participe à la discussion
        if ($discussion->getInitiateur() !== $user && $discussion->getDestinataire() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas masquer cette discussion.');
            return $this->redirectToRoute('app_discussion_index');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('hide_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        try {
            // Masquer la discussion pour cet utilisateur (soft delete)
            $discussion->hideForUser($user);
            $this->em->flush();

            $this->addFlash('success', 'Discussion masquée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du masquage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_discussion_index');
    }

    #[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]
    public function delete(Request $request, Discussion $discussion, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Remove admin-only delete: users cannot delete discussions, only hide/soft-delete if needed
        $this->addFlash('error', 'La suppression définitive est réservée à l\'administration.');
        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        try {
            $this->em->remove($discussion);
            $this->em->flush();

            $this->addFlash('success', 'Discussion supprimée définitivement avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_discussion_index');
    }
}
