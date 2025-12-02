<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Route('/users', name: 'app_user_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('user/dashboard.html.twig', [
            'users' => $users,
        ]);
    }

    // Minimal placeholders to keep links working while respecting permissions
    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET'])]
    public function edit(User $user): Response
    {
        $current = $this->getUser();
        $canEdit = $this->isGranted('ROLE_ADMIN') || ($current instanceof User && $current->getId() === $user->getId());
        if (!$canEdit) {
            throw $this->createAccessDeniedException();
        }

        $this->addFlash('info', "L'édition de l'utilisateur n'est pas encore implémentée.");
        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/users/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user): RedirectResponse
    {
        $this->addFlash('info', "La suppression d'utilisateur n'est pas encore implémentée.");
        return $this->redirectToRoute('app_user_dashboard');
    }
}
