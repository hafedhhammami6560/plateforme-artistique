<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository, UserRepository $userRepo, Request $request): Response
    {
        // Récupérer les paramètres de recherche et tri
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $sort = $request->query->get('sort', 'date_desc');
        
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        $user = null;
        $mesProduits = [];
        $autresProduits = [];
        $isCreator = false;
        
        // Construire la requête avec filtres
        $qb = $produitRepository->createQueryBuilder('p');
        
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
        
        $allProduits = $qb->getQuery()->getResult();
        
        if ($userId) {
            $user = $userRepo->find($userId);
            if ($user) {
                $userType = strtolower($user->getUserType() ?? '');
                $isCreator = in_array($userType, ['artiste', 'musicien', 'scénariste']);
                
                // Si c'est un créateur, séparer ses produits des autres
                if ($isCreator) {
                    foreach ($allProduits as $produit) {
                        if ($produit->getArtist() && $produit->getArtist()->getId() === $user->getId()) {
                            $mesProduits[] = $produit;
                        } else {
                            $autresProduits[] = $produit;
                        }
                    }
                } else {
                    // Pour les non-créateurs, tous les produits dans "autres"
                    $autresProduits = $allProduits;
                }
            }
        } else {
            // Utilisateur non connecté - tous les produits
            $autresProduits = $allProduits;
        }
        
        $categories = $produitRepository->findAllCategories();

        return $this->render('produit/index.html.twig', [
            'mesProduits' => $mesProduits,
            'autresProduits' => $autresProduits,
            'categories' => $categories,
            'selected_categorie' => $categorie,
            'isCreator' => $isCreator,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'produit_new', methods: ['GET', 'POST'])]
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
            $this->addFlash('error', 'Seuls les créateurs (Artiste, Musicien, Scénariste) peuvent créer des produits.');
            return $this->redirectToRoute('produit_index');
        }

        // TODO: Implémenter le formulaire de création de produit
        $this->addFlash('info', 'La création de produits sera bientôt disponible.');
        return $this->redirectToRoute('produit_index');
    }

    #[Route('/{id}', name: 'produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, UserRepository $userRepo): Response
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
        if (!$produit->getArtist() || $produit->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres produits.');
            return $this->redirectToRoute('produit_show', ['id' => $produit->getId()]);
        }

        // Check if product is under contract
        if ($produit->isSousContrat()) {
            $this->addFlash('error', 'Ce produit est sous contrat et ne peut pas être modifié.');
            return $this->redirectToRoute('produit_show', ['id' => $produit->getId()]);
        }

        $this->addFlash('info', 'La modification de produits sera bientôt disponible.');
        return $this->redirectToRoute('produit_show', ['id' => $produit->getId()]);
    }

    #[Route('/{id}', name: 'produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, UserRepository $userRepo): Response
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
        if (!$produit->getArtist() || $produit->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres produits.');
            return $this->redirectToRoute('produit_index');
        }

        // Check if product is under contract
        if ($produit->isSousContrat()) {
            $this->addFlash('error', 'Ce produit est sous contrat et ne peut pas être supprimé.');
            return $this->redirectToRoute('produit_show', ['id' => $produit->getId()]);
        }

        $this->addFlash('info', 'La suppression de produits sera bientôt disponible.');
        return $this->redirectToRoute('produit_index');
    }
}
