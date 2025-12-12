<?php
/**
 * Contrôleur OrganisationController
 * 
 * Gère toutes les opérations CRUD pour les organisations
 * Fonctionnalités: recherche textuelle, filtrage par communauté, tri multiple
 * 
 * Routes disponibles:
 * - GET  /organisation/           : Liste avec recherche/filtres
 * - GET  /organisation/new        : Formulaire de création
 * - POST /organisation/new        : Traitement de création
 * - GET  /organisation/{id}       : Affichage détaillé
 * - GET  /organisation/{id}/edit  : Formulaire d'édition
 * - POST /organisation/{id}/edit  : Traitement d'édition
 * - POST /organisation/{id}       : Suppression
 */
namespace App\Controller;

use App\Entity\Organisation;
use App\Form\OrganisationType;
use App\Repository\OrganisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Préfixe de toutes les routes: /organisation
#[Route('/organisation')]
class OrganisationController extends AbstractController
{
    /**
     * Action INDEX - Liste des organisations avec recherche et filtres
     * 
     * Fonctionnalités:
     * - Recherche textuelle sur nom, email, adresse
     * - Filtre par communauté (relation ManyToOne)
     * - Tri sur plusieurs champs (name, email, date, auteur)
     * - Récupération de toutes les communautés pour le menu déroulant
     * 
     * Paramètres GET:
     * - search     : Terme de recherche
     * - communite  : ID de la communauté à filtrer
     * - sort       : Champ de tri
     * - order      : Ordre ASC/DESC
     * 
     * @param Request $request Requête HTTP
     * @param OrganisationRepository $repo Repository pour les requêtes
     * @param EntityManagerInterface $em Entity Manager pour accéder aux autres entités
     * @return Response Page HTML
     */
    #[Route('/', name: 'organisation_index', methods: ['GET'])]
    public function index(Request $request, OrganisationRepository $repo, EntityManagerInterface $em): Response
    {
        // Check if user is connected via cookie
        $userId = $request->cookies->get('user_id');
        
        // Redirect to login if not connected
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        
        // Récupération des paramètres GET
        $search = $request->query->get('search', '');
        $communiteId = $request->query->get('communite', '');
        $sortBy = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'ASC');

        // Création du QueryBuilder avec jointure LEFT sur la communauté
        // 'o' = alias pour Organisation, 'c' = alias pour Communite
        $queryBuilder = $repo->createQueryBuilder('o')
            ->leftJoin('o.communite', 'c');  // LEFT JOIN pour inclure les orgas sans communauté

