<?php

namespace App\Controller\Admin;

use App\Entity\Contrat;
use App\Entity\User;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/admin/contrat")]
class ContratAdminController extends AbstractController
{
    #[Route(path: "/", name: "admin_contrat_dashboard", methods: ["GET"])]
    public function dashboard(ContratRepository $contratRepository, Request $request, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        $contrats = $contratRepository->findAll();
        return $this->render('admin/contrat/dashboard.html.twig', [
            'contrats' => $contrats,
        ]);
    }

    #[Route(path: "/{id}", name: "admin_contrat_show", methods: ["GET"])]
    public function show(Contrat $contrat, Request $request, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        return $this->render('admin/contrat/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }

    #[Route(path: "/{id}/edit", name: "admin_contrat_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Contrat $contrat, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contrat modifié.');
            return $this->redirectToRoute('admin_contrat_dashboard');
        }
        return $this->render('admin/contrat/edit.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: "/{id}/delete", name: "admin_contrat_delete", methods: ["POST"])]
    public function delete(Request $request, Contrat $contrat, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        // Check authentication
        $userId = $request->cookies->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('auth_login');
        }
        $user = $userRepo->find($userId);
        if (!$user || $user->getUserType() !== 'admin') {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez être administrateur.');
        }

        if ($this->isCsrfTokenValid('delete'.$contrat->getId(), $request->request->get('_token'))) {
            $em->remove($contrat);
            $em->flush();
            $this->addFlash('success', 'Contrat supprimé.');
        }
        return $this->redirectToRoute('admin_contrat_dashboard');
    }
}
