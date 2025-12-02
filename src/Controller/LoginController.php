<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        return $this->render('User/loginpage.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }

    #[Route('/homepage', name: 'app_homepage', methods: ['GET'])]
    public function homepage(): Response
    {
        // Check if user has ROLE_ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_user_index');
        }
        
        return $this->render('User/homepage.html.twig');
    }

    #[Route('/users', name: 'app_user_index', methods: ['GET'])]
    public function userIndex(EntityManagerInterface $entityManager): Response
    {
        // Only admins can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all users from database
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('User/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            
            // Update user information
            if ($name) {
                $user->setName($name);
            }
            if ($email) {
                $user->setEmail($email);
            }
            // Only update password if provided
            if ($password && !empty($password)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            
            // Save changes
            $entityManager->flush();
            
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }
        
        return $this->render('User/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            // Create new user
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Save to database
            $entityManager->persist($user);
            $entityManager->flush();

            // Redirect to login page with success message
            $this->addFlash('success', 'Account created successfully! Please log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('User/singinpage.html.twig');
    }

    #[Route('/admin/user/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function adminCreateUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role', 'ROLE_USER');

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setRoles([$role]);
            
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('User/admin_form.html.twig', [
            'user' => null,
            'action' => 'Create',
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function adminEditUser(
        int $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role', 'ROLE_USER');

            $user->setName($name);
            $user->setEmail($email);
            $user->setRoles([$role]);
            
            if ($password && !empty($password)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('User/admin_form.html.twig', [
            'user' => $user,
            'action' => 'Edit',
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function adminDeleteUser(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Prevent admin from deleting themselves
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own account!');
            return $this->redirectToRoute('app_user_index');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully!');
        return $this->redirectToRoute('app_user_index');
    }
}