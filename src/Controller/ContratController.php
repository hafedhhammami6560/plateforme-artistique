<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\User;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Repository\UserRepository;
use App\Service\ContratService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contrat')]
class ContratController extends AbstractController
{
    public function __construct(
        private ContratService $contratService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/', name: 'app_contrat_index', methods: ['GET'])]
    public function index(Request $request, ContratRepository $repo, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Get filter parameters
        $search = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        $statutFilter = $request->query->get('statut', '');
        $sortBy = $request->query->get('sort', 'date_desc');

            // Only show contracts where user is participant (not admin dashboard)
            $contrats = $repo->createQueryBuilder('c')
                ->where('c.artiste = :user OR c.producteur = :user')
                ->setParameter('user', $user)
                ->getQuery()->getResult();

            return $this->render('contrat/index.html.twig', [
                'contrats' => $contrats,
                'search' => $search,
                'typeFilter' => $typeFilter,
                'statutFilter' => $statutFilter,
                'sortBy' => $sortBy,
            ]);
    }

    #[Route('/new', name: 'app_contrat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $contrat = new Contrat();

        // Pre-fill with discussion data if coming from discussion
        $discussionId = $request->query->get('discussion_id') ?? $request->query->get('discussion');
        $fromDiscussion = false;
        $discussion = null;

        if ($discussionId) {
            $discussion = $this->em->getRepository(\App\Entity\Discussion::class)->find($discussionId);
            if ($discussion) {
                // Verify user is participant in discussion
                if ($discussion->getInitiateur()->getId() !== $user->getId() &&
                    $discussion->getDestinataire()->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Vous n\'êtes pas participant à cette discussion.');
                    return $this->redirectToRoute('app_contrat_index');
                }

                $fromDiscussion = true;
                $contrat->setType($discussion->getType());
                $contrat->setStatut(Contrat::STATUT_BROUILLON); // Brouillon par défaut

                // Set parties based on discussion
                if ($discussion->getInitiateur()->getId() === $user->getId()) {
                    $contrat->setArtiste($user);
                    $contrat->setProducteur($discussion->getDestinataire());
                } else {
                    $contrat->setArtiste($discussion->getDestinataire());
                    $contrat->setProducteur($user);
                }

                // Set product if Type A
                if ($discussion->isTypePublicationRights() && $discussion->getproject()) {
                    $contrat->setproject($discussion->getproject());
                }
            }
        } else {
            // Auto-fill based on user type when NOT from discussion
            $userType = strtolower($user->getUserType() ?? '');

            // Si artiste/musicien/scénariste → pré-remplir comme artiste
            if (in_array($userType, ['artiste', 'musicien', 'scénariste'])) {
                $contrat->setArtiste($user);
            }
            // Si publisher/sponsor → pré-remplir comme client (producteur)
            elseif (in_array($userType, ['publisher', 'sponsor'])) {
                $contrat->setProducteur($user);
            }
        }

        $showproject = $contrat->getType() === Contrat::TYPE_PUBLICATION_RIGHTS;

        $form = $this->createForm(ContratType::class, $contrat, [
            'from_discussion' => $fromDiscussion,
            'show_project' => $showproject,
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Create contract using service
                $createdContrat = $this->contratService->creerContrat(
                    $contrat->getArtiste(),
                    $contrat->getProducteur(),
                    $contrat->getType(),
                    $contrat->getPrix(),
                    $contrat->getConditionsTexte(),
                    $contrat->getDateDebut(),
                    $contrat->getDateFin(),
                    $contrat->getproject()
                );

                // Link to discussion if creating from discussion
                if ($discussion) {
                    $createdContrat->setDiscussionOrigine($discussion);
                }

                $this->em->flush();

                $successMessage = $createdContrat->getStatut() === Contrat::STATUT_BROUILLON
                    ? 'Brouillon de contrat créé avec succès ! Numéro: ' . $createdContrat->getNumeroContrat()
                    : 'Contrat créé avec succès ! Numéro: ' . $createdContrat->getNumeroContrat();

                $this->addFlash('success', $successMessage);

                // Redirect to discussion if coming from there
                if ($discussion) {
                    return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
                }

                return $this->redirectToRoute('app_contrat_show', ['id' => $createdContrat->getId()]);

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du contrat : ' . $e->getMessage());
            }
        }

