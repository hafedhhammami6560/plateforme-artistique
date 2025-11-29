<?php

namespace App\Controller;

use App\Entity\Organisation;
use App\Form\OrganisationType;
use App\Repository\OrganisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisation')]
class OrganisationController extends AbstractController
{
    #[Route('/', name: 'organisation_index', methods: ['GET'])]
    public function index(OrganisationRepository $repo): Response
    {
        $organisations = $repo->findAll();

        return $this->render('organisation/index.html.twig', [
            'organisations' => $organisations,
        ]);
    }

    #[Route('/new', name: 'organisation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $organisation = new Organisation();
        // Using static user since User module is static in this project
        $organisation->setCreatedBy('user_static');
        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($organisation);
            $em->flush();

            return $this->redirectToRoute('organisation_index');
        }

        return $this->render('organisation/new.html.twig', [
            'organisation' => $organisation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'organisation_show', methods: ['GET'])]
    public function show(Organisation $organisation): Response
    {
        return $this->render('organisation/show.html.twig', [
            'organisation' => $organisation,
        ]);
    }

    #[Route('/{id}/edit', name: 'organisation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Organisation $organisation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('organisation_index');
        }

        return $this->render('organisation/edit.html.twig', [
            'organisation' => $organisation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'organisation_delete', methods: ['POST'])]
    public function delete(Request $request, Organisation $organisation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organisation->getId(), $request->request->get('_token'))) {
            $em->remove($organisation);
            $em->flush();
        }

        return $this->redirectToRoute('organisation_index');
    }
}
