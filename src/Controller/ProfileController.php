<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Update name
            if ($name && $name !== $user->getName()) {
                $user->setName($name);
            }

            // Update email
            if ($email && $email !== $user->getEmail()) {
                // Check if email already exists
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                    return $this->redirectToRoute('app_profile_edit');
                }
                $user->setEmail($email);
            }

            // Update signature électronique if provided
            $signatureData = $request->request->get('signature_electronique');
            if ($signatureData !== null) {
                if (!empty($signatureData)) {
                    // Valider que c'est bien une image base64
                    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signatureData)) {
                        $user->setSignatureElectronique($signatureData);
                        $this->addFlash('success', 'Votre signature électronique a été enregistrée.');
                    } else {
                        $this->addFlash('error', 'Format de signature invalide.');
                    }
                } elseif ($request->request->has('clear_signature')) {
                    $user->setSignatureElectronique(null);
                    $user->setSignatureCreatedAt(null);
                    $this->addFlash('success', 'Votre signature électronique a été supprimée.');
                }
            }

            // Update password if provided
            if ($newPassword) {
                if (!$currentPassword) {
                    $this->addFlash('error', 'Veuillez entrer votre mot de passe actuel.');
                    return $this->redirectToRoute('app_profile_edit');
                }

                // Verify current password
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile_edit');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_profile_edit');
                }

                if (strlen($newPassword) < 6) {
                    $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
                    return $this->redirectToRoute('app_profile_edit');
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }
}
