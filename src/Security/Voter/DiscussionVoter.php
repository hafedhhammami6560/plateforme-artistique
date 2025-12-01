<?php

namespace App\Security\Voter;

use App\Entity\Discussion;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour gérer les permissions sur les discussions
 */
class DiscussionVoter extends Voter
{
    public const VIEW = 'DISCUSSION_VIEW';
    public const EDIT = 'DISCUSSION_EDIT';
    public const DELETE = 'DISCUSSION_DELETE';
    public const SEND_MESSAGE = 'DISCUSSION_SEND_MESSAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter ne gère que les attributs spécifiques aux discussions
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::SEND_MESSAGE])) {
            return false;
        }

        // Ce voter ne gère que les objets Discussion
        if (!$subject instanceof Discussion) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        /** @var Discussion $discussion */
        $discussion = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($discussion, $user),
            self::EDIT => $this->canEdit($discussion, $user),
            self::DELETE => $this->canDelete($discussion, $user),
            self::SEND_MESSAGE => $this->canSendMessage($discussion, $user),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir la discussion
     * Un utilisateur peut voir une discussion s'il en est participant
     */
    private function canView(Discussion $discussion, User $user): bool
    {
        // Les admins peuvent tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // L'utilisateur doit être participant (artiste ou publisher)
        return $discussion->isParticipant($user);
    }

    /**
     * Vérifie si l'utilisateur peut éditer la discussion
     * Admin: oui
     * Publisher: oui sauf si archivée
     * Artiste: non
     */
    private function canEdit(Discussion $discussion, User $user): bool
    {
        // Les admins peuvent tout éditer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Les publishers ne peuvent pas éditer si archivée
        if ($user->isPublisher()) {
            if ($discussion->getStatus() === Discussion::STATUS_ARCHIVED) {
                return false;
            }
            // Le publisher doit être participant
            return $discussion->getPublisher() === $user;
        }

        // Les artistes ne peuvent pas éditer
        return false;
    }

    /**
     * Vérifie si l'utilisateur peut supprimer la discussion
     * Seuls les participants peuvent supprimer si aucun message n'a été envoyé
     */
    private function canDelete(Discussion $discussion, User $user): bool
    {
        // Les admins peuvent tout supprimer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // La discussion ne peut être supprimée que si elle n'a pas de messages
        if ($discussion->getMessageCount() > 0) {
            return false;
        }

        // La discussion ne peut être supprimée que si elle n'a pas de contrat
        if ($discussion->hasContract()) {
            return false;
        }

        // Seul le publisher (initiateur) peut supprimer une discussion vide
        return $discussion->getPublisher() === $user;
    }

    /**
     * Vérifie si l'utilisateur peut envoyer un message dans la discussion
     */
    private function canSendMessage(Discussion $discussion, User $user): bool
    {
        // On ne peut pas envoyer de message dans une discussion fermée ou archivée
        if ($discussion->isClosed() || $discussion->getStatus() === Discussion::STATUS_ARCHIVED) {
            return false;
        }

        // L'utilisateur doit être participant
        return $discussion->isParticipant($user);
    }
}
