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

    // ... (autres méthodes avancées à intégrer, voir le fichier source complet de la branche)
}
