<?php

namespace App\Service;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\User;
use App\Entity\project;
use App\Entity\Contrat;
use Doctrine\ORM\EntityManagerInterface;

class DiscussionService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Crée une discussion Type A (Publication Rights) avec un project existant
     */
    public function creerDiscussionTypeA(
        User $initiateur,
        User $destinataire,
        project $project,
        string $titre,
        string $messageInitial
    ): Discussion {
        // Validation: Le project doit exister et être disponible
        if ($project->isSousContrat()) {
            throw new \InvalidArgumentException('Ce project est déjà sous contrat et n\'est pas disponible pour une nouvelle discussion.');
        }

        $discussion = new Discussion();
        $discussion->setType(Discussion::TYPE_PUBLICATION_RIGHTS);
        $discussion->setInitiateur($initiateur);
        $discussion->setDestinataire($destinataire);
        $discussion->setproject($project);
        $discussion->setTitre($titre);
        $discussion->setStatut(Discussion::STATUT_EN_COURS);

        $this->entityManager->persist($discussion);
        
        // Ajouter le message initial
        $message = new Message();
        $message->setDiscussion($discussion);
        $message->setAuteur($initiateur);
        $message->setContenu($messageInitial);
        
        $discussion->addMessage($message);
        $this->entityManager->persist($message);
        
        $this->entityManager->flush();

        return $discussion;
    }

    /**
     * Crée une discussion Type B (Custom Order) sans project
     */
    public function creerDiscussionTypeB(
        User $initiateur,
        User $destinataire,
        string $titre,
        string $messageInitial
    ): Discussion {
        $discussion = new Discussion();
        $discussion->setType(Discussion::TYPE_CUSTOM_ORDER);
        $discussion->setInitiateur($initiateur);
        $discussion->setDestinataire($destinataire);
        $discussion->setproject(null); // Pas de project pour Type B
        $discussion->setTitre($titre);
        $discussion->setStatut(Discussion::STATUT_EN_COURS);

        $this->entityManager->persist($discussion);
        
        // Ajouter le message initial
        $message = new Message();
        $message->setDiscussion($discussion);
        $message->setAuteur($initiateur);
        $message->setContenu($messageInitial);
        
        $discussion->addMessage($message);
        $this->entityManager->persist($message);
        
        $this->entityManager->flush();

        return $discussion;
    }

    /**
     * Ajoute un message à une discussion
     */
    public function ajouterMessage(Discussion $discussion, User $auteur, string $contenu): Message
    {
        if ($discussion->isTerminee()) {
            throw new \InvalidArgumentException('Cette discussion est terminée, vous ne pouvez plus ajouter de messages.');
        }

        // Vérifier que l'auteur fait partie de la discussion
        if ($discussion->getInitiateur()->getId() !== $auteur->getId() 
            && $discussion->getDestinataire()->getId() !== $auteur->getId()) {
            throw new \InvalidArgumentException('Vous n\'êtes pas autorisé à participer à cette discussion.');
        }

        $message = new Message();
        $message->setDiscussion($discussion);
        $message->setAuteur($auteur);
        $message->setContenu($contenu);
        
        $discussion->addMessage($message);
        $discussion->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * Lie un contrat à une discussion
     */
    public function lierContrat(Discussion $discussion, Contrat $contrat): void
    {
        if ($discussion->getContrat()) {
            throw new \InvalidArgumentException('Cette discussion a déjà un contrat associé.');
        }

        // Validation Type A: le project du contrat doit correspondre
        if ($discussion->isTypePublicationRights()) {
            if (!$discussion->getproject()) {
                throw new \InvalidArgumentException('Discussion Type A sans project - état invalide.');
            }
            if ($contrat->getproject()?->getId() !== $discussion->getproject()->getId()) {
                throw new \InvalidArgumentException('Le project du contrat doit correspondre au project de la discussion.');
            }
        }

        // Validation Type B: pas de project dans le contrat à la création
        if ($discussion->isTypeCustomOrder()) {
            if ($contrat->getproject()) {
                throw new \InvalidArgumentException('Pour une commande personnalisée, le project est créé après la signature du contrat.');
            }
        }

        $discussion->setContrat($contrat);
        $this->entityManager->flush();
    }

    /**
     * Termine une discussion
     */
    public function terminer(Discussion $discussion): void
    {
        $discussion->setStatut(Discussion::STATUT_TERMINEE);
        $discussion->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Marque les messages comme lus pour un utilisateur
     */
    public function marquerMessagesLus(Discussion $discussion, User $user): void
    {
        foreach ($discussion->getMessages() as $message) {
            if ($message->getAuteur()->getId() !== $user->getId() && !$message->isLu()) {
                $message->setLu(true);
            }
        }
        
        $this->entityManager->flush();
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function compterMessagesNonLus(User $user): int
    {
        return $this->entityManager->getRepository(Message::class)
            ->count([
                'lu' => false,
                // On devrait filtrer par discussions de l'utilisateur
            ]);
    }

    /**
     * Vérifie si un utilisateur peut accéder à une discussion
     */
    public function peutAcceder(Discussion $discussion, User $user): bool
    {
        return $discussion->getInitiateur()->getId() === $user->getId()
            || $discussion->getDestinataire()->getId() === $user->getId();
    }
}

