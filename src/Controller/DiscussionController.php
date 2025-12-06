<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\User;
use App\Form\DiscussionType;
use App\Form\MessageType;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use App\Service\DiscussionService;
use App\Service\PermissionService;
use App\Security\Voter\DiscussionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/discussion')]
class DiscussionController extends AbstractController
{
    public function __construct(
        private DiscussionService $discussionService,
        private PermissionService $permissionService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    #[Route('/', name: 'app_discussion_index', methods: ['GET'])]
    public function index(DiscussionRepository $repository, Request $request): Response
    {
        // Get user ID from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder aux discussions.');
            return $this->redirectToRoute('auth_login');
        }
        
        // Récupérer les paramètres de recherche, filtrage et tri
        $search = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        $statutFilter = $request->query->get('statut', '');
        $sortBy = $request->query->get('sort', 'date_desc');
        
        // Construire la requête
        $qb = $repository->createQueryBuilder('d')
            ->leftJoin('d.initiateur', 'initiateur')
            ->leftJoin('d.destinataire', 'destinataire')
            ->leftJoin('d.produit', 'produit')
            ->where('d.initiateur = :userId')
            ->orWhere('d.destinataire = :userId')
            ->setParameter('userId', $userId);
        
        // Recherche par titre ou nom d'utilisateur
        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('d.titre', ':search'),
                    $qb->expr()->like('initiateur.name', ':search'),
                    $qb->expr()->like('destinataire.name', ':search'),
                    $qb->expr()->like('produit.nom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre par type
        if (!empty($typeFilter)) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $typeFilter);
        }
        
        // Filtre par statut
        if (!empty($statutFilter)) {
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $statutFilter);
        }
        
        // Tri
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('d.createdAt', 'ASC');
                break;
            case 'titre_asc':
                $qb->orderBy('d.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('d.titre', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('d.createdAt', 'DESC');
                break;
        }
        
        $discussions = $qb->getQuery()->getResult();

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statutFilter' => $statutFilter,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/new', name: 'app_discussion_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour créer une discussion.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier les permissions
        if (!$this->permissionService->canCreateDiscussion($currentUser)) {
            $this->addFlash('error', $this->permissionService->getPermissionDeniedMessage($currentUser, 'create_discussion'));
            return $this->redirectToRoute('app_discussion_index');
        }

        $discussion = new Discussion();
        $form = $this->createForm(DiscussionType::class, $discussion, [
            'user' => $currentUser,
            'permission_service' => $this->permissionService
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Vérifier les permissions pour le type de discussion
                if (!$this->permissionService->canCreateDiscussionType($currentUser, $discussion->getType())) {
                    $this->addFlash('error', 'Vous n\'avez pas la permission de créer ce type de discussion.');
                    return $this->render('discussion/new.html.twig', [
                        'form' => $form,
                    ]);
                }

                $messageInitial = $form->get('messageInitial')->getData();
                $type = $discussion->getType();
                $initiateur = $currentUser;
                $destinataire = $discussion->getDestinataire();
                $titre = $discussion->getTitre();

                // Créer selon le type
                if ($type === Discussion::TYPE_PUBLICATION_RIGHTS) {
                    $produit = $discussion->getProduit();
                    if (!$produit) {
                        $this->addFlash('error', 'Un produit est obligatoire pour une discussion de type Publication Rights.');
                        return $this->render('discussion/new.html.twig', [
                            'form' => $form,
                        ]);
                    }
                    
                    $discussion = $this->discussionService->creerDiscussionTypeA(
                        $initiateur,
                        $destinataire,
                        $produit,
                        $titre,
                        $messageInitial
                    );
                } else {
                    // Type B - Custom Order
                    $discussion = $this->discussionService->creerDiscussionTypeB(
                        $initiateur,
                        $destinataire,
                        $titre,
                        $messageInitial
                    );
                }

                $this->addFlash('success', 'Discussion créée avec succès !');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('discussion/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_discussion_show', methods: ['GET', 'POST'])]
    public function show(Discussion $discussion, Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier les permissions
        if (!$this->permissionService->canViewDiscussion($currentUser, $discussion)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette discussion.');
            return $this->redirectToRoute('app_discussion_index');
        }

        // Marquer les messages comme lus
        $this->discussionService->marquerMessagesLus($discussion, $currentUser);

        // Formulaire pour ajouter un message
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Vérifier les permissions
                if (!$this->permissionService->canSendMessage($currentUser, $discussion)) {
                    $this->addFlash('error', 'Vous ne pouvez pas envoyer de message dans cette discussion.');
                    return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
                }
                
                $this->discussionService->ajouterMessage(
                    $discussion,
                    $currentUser,
                    $message->getContenu()
                );

                $this->addFlash('success', 'Message envoyé !');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/terminer', name: 'app_discussion_terminer', methods: ['POST'])]
    public function terminer(Discussion $discussion, Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canEditDiscussion($currentUser, $discussion)) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de terminer cette discussion.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        try {
            $this->discussionService->terminer($discussion);
            $this->addFlash('success', 'Discussion terminée.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canEditDiscussion($currentUser, $discussion)) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette discussion.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $discussion->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                $this->addFlash('success', 'Discussion mise à jour.');
                return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }
}
