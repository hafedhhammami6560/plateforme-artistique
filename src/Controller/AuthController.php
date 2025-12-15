<?php
/**
 * Contrôleur AuthController
 *
 * Gère l'authentification avec base de données
 */
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[Route('/auth')]
class AuthController extends AbstractController
{
    /**
     * Page de connexion
     */
    #[Route('/login', name: 'auth_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Always have a captcha ready for the form
        $captchaQuestion = $this->ensureCaptcha($session);

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username', '');
            $password = $request->request->get('password', '');
            $captchaAnswer = trim((string) $request->request->get('captcha_answer', ''));
            $expectedCaptcha = (string) $session->get('login_captcha_answer', '');

            if ($captchaAnswer === '' || strcasecmp($captchaAnswer, $expectedCaptcha) !== 0) {
                $this->addFlash('error', 'Captcha incorrect.');
                $captchaQuestion = $this->refreshCaptcha($session);
                return $this->render('home/login_page.html.twig', [
                    'captchaQuestion' => $captchaQuestion,
                ]);
            }

            // Debug
            error_log("Login attempt - Username: " . $username);

            // Chercher l'utilisateur dans la base de données
            $user = $userRepository->findOneBy(['email' => $username]);

            if (!$user) {
                $user = $userRepository->findOneBy(['name' => $username]);
            }

            // Debug
            if ($user) {
                error_log("User found: " . $user->getEmail());
                $isValid = $passwordHasher->isPasswordValid($user, $password);
                error_log("Password valid: " . ($isValid ? 'YES' : 'NO'));
            } else {
                error_log("User not found");
            }

