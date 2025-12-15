<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Service\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET'])]
    public function index(Request $request, ProjectRepository $projectRepository, \App\Repository\CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->get('category', null);
        
        if ($categoryId) {
            $projects = $projectRepository->findByCategory((int)$categoryId);
        } else {
            $projects = $projectRepository->findAllOrdered();
        }
        
        $categories = $categoryRepository->findAllOrdered();
        
        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'categories' => $categories,
            'selectedCategory' => $categoryId ? (int)$categoryId : null,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un projet.');
            return $this->redirectToRoute('auth_login');
        }

        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload avec Cloudinary
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $uploadResult = $cloudinaryService->uploadImage($imageFile, 'artworks/projects');
                
                if ($uploadResult['success']) {
                    $project->setCloudinaryUrl($uploadResult['url']);
                    $project->setCloudinaryPublicId($uploadResult['public_id']);
                    // Garder aussi le nom de fichier local pour compatibilité
                    $project->setImage($uploadResult['public_id']);
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $uploadResult['error']);
                    return $this->render('project/new.html.twig', [
                        'project' => $project,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès !');

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour éditer un projet.');
            return $this->redirectToRoute('auth_login');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload avec Cloudinary
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image de Cloudinary si elle existe
                if ($project->getCloudinaryPublicId()) {
                    $cloudinaryService->deleteFile($project->getCloudinaryPublicId());
                }
                
                $uploadResult = $cloudinaryService->uploadImage($imageFile, 'artworks/projects');
                
                if ($uploadResult['success']) {
                    $project->setCloudinaryUrl($uploadResult['url']);
                    $project->setCloudinaryPublicId($uploadResult['public_id']);
                    $project->setImage($uploadResult['public_id']);
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $uploadResult['error']);
                    return $this->render('project/edit.html.twig', [
                        'project' => $project,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Projet modifié avec succès !');

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un projet.');
            return $this->redirectToRoute('auth_login');
        }

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            // Supprimer l'image de Cloudinary avant de supprimer le projet
            if ($project->getCloudinaryPublicId()) {
                $cloudinaryService->deleteFile($project->getCloudinaryPublicId());
            }
            
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet supprimé avec succès !');
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }
}
