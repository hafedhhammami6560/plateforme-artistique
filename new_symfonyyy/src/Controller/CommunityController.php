<?php

namespace App\Controller;

use App\Entity\Community;
use App\Form\CommunityType;
use App\Repository\CommunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/community')]
class CommunityController extends AbstractController
{
    #[Route('/', name: 'app_community_index', methods: ['GET'])]
    public function index(Request $request, CommunityRepository $communityRepository): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $privacy = $request->query->get('privacy', '');
        $sortBy = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'ASC');

        $queryBuilder = $communityRepository->createQueryBuilder('c');

        // Filtrage par recherche
        if ($search) {
            $queryBuilder->andWhere('c.name LIKE :search OR c.slug LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtrage par type
        if ($type) {
            $queryBuilder->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        // Filtrage par privacy
        if ($privacy !== '') {
            $queryBuilder->andWhere('c.isPrivate = :privacy')
                ->setParameter('privacy', $privacy === '1');
        }

        // Tri
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
        $community = new Community();
        $form = $this->createForm(CommunityType::class, $community);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($community);
            $entityManager->flush();

            $this->addFlash('success', 'Community created successfully!');
            return $this->redirectToRoute('app_community_index');
        }

        return $this->render('community/new.html.twig', [
            'community' => $community,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_community_show', methods: ['GET'])]
    public function show(Community $community): Response
    {
        return $this->render('community/show.html.twig', [
            'community' => $community,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_community_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Community $community, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommunityType::class, $community);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Community updated successfully!');
            return $this->redirectToRoute('app_community_index');
        }

        return $this->render('community/edit.html.twig', [
            'community' => $community,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_community_delete', methods: ['POST'])]
    public function delete(Request $request, Community $community, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$community->getId(), $request->request->get('_token'))) {
            $entityManager->remove($community);
            $entityManager->flush();
            $this->addFlash('success', 'Community deleted successfully!');
        }

        return $this->redirectToRoute('app_community_index');
    }
}