        return $this->render('contrat/new.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView(),
            'from_discussion' => $fromDiscussion,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Contrat $contrat, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

            // Only allow user to view if they are participant
            $canView = $contrat->getArtiste()->getId() === $user->getId() || $contrat->getProducteur()->getId() === $user->getId();
            if (!$canView) {
                $this->addFlash('error', 'Vous n\'avez pas accès à ce contrat.');
                return $this->redirectToRoute('app_contrat_index');
            }

        // Handle signature
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            try {
                if ($action === 'sign_artist' && $contrat->getArtiste()->getId() === $user->getId()) {
                    $this->contratService->signerParArtist($contrat, $user);
                    $this->addFlash('success', 'Vous avez signé le contrat en tant qu\'artiste.');
                    return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
                } elseif ($action === 'sign_client' && $contrat->getProducteur()->getId() === $user->getId()) {
                    $this->contratService->signerParClient($contrat, $user);
                    $this->addFlash('success', 'Vous avez signé le contrat en tant que client.');
                    return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la signature : ' . $e->getMessage());
            }
        }

        return $this->render('contrat/show.html.twig', [
            'contrat' => $contrat,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contrat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contrat $contrat, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

            // Only allow user to edit if they are participant and contract is not archived
            if ($contrat->isArchived()) {
                $this->addFlash('error', 'Ce contrat est archivé et ne peut plus être modifié.');
                return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
            }
            $canEdit = $contrat->getArtiste()->getId() === $user->getId() || $contrat->getProducteur()->getId() === $user->getId();
            if (!$canEdit) {
                $this->addFlash('error', 'Vous n\'avez pas la permission de modifier ce contrat.');
                return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
            }

        $showproject = $contrat->getType() === Contrat::TYPE_PUBLICATION_RIGHTS;

        $form = $this->createForm(ContratType::class, $contrat, [
            'from_discussion' => false,
            'show_project' => $showproject,
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contrat->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();

                $this->addFlash('success', 'Contrat modifié avec succès !');
                return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('contrat/edit.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_contrat_delete', methods: ['POST'])]
    public function delete(Request $request, Contrat $contrat, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // SEUL L'ADMIN peut supprimer définitivement un contrat
        if ($user->getUserType() !== 'admin') {
            $this->addFlash('error', 'Seul un administrateur peut supprimer définitivement un contrat.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete_contrat_' . $contrat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        try {
            $this->em->remove($contrat);
            $this->em->flush();

            $this->addFlash('success', 'Contrat supprimé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_index');
    }

    #[Route('/{id}/finaliser', name: 'app_contrat_finaliser', methods: ['POST'])]
    public function finaliser(Request $request, Contrat $contrat, UserRepository $userRepo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier que le contrat est en statut brouillon
        if ($contrat->getStatut() !== Contrat::STATUT_BROUILLON) {
            $this->addFlash('error', 'Seul un contrat brouillon peut être finalisé.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        // Vérifier que l'utilisateur participe au contrat
        if ($contrat->getArtiste()->getId() !== $user->getId() && $contrat->getProducteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas finaliser ce contrat.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('finaliser_contrat_' . $contrat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        try {
            // Marquer l'acceptation de l'utilisateur
            $isArtist = $contrat->getArtiste()->getId() === $user->getId();

            if ($isArtist) {
                $contrat->setAcceptationArtiste(true);
            } else {
                $contrat->setAcceptationClient(true);
            }

            // Si les deux ont accepté, passer en statut final
            if ($contrat->isAcceptationArtiste() && $contrat->isAcceptationClient()) {
                $contrat->setStatut(Contrat::STATUT_FINAL);
                $contrat->setUpdatedAt(new \DateTimeImmutable());
                $this->addFlash('success', 'Le contrat a été finalisé ! Il est maintenant prêt à être signé par les deux parties.');
            } else {
                $this->addFlash('success', 'Vous avez accepté les conditions du contrat. En attente de l\'acceptation de l\'autre partie.');
            }

            $this->em->flush();

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la finalisation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
}
