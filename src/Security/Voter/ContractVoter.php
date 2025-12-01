<?php

namespace App\Security\Voter;

use App\Entity\Contract;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour gérer les permissions sur les contrats
 */
class ContractVoter extends Voter
{
    public const VIEW = 'CONTRACT_VIEW';
    public const EDIT = 'CONTRACT_EDIT';
    public const DELETE = 'CONTRACT_DELETE';
    public const SIGN = 'CONTRACT_SIGN';
    public const TERMINATE = 'CONTRACT_TERMINATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter ne gère que les attributs spécifiques aux contrats
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::SIGN, self::TERMINATE])) {
            return false;
        }

        // Ce voter ne gère que les objets Contract
        if (!$subject instanceof Contract) {
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

        /** @var Contract $contract */
        $contract = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($contract, $user),
            self::EDIT => $this->canEdit($contract, $user),
            self::DELETE => $this->canDelete($contract, $user),
            self::SIGN => $this->canSign($contract, $user),
            self::TERMINATE => $this->canTerminate($contract, $user),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir le contrat
     * Un utilisateur peut voir un contrat s'il est participant de la discussion liée
     */
    private function canView(Contract $contract, User $user): bool
    {
        // Les admins peuvent tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // L'utilisateur doit être participant de la discussion
        $discussion = $contract->getDiscussion();
        if (!$discussion) {
            return false;
        }

        return $discussion->isParticipant($user);
    }

    /**
     * Vérifie si l'utilisateur peut éditer le contrat
     * Admin: non (seulement voir et supprimer)
     * Publisher: oui sauf si terminé ou archivé
     * Artiste: non
     */
    private function canEdit(Contract $contract, User $user): bool
    {
        // Les admins ne peuvent PAS éditer (seulement voir et supprimer)
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return false;
        }

        // Un contrat terminé ne peut pas être édité
        if ($contract->isTerminated()) {
            return false;
        }

        // Un contrat signé après une certaine période ne peut pas être édité
        if ($contract->isSigned() && $contract->getStatus() === Contract::STATUS_ACTIVE) {
            return false;
        }

        // Seul le publisher (créateur du contrat) peut éditer
        $discussion = $contract->getDiscussion();
        if (!$discussion) {
            return false;
        }

        // Le publisher peut éditer si le contrat n'est pas terminé
        if ($discussion->getPublisher() === $user && $user->isPublisher()) {
            return $contract->getStatus() !== Contract::STATUS_TERMINATED;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut supprimer le contrat
     * Seul un contrat en brouillon peut être supprimé, et seulement par le publisher
     */
    private function canDelete(Contract $contract, User $user): bool
    {
        // Les admins peuvent tout supprimer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seuls les contrats en brouillon peuvent être supprimés
        if ($contract->getStatus() !== Contract::STATUS_DRAFT) {
            return false;
        }

        // Seul le publisher peut supprimer
        $discussion = $contract->getDiscussion();
        if (!$discussion) {
            return false;
        }

        return $discussion->getPublisher() === $user;
    }

    /**
     * Vérifie si l'utilisateur peut signer le contrat
     * Seul l'artiste peut signer un contrat proposé
     */
    private function canSign(Contract $contract, User $user): bool
    {
        // Le contrat doit être en statut "proposé" pour être signé
        if (!$contract->canBeSigned()) {
            return false;
        }

        // Seul l'artiste peut signer le contrat
        $discussion = $contract->getDiscussion();
        if (!$discussion) {
            return false;
        }

        // Vérifier que l'utilisateur est bien l'artiste
        if ($discussion->getArtist() !== $user) {
            return false;
        }

        // L'artiste ne peut pas signer si le contrat est déjà signé
        if ($contract->isSigned()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut terminer le contrat
     * Les deux parties ou un admin peuvent terminer un contrat actif
     */
    private function canTerminate(Contract $contract, User $user): bool
    {
        // Les admins peuvent terminer n'importe quel contrat
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seuls les contrats actifs ou signés peuvent être terminés
        if (!$contract->isActive() && !$contract->isSigned()) {
            return false;
        }

        // Les deux parties peuvent terminer le contrat
        $discussion = $contract->getDiscussion();
        if (!$discussion) {
            return false;
        }

        return $discussion->isParticipant($user);
    }
}
