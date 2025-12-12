<?php

namespace App\Controller\Admin;

use App\Entity\Projet;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/projet', name: 'admin_projet_')]
class ProjetAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ProjetRepository $projetRepository): Response
    {
        return $this->render('admin/projet/index.html.twig', [
            'projets' => $projetRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $Projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('kernel.project_dir').'/public/uploads/Projets', $newFilename);
                    $Projet->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors du téléchargement de l'image.");
                }
            }

            if ($Projet->getCategorie() && !$Projet->getCategorieLabel()) {
                $Projet->setCategorieLabel($Projet->getCategorie()->getNom());
            }

            $em->persist($Projet);
            $em->flush();
            $this->addFlash('success', 'Projet créé.');
            return $this->redirectToRoute('admin_Projet_index');
        }

        return $this->render('admin/Projet/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Projet $Projet): Response
    {
        return $this->render('admin/Projet/show.html.twig', [
            'Projet' => $Projet,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projet $Projet, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProjetType::class, $Projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('kernel.project_dir').'/public/uploads/Projets', $newFilename);
                    if ($Projet->getImage()) {
                        $old = $this->getParameter('kernel.project_dir').'/public/uploads/Projets/'.$Projet->getImage();
                        if (file_exists($old)) { @unlink($old); }
                    }
                    $Projet->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors du téléchargement de l'image.");
                }
            }

            if ($Projet->getCategorie() && !$Projet->getCategorieLabel()) {
                $Projet->setCategorieLabel($Projet->getCategorie()->getNom());
            }

            $em->flush();
            $this->addFlash('success', 'Projet modifié.');
            return $this->redirectToRoute('admin_Projet_index');
        }

        return $this->render('admin/Projet/edit.html.twig', [
            'Projet' => $Projet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Projet $Projet, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$Projet->getId(), $request->request->get('_token'))) {
            if ($Projet->getImage()) {
                $f = $this->getParameter('kernel.project_dir').'/public/uploads/Projets/'.$Projet->getImage();
                if (file_exists($f)) { @unlink($f); }
            }
            $em->remove($Projet);
            $em->flush();
            $this->addFlash('success', 'Projet supprimé.');
        }
        return $this->redirectToRoute('admin_Projet_index');
    }
}

