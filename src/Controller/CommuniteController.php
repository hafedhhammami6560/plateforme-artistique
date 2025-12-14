<?php
/**
 * Contrôleur CommuniteController
 * 
 * Gère le CRUD pour l'entité Communite (communautés artistiques)
 * Fonctionnalités: recherche textuelle, tri sur plusieurs champs
 * 
 * Routes disponibles:
 * - GET  /communite/           : Liste avec recherche et tri
 * - GET  /communite/new        : Formulaire de création
 * - POST /communite/new        : Traitement création
 * - GET  /communite/{id}       : Affichage détaillé
 * - GET  /communite/{id}/edit  : Formulaire édition
 * - POST /communite/{id}/edit  : Traitement édition
 * - POST /communite/{id}       : Suppression
 */
namespace App\Controller;

use App\Entity\Communite;
use App\Form\CommuniteType;
use App\Repository\CommuniteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Préfixe de toutes les routes: /communite
#[Route('/communite')]
class CommuniteController extends AbstractController
{
    /**
     * Action INDEX - Liste des communités avec recherche et tri
     * 
     * Fonctionnalités:
     * - Recherche textuelle sur nom et description
     * - Tri sur name, description, date de création, auteur
     * 
     * Paramètres GET:
     * - search : Terme de recherche
     * - sort   : Champ de tri (name/description/createdAt/createdBy)
     * - order  : Ordre ASC ou DESC
     * 
     * @param Request $request Requête HTTP
     * @param CommuniteRepository $repo Repository pour QueryBuilder
     * @return Response Page HTML
     */
    #[Route('/', name: 'communite_index', methods: ['GET'])]
    public function index(Request $request, CommuniteRepository $repo): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');
        
