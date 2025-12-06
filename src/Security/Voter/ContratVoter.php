<?php

namespace App\Security\Voter;

use App\Entity\Contrat;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContratVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const SIGN = 'sign';
    const SIGN_ARTIST = 'sign_artist';
    const SIGN_CLIENT = 'sign_client';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW, 
            self::EDIT, 
            self::SIGN, 
            self::SIGN_ARTIST, 
            self::SIGN_CLIENT
        ]) && $subject instanceof Contrat;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Contrat $contrat */
        $contrat = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($contrat, $user),
            self::EDIT => $this->canEdit($contrat, $user),
            self::SIGN => $this->canSign($contrat, $user),
            self::SIGN_ARTIST => $this->canSignArtist($contrat, $user),
            self::SIGN_CLIENT => $this->canSignClient($contrat, $user),
            default => false,
        };
    }

    private function canView(Contrat $contrat, User $user): bool
    {
        // Admin peut tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Les parties du contrat peuvent le voir
        return $contrat->getArtiste()->getId() === $user->getId()
            || $contrat->getProducteur()->getId() === $user->getId();
    }

    private function canEdit(Contrat $contrat, User $user): bool
    {
        // Admin peut toujours éditer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Ne peut pas éditer si une signature a déjà été apposée
        if (!$contrat->canBeModified()) {
            return false;
        }

        // Seul l'artiste peut éditer le contrat (c'est lui qui le crée)
        return $contrat->getArtiste()->getId() === $user->getId();
    }

    private function canSign(Contrat $contrat, User $user): bool
    {
        return $this->canSignArtist($contrat, $user) 
            || $this->canSignClient($contrat, $user);
    }

    private function canSignArtist(Contrat $contrat, User $user): bool
    {
        // Doit être l'artiste du contrat
        if ($contrat->getArtiste()->getId() !== $user->getId()) {
            return false;
        }

        // Ne peut pas signer si déjà signé
        if ($contrat->isSignatureArtist()) {
            return false;
        }

        // Le contrat doit être en statut approprié
        return in_array($contrat->getStatut(), [
            Contrat::STATUT_BROUILLON,
            Contrat::STATUT_EN_ATTENTE_SIGNATURE
        ]);
    }

    private function canSignClient(Contrat $contrat, User $user): bool
    {
        // Doit être le client du contrat
        if ($contrat->getProducteur()->getId() !== $user->getId()) {
            return false;
        }

        // Ne peut pas signer si déjà signé
        if ($contrat->isSignatureClient()) {
            return false;
        }

        // Le contrat doit être en statut approprié
        return in_array($contrat->getStatut(), [
            Contrat::STATUT_BROUILLON,
            Contrat::STATUT_EN_ATTENTE_SIGNATURE
        ]);
    }
}
