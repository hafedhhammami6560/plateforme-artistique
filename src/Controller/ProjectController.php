<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, UserRepository $userRepo, Request $request): Response
    {
        // Récupérer les paramètres de recherche et tri
        $search = $request->query->get('search', '');
            $category = $request->query->get('category', '');
        $sort = $request->query->get('sort', 'date_desc');
        
        // Check if user is connected
        $userId = $request->cookies->get('user_id');
        $user = null;
        $mesProjects = [];
        $autresProjects = [];
        $isCreator = false;
        
        // Construire la requête avec filtres
        $qb = $projectRepository->createQueryBuilder('p');
        
        // Filtre de recherche
        if ($search) {
            $qb->leftJoin('p.artist', 'a')
               ->andWhere('p.nom LIKE :search OR p.description LIKE :search OR a.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre de catégorie
            if ($category) {
                $qb->andWhere('p.categoryLabel = :category')
                   ->setParameter('category', $category);
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
        
        $allProjects = $qb->getQuery()->getResult();
        
        if ($userId) {
            $user = $userRepo->find($userId);
            if ($user) {
                $userType = strtolower($user->getUserType() ?? '');
                $isCreator = in_array($userType, ['artiste', 'musicien', 'scénariste']);
                
                // Si c'est un créateur, séparer ses Projects des autres
                if ($isCreator) {
                    foreach ($allProjects as $project) {
                        if ($project->getArtist() && $project->getArtist()->getId() === $user->getId()) {
                            $mesProjects[] = $project;
                        } else {
                            $autresProjects[] = $project;
                        }
                    }
                } else {
                    // Pour les non-créateurs, tous les Projects dans "autres"
                    $autresProjects = $allProjects;
                }
            }
        } else {
            // Utilisateur non connecté - tous les Projects
            $autresProjects = $allProjects;
        }
        
        $categorys = $projectRepository->findAllcategorys();

        return $this->render('project/index.html.twig', [
            'mesProjects' => $mesProjects,
            'autresProjects' => $autresProjects,
            'categorys' => $categorys,
                'selected_category' => $category,
            'isCreator' => $isCreator,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'project_new', methods: ['GET', 'POST'])]
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
            $this->addFlash('error', 'Seuls les créateurs (Artiste, Musicien, Scénariste) peuvent créer des projects.');
            return $this->redirectToRoute('project_index');
        }

        // TODO: Implémenter le formulaire de création de project
        $this->addFlash('info', 'La création de projects sera bientôt disponible.');
        return $this->redirectToRoute('project_index');
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, UserRepository $userRepo): Response
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

        // Check if user owns this project
        if (!$project->getArtist() || $project->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres projects.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Check if project is under contract
        if ($project->isSousContrat()) {
            $this->addFlash('error', 'Ce project est sous contrat et ne peut pas être modifié.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $this->addFlash('info', 'La modification de projects sera bientôt disponible.');
        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, UserRepository $userRepo): Response
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

        // Check if user owns this project
        if (!$project->getArtist() || $project->getArtist()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres projects.');
            return $this->redirectToRoute('project_index');
        }

        // Check if project is under contract
        if ($project->isSousContrat()) {
            $this->addFlash('error', 'Ce project est sous contrat et ne peut pas être supprimé.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $this->addFlash('info', 'La suppression de projects sera bientôt disponible.');
        return $this->redirectToRoute('project_index');
    }
}