            if ($user && $passwordHasher->isPasswordValid($user, $password)) {
                // Authentifier l'utilisateur dans Symfony
                $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                // store token in token storage
                $this->container->get('security.token_storage')->setToken($token);
                // persist token in session so it survives the redirect
                $request->getSession()->set('_security_main', serialize($token));
                // regenerate session id to prevent fixation
                $request->getSession()->migrate(true);

                // Déterminer le rôle et la redirection
                $roles = $user->getRoles();
                $isAdmin = in_array('ROLE_ADMIN', $roles);

                // Rediriger selon le rôle
                if ($isAdmin) {
                    $response = $this->redirectToRoute('app_admin_user_dashboard');
                    $cookieRole = 'admin';
                    $this->addFlash('success', 'Bienvenue administrateur !');
                } else {
                    $response = $this->redirectToRoute('app_home');
                    $cookieRole = 'client';
                    $this->addFlash('success', 'Connexion réussie !');
                }

                // Créer des cookies pour stocker l'ID utilisateur et le rôle
                $userIdCookie = Cookie::create('user_id')
                    ->withValue((string)$user->getId())
                    ->withExpires(time() + 3600)
                    ->withPath('/')
                    ->withSecure(false)
                    ->withHttpOnly(true);

                $roleCookie = Cookie::create('user_role')
                    ->withValue($cookieRole)
                    ->withExpires(time() + 3600)
                    ->withPath('/')
                    ->withSecure(false)
                    ->withHttpOnly(true);

                $response->headers->setCookie($userIdCookie);
                $response->headers->setCookie($roleCookie);

                return $response;
            } else {
                $this->addFlash('error', 'Identifiants incorrects.');
            }
        }

        return $this->render('home/login_page.html.twig', [
            'captchaQuestion' => $captchaQuestion,
        ]);
    }

    /**
     * Crée un captcha simple (addition) et le stocke en session.
     */
    private function refreshCaptcha(SessionInterface $session): string
    {
        $code = $this->generateCaptchaCode();
        $session->set('login_captcha_answer', $code);
        $session->set('login_captcha_question', $this->generateCaptchaImage($code));

        return (string) $session->get('login_captcha_question');
    }

    /**
     * Signup captcha helpers (reuse generator but separate session keys)
     */
    private function refreshSignupCaptcha(SessionInterface $session): string
    {
        $code = $this->generateCaptchaCode();
        $session->set('signup_captcha_answer', $code);
        $session->set('signup_captcha_question', $this->generateCaptchaImage($code));

        return (string) $session->get('signup_captcha_question');
    }

    private function ensureSignupCaptcha(SessionInterface $session): string
    {
        if (!$session->has('signup_captcha_answer') || !$session->has('signup_captcha_question')) {
            return $this->refreshSignupCaptcha($session);
        }

        return (string) $session->get('signup_captcha_question');
    }

    /**
     * Assure qu'une question de captcha est disponible.
     */
    private function ensureCaptcha(SessionInterface $session): string
    {
        if (!$session->has('login_captcha_answer') || !$session->has('login_captcha_question')) {
            return $this->refreshCaptcha($session);
        }

        return (string) $session->get('login_captcha_question');
    }

    /**
     * Génère un code captcha alphanumérique lisible.
     */
    private function generateCaptchaCode(int $length = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sans caractères ambigus
        $code = '';
        $maxIndex = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**
     * Génère une image SVG captcha avec bruit léger (non copiable en texte).
     */
    private function generateCaptchaImage(string $code): string
    {
        $width = 200;
        $height = 60;

        // Positionner chaque caractère avec un léger décalage aléatoire
        $chars = str_split($code);
        $xStart = 20;
        $yBase = 40;
        $xStep = 28;
        $letters = '';
        foreach ($chars as $index => $char) {
            $dx = random_int(-4, 4);
            $dy = random_int(-3, 3);
            $rotation = random_int(-15, 15);
            $letters .= sprintf(
                '<text x="%d" y="%d" transform="rotate(%d %d %d)" font-family="Courier New, monospace" font-size="28" font-weight="bold" fill="#0f172a">%s</text>',
                $xStart + ($xStep * $index) + $dx,
                $yBase + $dy,
                $rotation,
                $xStart + ($xStep * $index) + $dx,
                $yBase + $dy,
                htmlspecialchars($char, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        // Ajouter quelques lignes de bruit
        $noise = '';
        for ($i = 0; $i < 6; $i++) {
            $noise .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1" stroke-opacity="0.55" />',
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height)
            );
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="Captcha">'
            . '<rect width="100%%" height="100%%" fill="#e2e8f0" />%s%s'
            . '</svg>',
            $width,
            $height,
            $width,
            $height,
            $noise,
            $letters
        );

        $base64 = base64_encode($svg);
        return 'data:image/svg+xml;base64,' . $base64;
    }

    /**
     * Page d'inscription
     */
    #[Route('/signup', name: 'auth_signup', methods: ['GET', 'POST'])]
    public function signup(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        // Ensure a captcha for signup form
        $signupCaptchaQuestion = $this->ensureSignupCaptcha($request->getSession());

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name', '');
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');
            $userType = $request->request->get('user_type', '');
            $signupCaptchaAnswer = trim((string) $request->request->get('signup_captcha_answer', ''));
            $expectedSignupCaptcha = (string) $request->getSession()->get('signup_captcha_answer', '');
            $newsletter = $request->request->get('newsletter') ? true : false;

            // Valider les données
            if (empty($name) || empty($email) || empty($password) || empty($userType)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('home/singinpage.html.twig');
            }

            // Validate signup captcha
            if ($signupCaptchaAnswer === '' || strcasecmp($signupCaptchaAnswer, $expectedSignupCaptcha) !== 0) {
                $this->addFlash('error', 'Captcha d\'inscription incorrect.');
                // refresh captcha
                $signupCaptchaQuestion = $this->refreshSignupCaptcha($request->getSession());
                return $this->render('home/singinpage.html.twig', [
                    'signupCaptchaQuestion' => $signupCaptchaQuestion,
                ]);
            }

            // Vérifier si l'email existe déjà
            $existingUser = $userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('home/singinpage.html.twig');
            }

            // Créer un nouveau utilisateur avec le rôle CLIENT
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setUserType($userType);
            $user->setNewsletterSubscribed($newsletter);
            $user->setRoles(['ROLE_CLIENT']); // Rôle client par défaut
            $user->setIsVerified(true); // Auto-vérifier pour simplifier

            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Sauvegarder dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Create account successfully! You can now login.');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('home/singinpage.html.twig');
    }

    /**
     * Déconnexion
     */
    #[Route('/logout', name: 'auth_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        // Invalider le token de sécurité
        if ($this->container->has('security.token_storage')) {
            $this->container->get('security.token_storage')->setToken(null);
        }

        // Invalider la session pour éviter la persistance de l'utilisateur
        $request->getSession()->invalidate();

        // Redirect user to the login page after logout
        $response = $this->redirectToRoute('auth_login');

        // Supprimer les cookies
        $response->headers->clearCookie('user_role', '/');
        $response->headers->clearCookie('user_id', '/');

        $this->addFlash('success', 'Vous avez été déconnecté avec succès.');
        return $response;
    }

    /**
     * Link to this controller to start the "connect" process with Google
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'profile', 'email' // the scopes you want to access
            ]);
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(Request $request, ClientRegistry $clientRegistry, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $client = $clientRegistry->getClient('google');

        try {
            // Get the access token
            $accessToken = $client->getAccessToken();

            // Get user information from Google
            $googleUser = $client->fetchUserFromToken($accessToken);

            $email = $googleUser->getEmail();
            $name = $googleUser->getName();

            // Check if user already exists
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setName($name);
                $user->setRoles(['ROLE_CLIENT']);
                $user->setIsVerified(true);
                $user->setPassword(''); // No password for OAuth users

                $entityManager->persist($user);
                $entityManager->flush();
            }

            // Authentifier l'utilisateur dans Symfony
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            // store token in token storage
            $this->container->get('security.token_storage')->setToken($token);
            // persist token in session so it survives the redirect
            $request->getSession()->set('_security_main', serialize($token));
            // regenerate session id to prevent fixation
            $request->getSession()->migrate(true);

            // Determine role and redirection
            $roles = $user->getRoles();
            $isAdmin = in_array('ROLE_ADMIN', $roles);

            if ($isAdmin) {
                $response = $this->redirectToRoute('app_admin_user_dashboard');
                $cookieRole = 'admin';
                $this->addFlash('success', 'Bienvenue administrateur !');
            } else {
                $response = $this->redirectToRoute('app_home');
                $cookieRole = 'client';
                $this->addFlash('success', 'Connexion réussie avec Google !');
            }

            // Create cookies
            $userIdCookie = Cookie::create('user_id')
                ->withValue((string)$user->getId())
                ->withExpires(time() + 3600)
                ->withPath('/')
                ->withSecure(false)
                ->withHttpOnly(true);

            $roleCookie = Cookie::create('user_role')
                ->withValue($cookieRole)
                ->withExpires(time() + 3600)
                ->withPath('/')
                ->withSecure(false)
                ->withHttpOnly(true);

            $response->headers->setCookie($userIdCookie);
            $response->headers->setCookie($roleCookie);

            return $response;

        } catch (IdentityProviderException $e) {
            // Something went wrong!
            $this->addFlash('error', 'Erreur lors de la connexion avec Google.');
            return $this->redirectToRoute('auth_login');
        }
    }

    /**
     * Forgot Password - Request reset link
     */
    #[Route('/forgot-password', name: 'auth_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a secure reset token
                $resetToken = bin2hex(random_bytes(32));
                $expiresAt = new \DateTime('+1 hour');

                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt($expiresAt);
                $entityManager->flush();

                // Send reset email using Mailtrap
                $resetUrl = $this->generateUrl('auth_reset_password', ['token' => $resetToken], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $emailContent = $this->renderView('email/reset_password.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl,
                        'expiresAt' => $expiresAt
                    ]);

                    $mailtrapEmail = (new MailtrapEmail())
                        ->from(new Address('noreply@plateforme-artistique.com', 'Plateforme Artistique'))
                        ->to(new Address($user->getEmail(), $user->getName()))
                        ->subject('Réinitialisation de votre mot de passe')
                        ->html($emailContent)
                        ->category('Password Reset');

                    // Use sandbox mode for testing
                    $mailtrapClient = MailtrapClient::initSendingEmails(
                        apiKey: $_ENV['MAILTRAP_API_TOKEN'],
                        isSandbox: true, // Enable sandbox mode
                        inboxId: (int)$_ENV['MAILTRAP_INBOX_ID'] // Your inbox ID
                    );

                    $response = $mailtrapClient->send($mailtrapEmail);

                    $this->addFlash('success', 'Un email de réinitialisation a été envoyé à votre adresse.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                    error_log('Mailtrap error: ' . $e->getMessage());
                }
            } else {
                // Don't reveal if email exists or not for security
                $this->addFlash('success', 'Si cette adresse existe, un email de réinitialisation a été envoyé.');
            }

            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    /**
     * Reset Password - Validate token and set new password
     */
    #[Route('/reset-password/{token}', name: 'auth_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('auth_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('auth_reset_password', ['token' => $token]);
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('auth_reset_password', ['token' => $token]);
            }

            // Hash and set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Clear reset token
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token
        ]);
    }
}
