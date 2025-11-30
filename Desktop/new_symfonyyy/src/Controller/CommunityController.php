<?php
/**
 * Contrôleur CommunityController
 * 
 * Gère toutes les opérations CRUD (Create, Read, Update, Delete) pour les communautés
 * Inclut également des fonctionnalités avancées de recherche, filtrage et tri
 * 
 * Routes disponibles:
 * - GET  /community/           : Liste avec filtres
 * - GET  /community/new        : Formulaire de création
 * - POST /community/new        : Traitement de création
 * - GET  /community/{id}       : Affichage détaillé
 * - GET  /community/{id}/edit  : Formulaire d'édition
 * - POST /community/{id}/edit  : Traitement d'édition
 * - POST /community/{id}       : Suppression
 */
namespace App\Controller;

use App\Entity\Community;
use App\Form\CommunityType;
use App\Repository\CommunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Préfixe de route pour toutes les méthodes de ce contrôleur
#[Route('/community')]
class CommunityController extends AbstractController
{
    /**
     * Action INDEX - Affiche la liste des communautés avec recherche et filtres
     * 
     * Paramètres GET acceptés:
     * - search  : Recherche textuelle (nom, slug, description)
     * - type    : Filtre par type (general/artist/category)
     * - privacy : Filtre par visibilité (0=public, 1=privé)
     * - sort    : Champ de tri (name/slug/type/createdAt)
     * - order   : Ordre de tri (ASC/DESC)
     * 
     * @param Request $request Requête HTTP contenant les paramètres
     * @param CommunityRepository $communityRepository Repository pour accéder aux données
     * @return Response Page HTML avec la liste des communautés
     */
    #[Route('/', name: 'app_community_index', methods: ['GET'])]
    public function index(Request $request, CommunityRepository $communityRepository): Response
    {
        // Récupération des paramètres de recherche/filtrage depuis l'URL
        $search = $request->query->get('search', '');        // Terme de recherche
        $type = $request->query->get('type', '');            // Type de communauté
        $privacy = $request->query->get('privacy', '');      // Public/Privé
        $sortBy = $request->query->get('sort', 'name');      // Champ de tri (défaut: name)
        $order = $request->query->get('order', 'ASC');       // Ordre (défaut: ASC)

        // Construction de la requête Doctrine avec QueryBuilder
        // 'c' est l'alias pour l'entité Community
        $queryBuilder = $communityRepository->createQueryBuilder('c');

        // FILTRE 1: Recherche textuelle sur plusieurs champs
        // Utilise LIKE pour une recherche partielle (contient)
        if ($search) {
            $queryBuilder->andWhere('c.name LIKE :search OR c.slug LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');  // % = wildcard SQL
        }

        // FILTRE 2: Par type de communauté (general/artist/category)
        if ($type) {
            $queryBuilder->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        // FILTRE 3: Par visibilité (public/privé)
        // Vérifie !== '' car '0' est une valeur valide (public)
        if ($privacy !== '') {
            $queryBuilder->andWhere('c.isPrivate = :privacy')
                ->setParameter('privacy', $privacy === '1');  // Convertit en boolean
        }

        // TRI: Sécurisé avec liste blanche (whitelist)
        // Empêche l'injection SQL en validant le champ de tri
        $validSorts = ['name', 'slug', 'type', 'createdAt'];
        if (in_array($sortBy, $validSorts)) {
            $queryBuilder->orderBy('c.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');
        }

        $communities = $queryBuilder->getQuery()->getResult();

        return $this->render('community/index.html.twig', [
            'communities' => $communities,
            'search' => $search,
            'type' => $type,
            'privacy' => $privacy,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_community_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle instance vide de Community
        $community = new Community();
        
        // Génération du formulaire basé sur CommunityType
        $form = $this->createForm(CommunityType::class, $community);
        
        // Liaison des données POST avec le formulaire
        $form->handleRequest($request);

        // Vérification: formulaire soumis ET validation réussie
        // isValid() vérifie les contraintes Doctrine et les validations du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Préparation de l'entité pour l'insertion
                $entityManager->persist($community);
                
                // Exécution de la requête SQL INSERT
                $entityManager->flush();

                // Message flash de succès (affiché après la redirection)
                $this->addFlash('success', 'Communauté créée avec succès !');
                
                // Redirection vers la liste (pattern PRG: Post-Redirect-Get)
                return $this->redirectToRoute('app_community_index');
                
            } catch (\Exception $e) {
                // Gestion d'erreur: problème lors de la sauvegarde
                // Ex: contrainte unique violée, erreur de connexion BD, etc.
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire:
        // - Si méthode GET (première visite)
        // - Si formulaire invalide (erreurs de validation)
        // - Si exception levée lors de la sauvegarde
        return $this->render('community/new.html.twig', [
            'community' => $community,
            'form' => $form,  // Contient les erreurs de validation s'il y en a
        ]);
    }

    #[Route('/{id}', name: 'app_community_show', methods: ['GET'])]
    public function show(Community $community): Response
    {
        return $this->render('community/show.html.twig', [
            'community' => $community,
        ]);
    }

    /**
     * Action EDIT - Modification d'une communauté existante
     * 
     * @param Request $request Requête HTTP
     * @param Community $community Entité chargée automatiquement (ParamConverter)
     * @param EntityManagerInterface $entityManager Entity Manager
     * @return Response Page HTML ou redirection
     */
    #[Route('/{id}/edit', name: 'app_community_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Community $community, EntityManagerInterface $entityManager): Response
    {
        // Formulaire pré-rempli avec les données existantes
        $form = $this->createForm(CommunityType::class, $community);
        $form->handleRequest($request);

        // Vérification soumission et validation
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // flush() détecte automatiquement les changements (pas besoin de persist)
                // Doctrine compare l'état actuel avec l'état initial
                $entityManager->flush();

                // Message de succès
                $this->addFlash('success', 'Communauté modifiée avec succès !');
                return $this->redirectToRoute('app_community_index');
                
            } catch (\Exception $e) {
                // Erreur lors de la mise à jour (contraintes, BD, etc.)
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire (GET ou erreurs de validation)
        return $this->render('community/edit.html.twig', [
            'community' => $community,
            'form' => $form,
        ]);
    }

    /**
     * Action DELETE - Suppression d'une communauté
     * 
     * Sécurisée par token CSRF pour éviter les suppressions malveillantes
     * 
     * @param Request $request Requête HTTP
     * @param Community $community Entité à supprimer
     * @param EntityManagerInterface $entityManager Entity Manager
     * @return Response Redirection vers la liste
     */
    #[Route('/{id}', name: 'app_community_delete', methods: ['POST'])]
    public function delete(Request $request, Community $community, EntityManagerInterface $entityManager): Response
    {
        // Vérification du token CSRF (Cross-Site Request Forgery)
        // Empêche les suppressions depuis un site malveillant
        if ($this->isCsrfTokenValid('delete'.$community->getId(), $request->request->get('_token'))) {
            try {
                // Marque l'entité pour suppression
                $entityManager->remove($community);
                
                // Exécution de la requête SQL DELETE
                $entityManager->flush();
                
                // Message de succès
                $this->addFlash('success', 'Communauté supprimée avec succès !');
                
            } catch (\Exception $e) {
                // Erreur lors de la suppression
                // Ex: contrainte de clé étrangère (données liées), erreur BD, etc.
                $this->addFlash('error', 'Impossible de supprimer : ' . $e->getMessage());
            }
        } else {
            // Token CSRF invalide ou manquant
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
        }

        // Redirection vers la liste dans tous les cas
        return $this->redirectToRoute('app_community_index');
    }
}
