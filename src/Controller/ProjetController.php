<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/projet')]
class ProjetController extends AbstractController
{
    #[Route('/', name: 'projet_index', methods: ['GET'])]
    public function index(ProjetRepository $projetRepository, UserRepository $userRepo, Request $request): Response
    {
        // Récupérer les paramètres de recherche et tri
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $sort = $request->query->get('sort', 'date_desc');
        
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        $user = null;
        $mesProjets = [];
        $autresProjets = [];
        $isCreator = false;
        
        // Construire la requête avec filtres
        $qb = $projetRepository->createQueryBuilder('p');
        
        // Filtre de recherche
        if ($search) {
            $qb->leftJoin('p.artist', 'a')
               ->andWhere('p.nom LIKE :search OR p.description LIKE :search OR a.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre de catégorie
        if ($categorie) {
            $qb->andWhere('p.categorieLabel = :categorie OR p.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }
        
        // Tri
        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('p.dateCreation', 'ASC');
                break;
            case 'nom_asc':
                $qb->orderBy('p.nom', 'ASC');
                break;
            case 'nom_desc':
                $qb->orderBy('p.nom', 'DESC');
                break;
            case 'prix_asc':
                $qb->orderBy('p.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('p.prix', 'DESC');
                break;
            default: // date_desc
                $qb->orderBy('p.dateCreation', 'DESC');
        }
        
        $allProjets = $qb->getQuery()->getResult();
        
        if ($userId) {
            $user = $userRepo->find($userId);
            if ($user) {
                $userType = strtolower($user->getUserType() ?? '');
                $isCreator = in_array($userType, ['artiste', 'musicien', 'scénariste']);
                
                // Si c'est un créateur, séparer ses Projets des autres
                if ($isCreator) {
                    foreach ($allProjets as $Projet) {
                        if ($Projet->getArtist() && $Projet->getArtist()->getId() === $user->getId()) {
                            $mesProjets[] = $Projet;
                        } else {
                            $autresProjets[] = $Projet;
                        }
                    }
                } else {
                    // Pour les non-créateurs, tous les Projets dans "autres"
                    $autresProjets = $allProjets;
                }
            }
        } else {
            // Utilisateur non connecté - tous les Projets
            $autresProjets = $allProjets;
        }
        
        $categories = $projetRepository->findAllCategories();

        return $this->render('projet/index.html.twig', [
            'mesProjets' => $mesProjets,
            'autresProjets' => $autresProjets,
            'categories' => $categories,
            'selected_categorie' => $categorie,
            'isCreator' => $isCreator,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'projet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepo): Response
    {
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Check if user is a creator
        $userType = strtolower($user->getUserType() ?? '');
        if (!in_array($userType, ['artiste', 'musicien', 'scénariste'])) {
            $this->addFlash('error', 'Seuls les créateurs (Artiste, Musicien, Scénariste) peuvent créer des projets.');
            return $this->redirectToRoute('projet_index');
        }

        // TODO: Implémenter le formulaire de création de projet
        $this->addFlash('info', 'La création de projets sera bientôt disponible.');
        return $this->redirectToRoute('projet_index');
    }

    #[Route('/{id}', name: 'projet_show', methods: ['GET'])]
    public function show(Projet $projet): Response
    {
        return $this->render('projet/show.html.twig', [
            'projet' => $projet,
        ]);
    }

    #[Route('/{id}/edit', name: 'projet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projet $projet, UserRepository $userRepo): Response
    {
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Check if user owns this product
        if (!$projet->getArtist() || $projet->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres projets.');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        // Check if product is under contract
        if ($projet->isSousContrat()) {
            $this->addFlash('error', 'Ce projet est sous contrat et ne peut pas être modifié.');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        $this->addFlash('info', 'La modification de projets sera bientôt disponible.');
        return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
    }

    #[Route('/{id}', name: 'projet_delete', methods: ['POST'])]
    public function delete(Request $request, Projet $projet, UserRepository $userRepo): Response
    {
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Check if user owns this product
        if (!$projet->getArtist() || $projet->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres projets.');
            return $this->redirectToRoute('projet_index');
        }

        // Check if product is under contract
        if ($projet->isSousContrat()) {
            $this->addFlash('error', 'Ce projet est sous contrat et ne peut pas être supprimé.');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        $this->addFlash('info', 'La suppression de projets sera bientôt disponible.');
        return $this->redirectToRoute('projet_index');
    }
}

