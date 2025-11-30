<?php
/**
 * Contrôleur AuthController
 * 
 * Gère l'authentification simple avec cookies pour l'accès administrateur
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
class AuthController extends AbstractController
{
    /**
     * Page de connexion
     */
    #[Route('/login', name: 'auth_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        // Vérifier si déjà connecté
        if ($request->cookies->get('user_role') === 'admin') {
            $this->addFlash('info', 'Vous êtes déjà connecté en tant qu\'administrateur.');
            return $this->redirectToRoute('communite_index');
        }

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username', '');
            $password = $request->request->get('password', '');

            // Vérification simple (à remplacer par un vrai système d'authentification)
            if ($username === 'admin' && $password === 'admin123') {
                $response = $this->redirectToRoute('communite_index');
                
                // Créer un cookie pour stocker le rôle (expire dans 1 heure)
                $cookie = Cookie::create('user_role')
                    ->withValue('admin')
                    ->withExpires(time() + 3600)
                    ->withPath('/')
                    ->withSecure(false)
                    ->withHttpOnly(true);
                
                $response->headers->setCookie($cookie);
                
                $this->addFlash('success', 'Connexion réussie ! Vous avez maintenant les droits administrateur.');
                return $response;
            } else {
                $this->addFlash('error', 'Identifiants incorrects. Utilisez admin/admin123');
            }
        }

        return $this->render('auth/login.html.twig');
    }

    /**
     * Déconnexion
     */
    #[Route('/logout', name: 'auth_logout', methods: ['GET'])]
    public function logout(): Response
    {
        $response = $this->redirectToRoute('communite_index');
        
        // Supprimer le cookie
        $response->headers->clearCookie('user_role', '/');
        
        $this->addFlash('success', 'Vous avez été déconnecté avec succès.');
        return $response;
    }
}
