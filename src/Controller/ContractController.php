<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Discussion;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use App\Security\Voter\ContractVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contract')]
#[IsGranted('ROLE_USER')]
class ContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContractRepository $contractRepository
    ) {
    }

    /**
     * Liste tous les contrats de l'utilisateur connecté
     */
    #[Route('/', name: 'app_contract_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_index');
        }
        $user = $this->getUser();
        $statusFilter = $request->query->get('status');

        if ($statusFilter) {
            $contracts = $this->contractRepository->findForUser($user, $statusFilter);
        } else {
            $contracts = $this->contractRepository->findForUser($user);
        }

        // Statistiques pour l'utilisateur
        $stats = $this->contractRepository->countByStatusForUser($user);
        $stats['total'] = array_sum($stats);

        return $this->render('contract/index.html.twig', [
            'contracts' => $contracts,
            'stats' => $stats,
            'currentFilter' => $statusFilter,
        ]);
    }

    /**
     * Création d'un nouveau contrat depuis une discussion
     */
    #[Route('/new', name: 'app_contract_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PUBLISHER')]
    public function new(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_index');
        }
        $contract = new Contract();
        $contract->setStatus(Contract::STATUS_DRAFT);

        $form = $this->createForm(ContractType::class, $contract, [
            'show_discussion_field' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que l'utilisateur est bien le publisher de la discussion
            $discussion = $contract->getDiscussion();
            if ($discussion->getPublisher() !== $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez créer un contrat que pour vos propres discussions.');
                return $this->redirectToRoute('app_contract_new');
            }

            $this->entityManager->persist($contract);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été créé en brouillon.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

            if ($form->isSubmitted() && !$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                if ($errors) {
                    $this->addFlash('error', 'Le formulaire de contrat contient des erreurs: ' . implode(' | ', $errors));
                }
            }

        return $this->render('contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    /**
     * Création d'un contrat depuis une discussion spécifique
     */
    #[Route('/new/discussion/{id}', name: 'app_contract_new_from_discussion', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PUBLISHER')]
    public function newFromDiscussion(Request $request, Discussion $discussion): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_index');
        }
        // Vérifier que la discussion n'a pas déjà un contrat
        if ($discussion->hasContract()) {
            $this->addFlash('warning', 'Cette discussion a déjà un contrat.');
            return $this->redirectToRoute('app_contract_show', ['id' => $discussion->getContract()->getId()]);
        }

        // Vérifier que l'utilisateur est le publisher
        if ($discussion->getPublisher() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez créer un contrat que pour vos propres discussions.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        $contract = new Contract();
        $contract->setDiscussion($discussion);
        $contract->setStatus(Contract::STATUS_DRAFT);

        $form = $this->createForm(ContractType::class, $contract, [
            'show_discussion_field' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contract);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été créé en brouillon.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
            'discussion' => $discussion,
        ]);
    }

    /**
     * Affiche les détails d'un contrat
     */
    #[Route('/{id}', name: 'app_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::VIEW, $contract);

        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    /**
     * Édition d'un contrat
     */
    #[Route('/{id}/edit', name: 'app_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_edit', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::EDIT, $contract);

        $form = $this->createForm(ContractType::class, $contract, [
            'show_discussion_field' => false,
            'show_status_field' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été mis à jour.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    /**
     * Proposer le contrat à l'artiste
     */
    #[Route('/{id}/propose', name: 'app_contract_propose', methods: ['POST'])]
    #[IsGranted('ROLE_PUBLISHER')]
    public function propose(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::EDIT, $contract);

        if ($this->isCsrfTokenValid('propose' . $contract->getId(), $request->request->get('_token'))) {
            if ($contract->getStatus() === Contract::STATUS_DRAFT) {
                $contract->setStatus(Contract::STATUS_PROPOSED);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le contrat a été proposé à l\'artiste.');
            } else {
                $this->addFlash('error', 'Seul un contrat en brouillon peut être proposé.');
            }
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    /**
     * Signature du contrat par l'artiste
     */
    #[Route('/{id}/sign', name: 'app_contract_sign', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function sign(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::SIGN, $contract);

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('sign' . $contract->getId(), $request->request->get('_token'))) {
                $contract->sign($this->getUser());
                $this->entityManager->flush();

                $this->addFlash('success', 'Vous avez signé le contrat avec succès.');

                return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
            }
        }

        return $this->render('contract/sign.html.twig', [
            'contract' => $contract,
        ]);
    }

    /**
     * Activer un contrat signé
     */
    #[Route('/{id}/activate', name: 'app_contract_activate', methods: ['POST'])]
    #[IsGranted('ROLE_PUBLISHER')]
    public function activate(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::EDIT, $contract);

        if ($this->isCsrfTokenValid('activate' . $contract->getId(), $request->request->get('_token'))) {
            if ($contract->getStatus() === Contract::STATUS_SIGNED) {
                $contract->setStatus(Contract::STATUS_ACTIVE);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le contrat a été activé.');
            } else {
                $this->addFlash('error', 'Seul un contrat signé peut être activé.');
            }
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    /**
     * Terminer un contrat
     */
    #[Route('/{id}/terminate', name: 'app_contract_terminate', methods: ['POST'])]
    public function terminate(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_show', ['id' => $contract->getId()]);
        }
        $this->denyAccessUnlessGranted(ContractVoter::TERMINATE, $contract);

        if ($this->isCsrfTokenValid('terminate' . $contract->getId(), $request->request->get('_token'))) {
            $contract->setStatus(Contract::STATUS_TERMINATED);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été terminé.');
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    /**
     * Suppression d'un contrat
     */
    #[Route('/{id}/delete', name: 'app_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_contract_index');
        }
        $this->denyAccessUnlessGranted(ContractVoter::DELETE, $contract);

        if ($this->isCsrfTokenValid('delete' . $contract->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($contract);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contrat a été supprimé.');
        }

        return $this->redirectToRoute('app_contract_index');
    }
}
