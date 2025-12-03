<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users', name: 'app_admin_user_')]
class UserController extends AbstractController
{
    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Static users for the "static User module"
        $users = [
            ['id' => 1, 'name' => 'Alice Dupont', 'email' => 'alice@example.com', 'roles' => ['ROLE_USER'], 'isVerified' => true],
            ['id' => 2, 'name' => 'Bob Martin', 'email' => 'bob@example.com', 'roles' => ['ROLE_ADMIN'], 'isVerified' => true],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com', 'roles' => ['ROLE_USER'], 'isVerified' => false],
        ];

        return $this->render('admin/user/dashboard.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', sprintf('User #%d updated (static).', $id));
            return $this->redirectToRoute('app_admin_user_dashboard');
        }

        // Render a minimal edit page or redirect back
        return $this->render('admin/user/edit.html.twig', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->addFlash('success', sprintf('User #%d deleted (static).', $id));
        return $this->redirectToRoute('app_admin_user_dashboard');
    }
}
