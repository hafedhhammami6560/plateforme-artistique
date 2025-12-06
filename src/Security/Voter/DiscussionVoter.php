<?php

namespace App\Security\Voter;

use App\Entity\Discussion;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DiscussionVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const ADD_MESSAGE = 'add_message';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::ADD_MESSAGE])
            && $subject instanceof Discussion;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Discussion $discussion */
        $discussion = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($discussion, $user),
            self::EDIT => $this->canEdit($discussion, $user),
            self::ADD_MESSAGE => $this->canAddMessage($discussion, $user),
            default => false,
        };
    }

    private function canView(Discussion $discussion, User $user): bool
    {
        // Admin peut tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Participants peuvent voir
        return $discussion->getInitiateur()->getId() === $user->getId()
            || $discussion->getDestinataire()->getId() === $user->getId();
    }

    private function canEdit(Discussion $discussion, User $user): bool
    {
        // Admin peut tout éditer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul l'initiateur peut éditer
        return $discussion->getInitiateur()->getId() === $user->getId()
            && !$discussion->isTerminee();
    }

    private function canAddMessage(Discussion $discussion, User $user): bool
    {
        // Ne peut pas ajouter de message si terminée
        if ($discussion->isTerminee()) {
            return false;
        }

        // Admin peut ajouter
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Participants peuvent ajouter des messages
        return $discussion->getInitiateur()->getId() === $user->getId()
            || $discussion->getDestinataire()->getId() === $user->getId();
    }
}
