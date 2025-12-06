<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users', name: 'app_admin_user_')]
class UserController extends AbstractController
{
    /**
     * Vérifie si l'utilisateur connecté a les droits administrateur
     */
    private function checkAdminAccess(Request $request, UserRepository $userRepository): ?Response
    {
        $userRole = $request->cookies->get('user_role');
        $userId = $request->cookies->get('user_id');
        
        if (!$userId || $userRole !== 'admin') {
            $this->addFlash('error', 'Accès refusé. Vous devez être administrateur pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        // Vérifier que l'utilisateur a bien le rôle ROLE_ADMIN dans la base
        $currentUser = $userRepository->find($userId);
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès refusé. Droits administrateur requis.');
            return $this->redirectToRoute('app_home');
        }

        return null;
    }

    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function dashboard(UserRepository $userRepository, Request $request): Response
    {
        // Vérifier les droits admin
        if ($response = $this->checkAdminAccess($request, $userRepository)) {
            return $response;
        }

        // Get all users from the database
        $users = $userRepository->findAll();

        return $this->render('admin/user/dashboard.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, \Doctrine\ORM\EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        // Vérifier les droits admin
        if ($response = $this->checkAdminAccess($request, $userRepository)) {
            return $response;
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role');
            $isVerified = $request->request->get('status') == '1';

            // Create new user
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setRoles([$role]);
            $user->setIsVerified($isVerified);
            
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf('User "%s" created successfully.', $user->getName()));
            return $this->redirectToRoute('app_admin_user_dashboard');
        }

        return $this->render('admin/user/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, UserRepository $userRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        // Vérifier les droits admin
        if ($response = $this->checkAdminAccess($request, $userRepository)) {
            return $response;
        }

        $user = $userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_user_dashboard');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $role = $request->request->get('role');
            $isVerified = $request->request->get('status') == '1';

            $user->setName($name);
            $user->setEmail($email);
            $user->setRoles([$role]);
            $user->setIsVerified($isVerified);

            $entityManager->flush();
            
            $this->addFlash('success', sprintf('User "%s" updated successfully.', $user->getName()));
            return $this->redirectToRoute('app_admin_user_dashboard');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id, UserRepository $userRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        // Vérifier les droits admin
        if ($response = $this->checkAdminAccess($request, $userRepository)) {
            return $response;
        }

        $user = $userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_user_dashboard');
        }

        $userName = $user->getName();
        $entityManager->remove($user);
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('User "%s" deleted successfully.', $userName));
        return $this->redirectToRoute('app_admin_user_dashboard');
    }
}
