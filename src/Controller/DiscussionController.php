<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/discussion')]
class DiscussionController extends AbstractController
{
    #[Route('/', name: 'app_discussion_index', methods: ['GET'])]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    #[Route('/new', name: 'app_discussion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $discussion = new Discussion();
        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Créer le premier message si du contenu est fourni
            $contenu = $discussion->getContenu();
            if ($contenu) {
                $message = new Message();
                $message->setContenu($contenu);
                $message->setAuteur($discussion->getInitiateur());
                $message->setDiscussion($discussion);
                $discussion->addMessage($message);
            }

            $entityManager->persist($discussion);
            $entityManager->flush();

            $this->addFlash('success', 'La discussion a été créée avec succès.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/new.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_discussion_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        // Ajouter un nouveau message
        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('message');
            if ($contenu) {
                $message = new Message();
                $message->setContenu($contenu);
                $message->setAuteur($discussion->getInitiateur()); // À adapter selon l'utilisateur connecté
                $message->setDiscussion($discussion);
                
                $entityManager->persist($message);
                $entityManager->flush();

                $this->addFlash('success', 'Message envoyé avec succès.');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
            }
        }

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discussion->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'La discussion a été modifiée avec succès.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]
    public function delete(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$discussion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($discussion);
            $entityManager->flush();
            $this->addFlash('success', 'La discussion a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_discussion_index');
    }

    #[Route('/{id}/close', name: 'app_discussion_close', methods: ['POST'])]
    public function close(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('close'.$discussion->getId(), $request->request->get('_token'))) {
            $discussion->setStatut(Discussion::STATUT_TERMINEE);
            $discussion->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'La discussion a été fermée avec succès.');
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/reopen', name: 'app_discussion_reopen', methods: ['POST'])]
    public function reopen(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reopen'.$discussion->getId(), $request->request->get('_token'))) {
            $discussion->setStatut(Discussion::STATUT_EN_COURS);
            $discussion->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'La discussion a été réouverte avec succès.');
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }
}
