<?php
/**
 * Contrôleur FeedbackController
 * 
 * Gère le CRUD pour l'entité Feedback (retours utilisateurs)
 * Fonctionnalités: recherche, filtrage par type/statut, tri
 * 
 * Routes disponibles:
 * - GET  /feedback/           : Liste avec recherche et filtres
 * - GET  /feedback/new        : Formulaire de création
 * - POST /feedback/new        : Traitement création
 * - GET  /feedback/{id}       : Affichage détaillé
 * - GET  /feedback/{id}/edit  : Formulaire édition
 * - POST /feedback/{id}/edit  : Traitement édition
 * - POST /feedback/{id}       : Suppression
 */
namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/feedback')]
class FeedbackController extends AbstractController
{
    /**
     * Action INDEX - Liste des feedbacks avec recherche et filtres
     */
    #[Route('/', name: 'feedback_index', methods: ['GET'])]
    public function index(Request $request, FeedbackRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $status = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'DESC');

        $queryBuilder = $repo->createQueryBuilder('f');

        // Recherche sur contenu et nom d'auteur
        if ($search) {
            $queryBuilder->andWhere('f.content LIKE :search OR f.authorName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par type
        if ($type) {
            $queryBuilder->andWhere('f.type = :type')
                ->setParameter('type', $type);
        }

        // Filtre par statut
        if ($status) {
            $queryBuilder->andWhere('f.status = :status')
                ->setParameter('status', $status);
        }

        // Tri
        $validSorts = ['createdAt', 'rating', 'authorName', 'type', 'status'];
        if (in_array($sortBy, $validSorts)) {
            $queryBuilder->orderBy('f.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');
        }

        $feedbacks = $queryBuilder->getQuery()->getResult();

        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'search' => $search,
            'type' => $type,
            'status' => $status,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    /**
     * Action NEW - Création d'un nouveau feedback
     */
    #[Route('/new', name: 'feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $feedback = new Feedback();
        $feedback->setAuthorName('user_static');

        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($feedback);
                $em->flush();

                $this->addFlash('success', 'Feedback créé avec succès !');
                return $this->redirectToRoute('feedback_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Action SHOW - Affichage détaillé d'un feedback
     */
    #[Route('/{id}', name: 'feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * Action EDIT - Modification d'un feedback existant
     */
    #[Route('/{id}/edit', name: 'feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', 'Feedback modifié avec succès !');
                return $this->redirectToRoute('feedback_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Action DELETE - Suppression d'un feedback
     */
    #[Route('/{id}', name: 'feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($feedback);
                $em->flush();
                $this->addFlash('success', 'Feedback supprimé avec succès !');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('feedback_index');
    }
}
