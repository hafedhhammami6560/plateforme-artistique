<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Form\DiscussionType;
use App\Form\MessageType;
use App\Repository\DiscussionRepository;
use App\Security\Voter\DiscussionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/discussion')]
#[IsGranted('ROLE_USER')]
class DiscussionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DiscussionRepository $discussionRepository
    ) {
    }


    /**
     * Liste toutes les discussions de l'utilisateur connecté
     */
    #[Route('/', name: 'app_discussion_index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_index');
        }
        $user = $this->getUser();
        $discussions = $this->discussionRepository->findForUser($user);

        // Statistiques
        $stats = [
            'total' => count($discussions),
            'active' => count(array_filter($discussions, fn($d) => $d->isActive())),
            'closed' => count(array_filter($discussions, fn($d) => $d->isClosed())),
            'withContract' => count(array_filter($discussions, fn($d) => $d->hasContract())),
        ];

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
            'stats' => $stats,
        ]);
    }

    /**
     * Création d'une nouvelle discussion
     */
    #[Route('/new', name: 'app_discussion_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PUBLISHER')]
    public function new(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_index');
        }
        $discussion = new Discussion();
        $user = $this->getUser();
        
        // Le publisher est l'utilisateur connecté
        $discussion->setPublisher($user);
        $discussion->setStatus(Discussion::STATUS_PENDING);

        $form = $this->createForm(DiscussionType::class, $discussion, [
            'show_initial_message' => true,
            'show_artist_field' => true,
            'show_publisher_field' => false,
        ]);

        $form->handleRequest($request);

        // The artist is now selected explicitly by the publisher via radio buttons

        if ($form->isSubmitted() && $form->isValid()) {

            // Créer le message initial
            $initialMessageContent = $form->get('initialMessage')->getData();
            $initialMessage = new Message();
            $initialMessage->setContent($initialMessageContent);
            $initialMessage->setSender($user);
            $initialMessage->setDiscussion($discussion);

            $discussion->addMessage($initialMessage);
            $discussion->setStatus(Discussion::STATUS_ACTIVE);

            $this->entityManager->persist($discussion);
            $this->entityManager->persist($initialMessage);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été créée avec succès.');

            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        // If submitted but invalid, show a helpful error message
        if ($form->isSubmitted() && !$form->isValid()) {
            $errorMessages = [];
            foreach ($form->getErrors(true) as $error) {
                $errorMessages[] = $error->getMessage();
            }
            if (!empty($errorMessages)) {
                $this->addFlash('error', 'Le formulaire contient des erreurs: ' . implode(' | ', $errorMessages));
            }
        }

        return $this->render('discussion/new.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    /**
     * Affiche les détails d'une discussion avec ses messages
     */
    #[Route('/{id}', name: 'app_discussion_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_show', ['id' => $discussion->getId()]);
        }
        // Vérifier que l'utilisateur peut voir cette discussion
        $this->denyAccessUnlessGranted(DiscussionVoter::VIEW, $discussion);

        $user = $this->getUser();

        // Créer le formulaire pour envoyer un nouveau message
        $message = new Message();
        $message->setSender($user);
        $message->setDiscussion($discussion);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que l'utilisateur peut envoyer un message
            $this->denyAccessUnlessGranted(DiscussionVoter::SEND_MESSAGE, $discussion);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre message a été envoyé.');

            // Rediriger pour éviter la resoumission du formulaire
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        // Marquer les messages comme lus pour l'utilisateur actuel
        $discussion->getMessages()->map(function(Message $msg) use ($user) {
            if ($msg->getSender() !== $user && !$msg->isRead()) {
                $msg->markAsRead();
            }
        });
        $this->entityManager->flush();

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    /**
     * Édition d'une discussion
     */
    #[Route('/{id}/edit', name: 'app_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_edit', ['id' => $discussion->getId()]);
        }
        $this->denyAccessUnlessGranted(DiscussionVoter::EDIT, $discussion);

        $form = $this->createForm(DiscussionType::class, $discussion, [
            'show_initial_message' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été mise à jour.');

            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'une discussion
     */
    #[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]
    public function delete(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_delete', ['id' => $discussion->getId()]);
        }
        $this->denyAccessUnlessGranted(DiscussionVoter::DELETE, $discussion);

        if ($this->isCsrfTokenValid('delete' . $discussion->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($discussion);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été supprimée.');
        }

        return $this->redirectToRoute('app_discussion_index');
    }

    /**
     * Fermer une discussion
     */
    #[Route('/{id}/close', name: 'app_discussion_close', methods: ['POST'])]
    public function close(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_change_status', ['id' => $discussion->getId(), 'status' => Discussion::STATUS_CLOSED]);
        }
        $this->denyAccessUnlessGranted(DiscussionVoter::EDIT, $discussion);

        if ($this->isCsrfTokenValid('close' . $discussion->getId(), $request->request->get('_token'))) {
            $discussion->setStatus(Discussion::STATUS_CLOSED);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été fermée.');
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    /**
     * Rouvrir une discussion fermée
     */
    #[Route('/{id}/reopen', name: 'app_discussion_reopen', methods: ['POST'])]
    public function reopen(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_discussion_change_status', ['id' => $discussion->getId(), 'status' => Discussion::STATUS_ACTIVE]);
        }
        $this->denyAccessUnlessGranted(DiscussionVoter::EDIT, $discussion);

        if ($this->isCsrfTokenValid('reopen' . $discussion->getId(), $request->request->get('_token'))) {
            $discussion->setStatus(Discussion::STATUS_ACTIVE);
            $this->entityManager->flush();

            $this->addFlash('success', 'La discussion a été rouverte.');
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }
}
