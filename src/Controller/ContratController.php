<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Discussion;
use App\Entity\User;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Repository\UserRepository;
use App\Service\ContratService;
use App\Service\DiscussionService;
use App\Service\PermissionService;
use App\Security\Voter\ContratVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contrat')]
class ContratController extends AbstractController
{
    public function __construct(
        private ContratService $contratService,
        private DiscussionService $discussionService,
        private PermissionService $permissionService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    #[Route('/', name: 'app_contrat_index', methods: ['GET'])]
    public function index(ContratRepository $repository, Request $request): Response
    {
        // Get user ID from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder aux contrats.');
            return $this->redirectToRoute('auth_login');
        }
        
        // Récupérer les paramètres de recherche, filtrage et tri
        $search = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        $statutFilter = $request->query->get('statut', '');
        $sortBy = $request->query->get('sort', 'date_desc');
        
        // Construire la requête
        $qb = $repository->createQueryBuilder('c')
            ->leftJoin('c.artiste', 'artiste')
            ->leftJoin('c.producteur', 'producteur')
            ->leftJoin('c.produit', 'produit')
            ->where('c.artiste = :userId')
            ->orWhere('c.producteur = :userId')
            ->setParameter('userId', $userId);
        
        // Recherche par numéro de contrat, nom d'utilisateur ou produit
        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.numeroContrat', ':search'),
                    $qb->expr()->like('artiste.name', ':search'),
                    $qb->expr()->like('producteur.name', ':search'),
                    $qb->expr()->like('produit.nom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre par type
        if (!empty($typeFilter)) {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $typeFilter);
        }
        
        // Filtre par statut
        if (!empty($statutFilter)) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $statutFilter);
        }
        
        // Tri
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('c.createdAt', 'ASC');
                break;
            case 'montant_asc':
                $qb->orderBy('c.prix', 'ASC');
                break;
            case 'montant_desc':
                $qb->orderBy('c.prix', 'DESC');
                break;
            case 'numero_asc':
                $qb->orderBy('c.numeroContrat', 'ASC');
                break;
            case 'numero_desc':
                $qb->orderBy('c.numeroContrat', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('c.createdAt', 'DESC');
                break;
        }
        
        $contrats = $qb->getQuery()->getResult();

        return $this->render('contrat/index.html.twig', [
            'contrats' => $contrats,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statutFilter' => $statutFilter,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/new', name: 'app_contrat_new', methods: ['GET', 'POST'])]
    #[Route('/discussion/{discussion}/new', name: 'app_contrat_new_from_discussion', methods: ['GET', 'POST'])]
    public function new(Request $request, ?Discussion $discussion = null): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un contrat.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier les permissions
        if (!$this->permissionService->canCreateContrat($currentUser)) {
            $this->addFlash('error', $this->permissionService->getPermissionDeniedMessage($currentUser, 'create_contrat'));
            return $this->redirectToRoute('app_contrat_index');
        }

        $contrat = new Contrat();
        $showProduit = true;

        // Si créé depuis une discussion
        if ($discussion) {
            // Vérifier permission de voir la discussion
            if (!$this->permissionService->canViewDiscussion($currentUser, $discussion)) {
                $this->addFlash('error', 'Vous n\'avez pas accès à cette discussion.');
                return $this->redirectToRoute('app_contrat_index');
            }
            
            $contrat->setType($discussion->getType());
            
            // Déterminer qui est l'artiste et qui est le producteur
            // L'initiateur de la discussion est toujours l'artiste
            // Le destinataire est toujours le publisher/sponsor
            $contrat->setArtiste($discussion->getInitiateur());
            $contrat->setProducteur($discussion->getDestinataire());
            
            // Type A : pré-remplir avec le produit
            if ($discussion->isTypePublicationRights()) {
                $contrat->setProduit($discussion->getProduit());
            } else {
                $showProduit = false;
            }
        } else {
            // Création directe sans discussion : l'utilisateur connecté est l'artiste
            $contrat->setArtiste($currentUser);
        }

        $form = $this->createForm(ContratType::class, $contrat, [
            'show_produit' => $showProduit,
            'current_user' => $contrat->getArtiste(), // Utiliser l'artiste du contrat pour filtrer les produits
            'from_discussion' => $discussion !== null
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contratCree = $this->contratService->creerContrat(
                    artist: $currentUser,
                    client: $contrat->getProducteur(),
                    type: $contrat->getType(),
                    prix: $contrat->getPrix(),
                    conditionsTexte: $contrat->getConditionsTexte(),
                    dateDebut: $contrat->getDateDebut(),
                    dateFin: $contrat->getDateFin(),
                    produit: $contrat->getProduit()
                );

                $this->entityManager->flush();

                // Si créé depuis une discussion, lier le contrat
                if ($discussion) {
                    $this->discussionService->lierContrat($discussion, $contratCree);
                }

                $this->addFlash('success', 'Contrat créé avec succès ! Numéro: ' . $contratCree->getNumeroContrat());
                return $this->redirectToRoute('app_contrat_show', ['id' => $contratCree->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('contrat/new.html.twig', [
            'form' => $form,
            'discussion' => $discussion,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_show', methods: ['GET'])]
    public function show(Contrat $contrat, Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canViewContrat($currentUser, $contrat)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce contrat.');
            return $this->redirectToRoute('app_contrat_index');
        }

        return $this->render('contrat/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contrat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contrat $contrat): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canEditContrat($currentUser, $contrat)) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier ce contrat.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        if (!$contrat->canBeModified()) {
            $this->addFlash('error', 'Ce contrat ne peut plus être modifié (signature déjà apposée).');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        $form = $this->createForm(ContratType::class, $contrat, [
            'is_edit' => true,
            'show_produit' => $contrat->isTypePublicationRights(),
            'current_user' => $currentUser
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contrat->setUpdatedAt(new \DateTimeImmutable());
                
                // Mettre à jour aussi les champs de compatibilité
                $contrat->setMontant((float) $contrat->getPrix());
                $contrat->setTermes($contrat->getConditionsTexte());
                
                $this->entityManager->flush();

                $this->addFlash('success', 'Contrat mis à jour.');
                return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('contrat/edit.html.twig', [
            'contrat' => $contrat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/signer-artist', name: 'app_contrat_signer_artist', methods: ['POST'])]
    public function signerArtist(Contrat $contrat, Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canSignContrat($currentUser, $contrat)) {
            $this->addFlash('error', 'Vous ne pouvez pas signer ce contrat.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        try {
            $this->contratService->signerParArtist($contrat, $currentUser);
            
            if ($contrat->isFullySigned()) {
                $this->addFlash('success', 'Contrat signé avec succès ! Le contrat est maintenant actif (toutes les parties ont signé).');
            } else {
                $this->addFlash('success', 'Votre signature a été apposée. En attente de la signature du client.');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }

    #[Route('/{id}/signer-client', name: 'app_contrat_signer_client', methods: ['POST'])]
    public function signerClient(Contrat $contrat, Request $request): Response
    {
        // Get user from cookie
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('auth_login');
        }

        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser || !$this->permissionService->canSignContrat($currentUser, $contrat)) {
            $this->addFlash('error', 'Vous ne pouvez pas signer ce contrat.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        try {
            $this->contratService->signerParClient($contrat, $currentUser);
            
            if ($contrat->isFullySigned()) {
                $this->addFlash('success', 'Contrat signé avec succès ! Le contrat est maintenant actif (toutes les parties ont signé).');
                
                // Message spécifique selon le type
                if ($contrat->isTypePublicationRights()) {
                    $this->addFlash('info', 'Le produit a été automatiquement marqué comme "sous contrat".');
                } else {
                    $this->addFlash('info', 'L\'artiste peut maintenant créer et associer le produit commandé.');
                }
            } else {
                $this->addFlash('success', 'Votre signature a été apposée. En attente de la signature de l\'artiste.');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }

    #[Route('/{id}/export-pdf', name: 'app_contrat_export_pdf', methods: ['GET'])]
    public function exportPdf(Contrat $contrat): Response
    {
        $this->denyAccessUnlessGranted(ContratVoter::VIEW, $contrat);

        // TODO: Implémenter l'export PDF
        // Utiliser DomPDF ou similaire
        
        $this->addFlash('info', 'Export PDF à implémenter prochainement.');
        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
}
