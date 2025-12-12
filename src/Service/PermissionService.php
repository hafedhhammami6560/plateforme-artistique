<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Discussion;
use App\Entity\Contrat;

/**
 * Service de gestion des permissions selon le type d'utilisateur
 */
class PermissionService
{
    /**
     * Définit les permissions pour les Discussions selon le type d'utilisateur
     * 
     * Règles:
     * - Artiste, Musicien, Scénariste: Peut créer des discussions de type "Custom Order" uniquement
     * - Publisher: Peut créer des discussions de type "Publication Rights" uniquement
     * - Sponsor: Peut créer les deux types de discussions
     * - Utilisateur Normal: Peut seulement consulter (pas de création)
     */
    public function canCreateDiscussion(User $user): bool
    {
        $userType = strtolower($user->getUserType() ?? '');
        
        // Utilisateur normal ne peut pas créer de discussions
        if ($userType === 'utilisateur') {
            return false;
        }
        
        // Tous les autres types peuvent créer des discussions
        return in_array($userType, ['artiste', 'musicien', 'scénariste', 'publisher', 'sponsor']);
    }

    /**
     * Détermine automatiquement le type de discussion selon l'initiateur et le destinataire
     * 
     * Règles:
     * - Sponsor lance → Type B (custom_order)
     * - Publisher lance → Type A (publication_rights)
     * - Visiteur/Utilisateur lance → Type B (custom_order)
     * - Artiste/Musicien/Scénariste avec Publisher → Type A (publication_rights)
     * - Artiste/Musicien/Scénariste avec Sponsor/Visiteur → Type B (custom_order)
     */
    public function determineDiscussionType(User $initiateur, User $destinataire): string
    {
        $initiateurType = strtolower($initiateur->getUserType() ?? '');
        $destinataireType = strtolower($destinataire->getUserType() ?? '');
        
        // Sponsor lance → Type B
        if ($initiateurType === 'sponsor') {
            return 'custom_order';
        }
        
        // Publisher lance → Type A
        if ($initiateurType === 'publisher') {
            return 'publication_rights';
        }
        
        // Visiteur/Utilisateur lance → Type B
        if (in_array($initiateurType, ['utilisateur', 'visiteur'])) {
            return 'custom_order';
        }
        
        // Artiste/Musicien/Scénariste
        if (in_array($initiateurType, ['artiste', 'musicien', 'scénariste'])) {
            // Avec Publisher → Type A
            if ($destinataireType === 'publisher') {
                return 'publication_rights';
            }
            // Avec Sponsor/Visiteur → Type B
            return 'custom_order';
        }
        
        // Par défaut → Type B
        return 'custom_order';
    }
    
    /**
     * Vérifie si un utilisateur peut créer une discussion de type spécifique
     */
    public function canCreateDiscussionType(User $user, string $discussionType): bool
    {
        $userType = strtolower($user->getUserType() ?? '');
        
        // Utilisateur normal ne peut rien créer
        if ($userType === 'utilisateur') {
            return false;
        }
        
        // Artiste, Musicien, Scénariste: Custom Order uniquement
        if (in_array($userType, ['artiste', 'musicien', 'scénariste'])) {
            return $discussionType === 'custom_order';
        }
        
        // Publisher: Publication Rights uniquement
        if ($userType === 'publisher') {
            return $discussionType === 'publication_rights';
        }
        
        // Sponsor: les deux types
        if ($userType === 'sponsor') {
            return in_array($discussionType, ['publication_rights', 'custom_order']);
        }
        
        return false;
    }

    /**
     * Vérifie si un utilisateur peut consulter une discussion
     */
    public function canViewDiscussion(User $user, Discussion $discussion): bool
    {
        // Un utilisateur peut voir une discussion s'il est:
        // 1. L'initiateur
        // 2. Le destinataire
        return $discussion->getInitiateur()->getId() === $user->getId() 
            || $discussion->getDestinataire()->getId() === $user->getId();
    }

    /**
     * Vérifie si un utilisateur peut envoyer des messages dans une discussion
     */
    public function canSendMessage(User $user, Discussion $discussion): bool
    {
        // Seuls l'initiateur et le destinataire peuvent envoyer des messages
        return $this->canViewDiscussion($user, $discussion);
    }

    /**
     * Vérifie si un utilisateur peut modifier une discussion
     */
    public function canEditDiscussion(User $user, Discussion $discussion): bool
    {
        // Seul l'initiateur peut modifier la discussion avant qu'elle ne soit acceptée
        return $discussion->getInitiateur()->getId() === $user->getId() 
            && $discussion->getStatut() === 'en_attente';
    }

    /**
     * Vérifie si un utilisateur peut supprimer une discussion
     */
    public function canDeleteDiscussion(User $user, Discussion $discussion): bool
    {
        // Seul l'initiateur peut supprimer la discussion si elle est en attente
        return $discussion->getInitiateur()->getId() === $user->getId() 
            && $discussion->getStatut() === 'en_attente';
    }