        // Redirect to login if not connected
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        
        // Récupération des paramètres GET avec valeurs par défaut
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'ASC');

        // Création du QueryBuilder (alias 'c' pour Communite)
        $queryBuilder = $repo->createQueryBuilder('c');

        // RECHERCHE: Nom OU Description contient le terme
        if ($search) {
            $queryBuilder->andWhere('c.name LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // TRI: Validation whitelist pour sécurité
        $validSorts = ['name', 'description', 'createdAt', 'createdBy'];
        if (in_array($sortBy, $validSorts)) {
            $queryBuilder->orderBy('c.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');
        }

        // Exécution de la requête
        $communites = $queryBuilder->getQuery()->getResult();

        // Rendu du template avec les résultats et les paramètres (pour garder l'état des filtres)
        return $this->render('communite/index.html.twig', [
            'communites' => $communites,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    /**
     * Action NEW - Création d'une nouvelle communité
     * 
     * Gère GET (formulaire) et POST (soumission)
     * Utilise un utilisateur statique en attendant le module User
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $em Entity Manager
     * @return Response Page HTML ou redirection
     */
    #[Route('/new', name: 'communite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // Nouvelle instance vide
        $communite = new Communite();
        
        // Utilisateur statique (module User non disponible)
        $communite->setCreatedBy('user_static');
        
        // Création et liaison du formulaire
        $form = $this->createForm(CommuniteType::class, $communite);
        $form->handleRequest($request);

        // Vérification si formulaire soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sauvegarde en base de données
                $em->persist($communite);
                $em->flush();

                // Message flash de succès
                $this->addFlash('success', 'Communité créée avec succès !');
                
                // Redirection vers la liste (pattern PRG: Post-Redirect-Get)
                return $this->redirectToRoute('communite_index');
                
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Erreur spécifique: contrainte UNIQUE violée (nom en double par exemple)
                $this->addFlash('error', 'Une communité avec ce nom existe déjà.');
            } catch (\Doctrine\DBAL\Exception $e) {
                // Erreur de base de données (connexion, syntaxe SQL, etc.)
                $this->addFlash('error', 'Erreur de base de données : ' . $e->getMessage());
            } catch (\Exception $e) {
                // Toute autre erreur non prévue
                $this->addFlash('error', 'Erreur inattendue : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire dans les cas suivants:
        // 1. Requête GET (première visite)
        // 2. Formulaire invalide (erreurs de validation Symfony/Doctrine)
        // 3. Exception levée lors de la sauvegarde
        return $this->render('communite/new.html.twig', [
            'communite' => $communite,
            'form' => $form->createView(),  // createView() pour le rendu Twig
        ]);
    }

    /**
     * Action SHOW - Affichage détaillé d'une communauté
     * 
     * Symfony charge automatiquement l'entité depuis l'ID dans l'URL
     * Si l'ID n'existe pas, une erreur 404 est automatiquement renvoyée
     * 
     * @param Communite $communite Entité chargée par ParamConverter
     * @return Response Page HTML de détail
     */
    #[Route('/{id}', name: 'communite_show', methods: ['GET'])]
    public function show(Communite $communite, EntityManagerInterface $em): Response
    {
        // Récupérer les feedbacks liés à cette communauté
        $feedbacks = $em->getRepository(\App\Entity\Feedback::class)
            ->createQueryBuilder('f')
            ->where('f.communite = :communite')
            ->andWhere('f.status = :status')
            ->setParameter('communite', $communite)
            ->setParameter('status', 'published')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->render('communite/show.html.twig', [
            'communite' => $communite,
            'feedbacks' => $feedbacks,
        ]);
    }

    /**
     * Action EDIT - Modification d'une communauté existante
     * 
     * Charge la communauté, pré-remplit le formulaire et sauvegarde les modifications
     * 
     * @param Request $request Requête HTTP
     * @param Communite $communite Entité à modifier (chargée automatiquement)
     * @param EntityManagerInterface $em Entity Manager
     * @return Response Page HTML ou redirection
     */
    #[Route('/{id}/edit', name: 'communite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Communite $communite, EntityManagerInterface $em): Response
    {
        // Vérification des droits admin via cookie
        if ($request->cookies->get('user_role') !== 'admin') {
            $this->addFlash('error', 'Accès refusé : Vous devez être connecté en tant qu\'administrateur pour modifier une communauté.');
            return $this->redirectToRoute('auth_login');
        }

        // Formulaire pré-rempli avec les valeurs existantes
        $form = $this->createForm(CommuniteType::class, $communite);
        $form->handleRequest($request);

        // Vérification soumission et validation
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // flush() sauvegarde automatiquement les modifications détectées
                // Pas besoin de persist() car l'entité est déjà gérée par Doctrine
                $em->flush();

                // Message de succès
                $this->addFlash('success', 'Communité modifiée avec succès !');
                return $this->redirectToRoute('communite_index');
                
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Erreur: nom en double
                $this->addFlash('error', 'Ce nom est déjà utilisé par une autre communauté.');
            } catch (\Exception $e) {
                // Autres erreurs de mise à jour
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire (GET ou erreurs)
        return $this->render('communite/edit.html.twig', [
            'communite' => $communite,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Action DELETE - Suppression d'une communauté
     * 
     * Méthode POST uniquement avec protection CSRF
     * Attention: supprimer une communauté peut échouer si des organisations y sont liées
     * 
     * @param Request $request Requête HTTP
     * @param Communite $communite Entité à supprimer
     * @param EntityManagerInterface $em Entity Manager
     * @return Response Redirection vers la liste
     */
    #[Route('/{id}', name: 'communite_delete', methods: ['POST'])]
    public function delete(Request $request, Communite $communite, EntityManagerInterface $em): Response
    {
        // Vérification des droits admin via cookie
        if ($request->cookies->get('user_role') !== 'admin') {
            $this->addFlash('error', 'Accès refusé : Vous devez être connecté en tant qu\'administrateur pour supprimer une communauté.');
            return $this->redirectToRoute('communite_index');
        }

        // Validation du token CSRF (sécurité)
        // Format: 'delete' + ID de l'entité
        if ($this->isCsrfTokenValid('delete'.$communite->getId(), $request->request->get('_token'))) {
            try {
                // Suppression de l'entité
                $em->remove($communite);
                $em->flush();
                
                // Message de succès
                $this->addFlash('success', 'Communauté supprimée avec succès !');
                
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                // Erreur critique: des organisations sont liées à cette communauté
                // La suppression violerait la contrainte de clé étrangère
                $this->addFlash('error', 'Impossible de supprimer : des organisations sont liées à cette communauté.');
            } catch (\Doctrine\DBAL\Exception $e) {
                // Erreur de base de données
                $this->addFlash('error', 'Erreur de base de données : ' . $e->getMessage());
            } catch (\Exception $e) {
                // Autres erreurs inattendues
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            // Token CSRF invalide = tentative d'attaque ou formulaire expiré
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
        }

        // Retour à la liste dans tous les cas
        return $this->redirectToRoute('communite_index');
    }
}