        // FILTRE 1: Recherche textuelle (nom OU email OU adresse)
        if ($search) {
            $queryBuilder->andWhere('o.name LIKE :search OR o.email LIKE :search OR o.address LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // FILTRE 2: Par communauté (relation ManyToOne)
        if ($communiteId) {
            $queryBuilder->andWhere('o.communite = :communiteId')
                ->setParameter('communiteId', $communiteId);
        }

        // TRI: Validation avec liste blanche pour éviter injection SQL
        $validSorts = ['name', 'email', 'createdAt', 'createdBy'];
        if (in_array($sortBy, $validSorts)) {
            $queryBuilder->orderBy('o.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');
        }

        // Exécution de la requête et récupération des résultats
        $organisations = $queryBuilder->getQuery()->getResult();

        // Récupération de toutes les communautés pour le menu déroulant du filtre
        // Utilisation du repository Communite via l'EntityManager
        $communites = $em->getRepository(\App\Entity\Communite::class)->findAll();

        return $this->render('organisation/index.html.twig', [
            'organisations' => $organisations,
            'communites' => $communites,
            'search' => $search,
            'communiteId' => $communiteId,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    /**
     * Action NEW - Création d'une nouvelle organisation
     * 
     * Gère GET (affichage formulaire) et POST (traitement soumission)
     * Utilise un utilisateur statique car le module User n'est pas encore implémenté
     * Redirige vers la liste après sauvegarde réussie
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $em Entity Manager pour sauvegarder
     * @return Response Page HTML ou redirection
     */
    #[Route('/new', name: 'organisation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // Création d'une nouvelle instance vide
        $organisation = new Organisation();
        
        // Utilisateur statique (module User non implémenté)
        $organisation->setCreatedBy('user_static');
        
        // Création du formulaire et liaison avec la requête
        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        // Vérification soumission et validation
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sauvegarde en base de données
                $em->persist($organisation);
                $em->flush();

                // Message de succès
                $this->addFlash('success', 'Organisation créée avec succès!');
                
                // Redirection vers la liste (pattern PRG)
                return $this->redirectToRoute('organisation_index');
                
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Erreur: valeur dupliquée (ex: email déjà utilisé)
                $this->addFlash('error', 'Cette organisation existe déjà (email ou nom en double).');
            } catch (\Exception $e) {
                // Autres erreurs (connexion BD, contraintes, etc.)
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire:
        // - GET (première visite)
        // - Formulaire invalide (erreurs de validation)
        // - Exception lors de la sauvegarde
        return $this->render('organisation/new.html.twig', [
            'organisation' => $organisation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'organisation_show', methods: ['GET'])]
    public function show(Organisation $organisation, EntityManagerInterface $em): Response
    {
        // Récupérer les feedbacks liés à cette organisation
        $feedbacks = $em->getRepository(\App\Entity\Feedback::class)
            ->createQueryBuilder('f')
            ->where('f.organisation = :organisation')
            ->andWhere('f.status = :status')
            ->setParameter('organisation', $organisation)
            ->setParameter('status', 'published')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->render('organisation/show.html.twig', [
            'organisation' => $organisation,
            'feedbacks' => $feedbacks,
        ]);
    }

    /**
     * Action EDIT - Modification d'une organisation
     * 
     * @param Request $request Requête HTTP
     * @param Organisation $organisation Entité chargée automatiquement
     * @param EntityManagerInterface $em Entity Manager
     * @return Response Page HTML ou redirection
     */
    #[Route('/{id}/edit', name: 'organisation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Organisation $organisation, EntityManagerInterface $em): Response
    {
        // Vérification des droits admin via cookie
        if ($request->cookies->get('user_role') !== 'admin') {
            $this->addFlash('error', 'Accès refusé : Vous devez être connecté en tant qu\'administrateur pour modifier une organisation.');
            return $this->redirectToRoute('auth_login');
        }

        // Formulaire pré-rempli avec les valeurs actuelles
        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        // Vérification soumission et validation
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Mise à jour automatique (Doctrine détecte les changements)
                $em->flush();

                // Message de succès
                $this->addFlash('success', 'Organisation modifiée avec succès !');
                return $this->redirectToRoute('organisation_index');
                
            } catch (\Exception $e) {
                // Erreur lors de la mise à jour
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire (GET ou erreurs)
        return $this->render('organisation/edit.html.twig', [
            'organisation' => $organisation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Action DELETE - Suppression d'une organisation
     * 
     * Protégée par token CSRF contre les attaques
     * 
     * @param Request $request Requête HTTP
     * @param Organisation $organisation Entité à supprimer
     * @param EntityManagerInterface $em Entity Manager
     * @return Response Redirection vers la liste
     */
    #[Route('/{id}', name: 'organisation_delete', methods: ['POST'])]
    public function delete(Request $request, Organisation $organisation, EntityManagerInterface $em): Response
    {
        // Vérification des droits admin via cookie
        if ($request->cookies->get('user_role') !== 'admin') {
            $this->addFlash('error', 'Accès refusé : Vous devez être connecté en tant qu\'administrateur pour supprimer une organisation.');
            return $this->redirectToRoute('organisation_index');
        }

        // Validation du token CSRF (sécurité contre attaques CSRF)
        if ($this->isCsrfTokenValid('delete'.$organisation->getId(), $request->request->get('_token'))) {
            try {
                // Suppression de l'entité
                $em->remove($organisation);
                $em->flush();
                
                // Message de succès
                $this->addFlash('success', 'Organisation supprimée avec succès !');
                
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                // Erreur: données liées existent (contrainte de clé étrangère)
                $this->addFlash('error', 'Impossible de supprimer : cette organisation est liée à d\'autres données.');
            } catch (\Exception $e) {
                // Autres erreurs de suppression
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            // Token CSRF invalide
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        // Retour vers la liste dans tous les cas
        return $this->redirectToRoute('organisation_index');
    }
    
}
