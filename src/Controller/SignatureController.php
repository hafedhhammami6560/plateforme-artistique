<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Repository\UserRepository;
use App\Service\ElectronicSignatureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signature')]
class SignatureController extends AbstractController
{
    public function __construct(
        private ElectronicSignatureService $signatureService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/contrat/{id}/signer', name: 'app_signature_sign', methods: ['POST'])]
    public function signContract(
        Request $request,
        Contrat $contrat,
        UserRepository $userRepo
    ): Response {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour signer un contrat.');
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier que l'utilisateur est partie prenante du contrat
        if ($contrat->getArtiste()->getId() !== $user->getId() && 
            $contrat->getProducteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à signer ce contrat.');
            return $this->redirectToRoute('app_contrat_index');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('sign_contract_' . $contrat->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        try {
            // Récupérer l'adresse IP et le user agent
            $ipAddress = $request->getClientIp() ?? '0.0.0.0';
            $userAgent = $request->headers->get('User-Agent');

            // Signer le contrat
            $signature = $this->signatureService->signContract(
                $contrat,
                $user,
                $ipAddress,
                $userAgent
            );

            $this->addFlash('success', 'Contrat signé avec succès ! Votre signature électronique a été enregistrée.');

            // Si le contrat est entièrement signé, générer le certificat
            if ($contrat->getStatut() === Contrat::STATUT_SIGNE) {
                try {
                    $certificate = $this->signatureService->generateSignatureCertificate($contrat);
                    $this->addFlash('success', sprintf(
                        'Certificat de signature généré : %s',
                        $certificate->getCertificateNumber()
                    ));
                } catch (\Exception $e) {
                    // Erreur de génération du certificat (non bloquante)
                    $this->addFlash('warning', 'Le contrat est signé mais le certificat n\'a pas pu être généré.');
                }
            }

        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la signature du contrat : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }

    #[Route('/contrat/{id}/historique', name: 'app_signature_history', methods: ['GET'])]
    public function signatureHistory(
        Request $request,
        Contrat $contrat,
        UserRepository $userRepo
    ): Response {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier que l'utilisateur est partie prenante du contrat
        if ($contrat->getArtiste()->getId() !== $user->getId() && 
            $contrat->getProducteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir l\'historique de ce contrat.');
            return $this->redirectToRoute('app_contrat_index');
        }

        // Récupérer l'historique
        $history = $this->signatureService->getSignatureHistory($contrat);

        return $this->render('signature/history.html.twig', [
            'contrat' => $contrat,
            'history' => $history,
        ]);
    }

    #[Route('/demande-signature/{id}', name: 'app_signature_request', methods: ['POST'])]
    public function requestSignature(
        Request $request,
        Contrat $contrat,
        UserRepository $userRepo
    ): Response {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier que l'utilisateur est partie prenante du contrat
        if ($contrat->getArtiste()->getId() !== $user->getId() && 
            $contrat->getProducteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à envoyer une demande pour ce contrat.');
            return $this->redirectToRoute('app_contrat_index');
        }

        // Déterminer à qui envoyer la demande
        $recipient = $contrat->getArtiste()->getId() === $user->getId() 
            ? $contrat->getProducteur() 
            : $contrat->getArtiste();

        try {
            $success = $this->signatureService->sendSignatureRequest($contrat, $recipient);
            
            if ($success) {
                $this->addFlash('success', sprintf(
                    'Demande de signature envoyée à %s (%s)',
                    $recipient->getName(),
                    $recipient->getEmail()
                ));
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi de la demande de signature.');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
}
