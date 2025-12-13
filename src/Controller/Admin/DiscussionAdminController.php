<?php

namespace App\Controller\Admin;

use App\Entity\Discussion;
use App\Entity\User;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/admin/discussion")]
class DiscussionAdminController extends AbstractController
{
    #[Route(path: "/", name: "admin_discussion_dashboard", methods: ["GET"])]
    public function dashboard(DiscussionRepository $discussionRepository, Request $request, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        $discussions = $discussionRepository->findAll();
        return $this->render('admin/discussion/dashboard.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    #[Route(path: "/{id}", name: "admin_discussion_show", methods: ["GET"])]
    public function show(Discussion $discussion, Request $request, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        return $this->render('admin/discussion/show.html.twig', [
            'discussion' => $discussion,
        ]);
    }

    #[Route(path: "/{id}/edit", name: "admin_discussion_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Discussion $discussion, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Discussion modifiée.');
            return $this->redirectToRoute('admin_discussion_dashboard');
        }
        return $this->render('admin/discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: "/{id}/delete", name: "admin_discussion_delete", methods: ["POST"])]
    public function delete(Request $request, Discussion $discussion, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        if ($this->isCsrfTokenValid('delete'.$discussion->getId(), $request->request->get('_token'))) {
            $em->remove($discussion);
            $em->flush();
            $this->addFlash('success', 'Discussion supprimée.');
        }
        return $this->redirectToRoute('admin_discussion_dashboard');
    }
}
