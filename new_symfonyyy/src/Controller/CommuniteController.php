<?php

namespace App\Controller;

use App\Entity\Communite;
use App\Form\CommuniteType;
use App\Repository\CommuniteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/communite')]
class CommuniteController extends AbstractController
{
    #[Route('/', name: 'communite_index', methods: ['GET'])]
    public function index(CommuniteRepository $repo): Response
    {
        $communites = $repo->findAll();

        return $this->render('communite/index.html.twig', [
            'communites' => $communites,
        ]);
    }

    #[Route('/new', name: 'communite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $communite = new Communite();
        // Using static user since User module is static in this project
        $communite->setCreatedBy('user_static');
        $form = $this->createForm(CommuniteType::class, $communite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($communite);
            $em->flush();

            return $this->redirectToRoute('communite_index');
        }

        return $this->render('communite/new.html.twig', [
            'communite' => $communite,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'communite_show', methods: ['GET'])]
    public function show(Communite $communite): Response
    {
        return $this->render('communite/show.html.twig', [
            'communite' => $communite,
        ]);
    }

    #[Route('/{id}/edit', name: 'communite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Communite $communite, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommuniteType::class, $communite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('communite_index');
        }

        return $this->render('communite/edit.html.twig', [
            'communite' => $communite,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'communite_delete', methods: ['POST'])]
    public function delete(Request $request, Communite $communite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$communite->getId(), $request->request->get('_token'))) {
            $em->remove($communite);
            $em->flush();
        }

        return $this->redirectToRoute('communite_index');
    }
}
