<?php

namespace App\Service;

use App\Entity\Contrat;
use App\Entity\project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ContratService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Génère un numéro de contrat unique au format CTR-YYYYMMDD-XXXXX
     */
    public function genererNumeroContrat(): string
    {
        $date = new \DateTimeImmutable();
        $prefix = 'CTR-' . $date->format('Ymd') . '-';
        
        // Trouver le dernier numéro du jour
        $lastContrat = $this->entityManager->getRepository(Contrat::class)
            ->createQueryBuilder('c')
            ->where('c.numeroContrat LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('c.numeroContrat', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastContrat) {
            // Extraire le numéro et l'incrémenter
            $lastNumber = (int) substr($lastContrat->getNumeroContrat(), -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Crée un nouveau contrat avec validation selon le type
     */
    public function creerContrat(
        User $artist,
        User $client,
        string $type,
        string $prix,
        string $conditionsTexte,
        \DateTimeImmutable $dateDebut,
        \DateTimeImmutable $dateFin,
        ?project $project = null
    ): Contrat {
        // Validation Type A: doit avoir un project
        if ($type === Contrat::TYPE_PUBLICATION_RIGHTS && !$project) {
            throw new \InvalidArgumentException('Un contrat de type Publication Rights doit avoir un project associé.');
        }

        // Validation Type B: ne doit PAS avoir de project
        if ($type === Contrat::TYPE_CUSTOM_ORDER && $project) {
            throw new \InvalidArgumentException('Un contrat de type Custom Order ne peut pas avoir de project lors de sa création.');
        }

        // Vérifier que le project n'a pas déjà un contrat actif
        if ($project && $project->isSousContrat()) {
            throw new \InvalidArgumentException('Ce project est déjà sous contrat.');
        }

        $contrat = new Contrat();
        $contrat->setNumeroContrat($this->genererNumeroContrat());
        $contrat->setType($type);
        $contrat->setArtiste($artist);
        $contrat->setProducteur($client);
        $contrat->setPrix($prix);
        $contrat->setMontant((float) $prix); // Compatibilité avec l'ancien champ
        $contrat->setConditionsTexte($conditionsTexte);
        $contrat->setTermes($conditionsTexte); // Compatibilité avec l'ancien champ
        $contrat->setDateDebut($dateDebut);
        $contrat->setDateFin($dateFin);
        $contrat->setStatut(Contrat::STATUT_BROUILLON);

        if ($project) {
            $contrat->setproject($project);
        }

        $this->entityManager->persist($contrat);

        return $contrat;
    }

    /**
     * Signature par l'artiste
     */
    public function signerParArtist(Contrat $contrat, User $artist): void
    {
        if ($contrat->getArtiste()->getId() !== $artist->getId()) {
            throw new \InvalidArgumentException('Seul l\'artiste du contrat peut signer en tant qu\'artiste.');
        }

        if ($contrat->isSignatureArtist()) {
            throw new \InvalidArgumentException('L\'artiste a déjà signé ce contrat.');
        }

        $contrat->setSignatureArtist(true);
        
        // Si les deux ont signé, finaliser le contrat
        if ($contrat->isFullySigned()) {
            $this->finaliserContrat($contrat);
        } else {
            $contrat->setStatut(Contrat::STATUT_EN_ATTENTE_SIGNATURE);
        }

        $this->entityManager->flush();
    }

    /**
     * Signature par le client (publisher ou sponsor)
     */
    public function signerParClient(Contrat $contrat, User $client): void
    {
        if ($contrat->getProducteur()->getId() !== $client->getId()) {
            throw new \InvalidArgumentException('Seul le client du contrat peut signer en tant que client.');
        }

        if ($contrat->isSignatureClient()) {
            throw new \InvalidArgumentException('Le client a déjà signé ce contrat.');
        }

        $contrat->setSignatureClient(true);
        
        // Si les deux ont signé, finaliser le contrat
        if ($contrat->isFullySigned()) {
            $this->finaliserContrat($contrat);
        } else {
            $contrat->setStatut(Contrat::STATUT_EN_ATTENTE_SIGNATURE);
        }

        $this->entityManager->flush();
    }

    /**
     * Finalise le contrat après double signature
     */
    private function finaliserContrat(Contrat $contrat): void
    {
        $contrat->setStatut(Contrat::STATUT_SIGNE);
        $contrat->setDateSignature(new \DateTimeImmutable());

        // Workflow Type A: Marquer le project comme "sous contrat"
        if ($contrat->isTypePublicationRights() && $contrat->getproject()) {
            $project = $contrat->getproject();
            $project->marquerSousContrat($contrat);
        }

        // Workflow Type B: Le project sera créé après par l'artiste
        // Pas d'action automatique ici
    }

    /**
     * Vérifie si un utilisateur peut modifier le contrat
     */
    public function peutModifier(Contrat $contrat): bool
    {
        return $contrat->canBeModified();
    }

    /**
     * Associe un project à un contrat Type B après signature
     */
    public function associerprojectTypeB(Contrat $contrat, project $project): void
    {
        if (!$contrat->isTypeCustomOrder()) {
            throw new \InvalidArgumentException('Seul un contrat de type Custom Order peut recevoir un project après signature.');
        }

        if (!$contrat->isFullySigned()) {
            throw new \InvalidArgumentException('Le contrat doit être entièrement signé avant d\'associer un project.');
        }

        if ($contrat->getproject()) {
            throw new \InvalidArgumentException('Ce contrat a déjà un project associé.');
        }

        $contrat->setproject($project);
        $project->marquerSousContrat($contrat);
        $project->setStatut('en_production');

        $this->entityManager->flush();
    }
}

