<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProjectRepository $projectRepository): Response
    {
        // Show the latest projects on the homepage
        $recentProjects = $projectRepository->findRecent(8);
        
        return $this->render('home/index.html.twig', [
            'recent_projects' => $recentProjects,
        ]);
    }
}