    /**
     * Définit les permissions pour les Contrats selon le type d'utilisateur
     * 
     * Règles:
     * - Artiste, Musicien, Scénariste: Peuvent créer et signer des contrats en tant qu'artiste
     * - Publisher: Peut créer des contrats en tant que producteur
     * - Sponsor: Peut créer des contrats en tant que producteur
     * - Utilisateur Normal: Peut seulement consulter (pas de création)
     */
    public function canCreateContrat(User $user): bool
    {
        $userType = strtolower($user->getUserType() ?? '');
        
        // Utilisateur normal ne peut pas créer de contrats
        if ($userType === 'utilisateur') {
            return false;
        }
        
        // Tous les autres types peuvent créer des contrats
        return in_array($userType, ['artiste', 'musicien', 'scénariste', 'publisher', 'sponsor']);
    }

    /**
     * Vérifie si un utilisateur peut consulter un contrat
     */
    public function canViewContrat(User $user, Contrat $contrat): bool
    {
        // Un utilisateur peut voir un contrat s'il est:
        // 1. Le producteur
        // 2. L'artiste
        return $contrat->getProducteur()->getId() === $user->getId() 
            || $contrat->getArtiste()->getId() === $user->getId();
    }

    /**
     * Vérifie si un utilisateur peut signer un contrat
     */
    public function canSignContrat(User $user, Contrat $contrat): bool
    {
        $userType = strtolower($user->getUserType() ?? '');
        
        // L'utilisateur normal ne peut pas signer
        if ($userType === 'utilisateur') {
            return false;
        }
        
        // Le producteur peut signer si ce n'est pas déjà fait
        if ($contrat->getProducteur()->getId() === $user->getId()) {
            return !$contrat->isSigneProducteur();
        }
        
        // L'artiste peut signer si ce n'est pas déjà fait
        if ($contrat->getArtiste()->getId() === $user->getId()) {
            return !$contrat->isSigneArtiste();
        }
        
        return false;
    }

    /**
     * Vérifie si un utilisateur peut modifier un contrat
     */
    public function canEditContrat(User $user, Contrat $contrat): bool
    {
        // Seul le producteur peut modifier le contrat avant signature
        return $contrat->getProducteur()->getId() === $user->getId() 
            && $contrat->getStatut() === 'en_attente';
    }

    /**
     * Vérifie si un utilisateur peut supprimer un contrat
     */
    public function canDeleteContrat(User $user, Contrat $contrat): bool
    {
        // Seul le producteur peut supprimer le contrat s'il est en attente
        return $contrat->getProducteur()->getId() === $user->getId() 
            && $contrat->getStatut() === 'en_attente';
    }

    /**
     * Récupère les types de discussions disponibles pour un utilisateur
     */
    public function getAvailableDiscussionTypes(User $user): array
    {
        $userType = strtolower($user->getUserType() ?? '');
        
        $allTypes = [
            'publication_rights' => 'Type A: droits sur projet existant',
            'custom_order' => 'Type B: commande œuvre sur mesure'
        ];
        
        // Utilisateur normal: aucun type disponible
        if ($userType === 'utilisateur') {
            return [];
        }
        
        // Artiste, Musicien, Scénariste: Custom Order uniquement
        if (in_array($userType, ['artiste', 'musicien', 'scénariste'])) {
            return ['custom_order' => $allTypes['custom_order']];
        }
        
        // Publisher: Publication Rights uniquement
        if ($userType === 'publisher') {
            return ['publication_rights' => $allTypes['publication_rights']];
        }
        
        // Sponsor: les deux types
        if ($userType === 'sponsor') {
            return $allTypes;
        }
        
        return [];
    }

    /**
     * Récupère un message d'erreur personnalisé selon le type d'utilisateur
     */
    public function getPermissionDeniedMessage(User $user, string $action): string
    {
        $userType = $user->getUserType() ?? 'utilisateur';
        
        $messages = [
            'create_discussion' => [
                'utilisateur' => 'Les utilisateurs normaux ne peuvent pas créer de discussions. Veuillez contacter un artiste ou un sponsor.',
                'artiste' => 'Les artistes peuvent uniquement créer des discussions de type "Custom Order".',
                'musicien' => 'Les musiciens peuvent uniquement créer des discussions de type "Custom Order".',
                'scénariste' => 'Les scénaristes peuvent uniquement créer des discussions de type "Custom Order".',
                'publisher' => 'Les publishers peuvent uniquement créer des discussions de type "Publication Rights".',
            ],
            'create_contrat' => [
                'utilisateur' => 'Les utilisateurs normaux ne peuvent pas créer de contrats.',
            ]
        ];
        
        return $messages[$action][strtolower($userType)] ?? 'Vous n\'avez pas la permission d\'effectuer cette action.';
    }
}
