# Système de Permissions - Contrats & Discussions

## Vue d'ensemble

Le système de permissions contrôle l'accès aux fonctionnalités des modules **Discussions** et **Contrats** selon le **type d'utilisateur**.

## Types d'utilisateurs

### 1. **Artiste, Musicien, Scénariste** (Créateurs de contenu)
- **Discussions**:
  - ✅ Peuvent créer des discussions de type **Custom Order** (Type B) uniquement
  - ❌ Ne peuvent pas créer de discussions de type **Publication Rights** (Type A)
  - ✅ Peuvent envoyer/recevoir des messages dans leurs discussions
  
- **Contrats**:
  - ✅ Peuvent créer des contrats en tant qu'**artiste**
  - ✅ Peuvent signer des contrats en tant qu'artiste
  - ✅ Peuvent modifier leurs contrats tant qu'ils ne sont pas signés

**Cas d'usage**: Un artiste veut commander une œuvre personnalisée à un autre artiste ou recevoir des commandes de sponsors.

---

### 2. **Publisher** (Éditeur/Distributeur)
- **Discussions**:
  - ✅ Peuvent créer des discussions de type **Publication Rights** (Type A) uniquement
  - ❌ Ne peuvent pas créer de discussions de type **Custom Order** (Type B)
  - ✅ Doivent spécifier un produit existant lors de la création
  
- **Contrats**:
  - ✅ Peuvent créer des contrats en tant que **producteur**
  - ✅ Peuvent signer des contrats en tant que producteur
  - ✅ Peuvent modifier leurs contrats tant qu'ils ne sont pas signés

**Cas d'usage**: Un publisher veut acquérir les droits de publication d'un album ou d'une œuvre existante.

---

### 3. **Sponsor** (Investisseur/Mécène)
- **Discussions**:
  - ✅ Peuvent créer **les deux types** de discussions
  - ✅ Type A: Pour acquérir des droits sur des produits existants
  - ✅ Type B: Pour commander des œuvres personnalisées
  
- **Contrats**:
  - ✅ Peuvent créer des contrats en tant que **producteur**
  - ✅ Peuvent signer des contrats
  - ✅ Accès complet aux fonctionnalités de contrats

**Cas d'usage**: Un sponsor peut à la fois investir dans des œuvres existantes et commander de nouvelles créations.

---

### 4. **Utilisateur Normal** (Spectateur)
- **Discussions**:
  - ❌ Ne peuvent pas créer de discussions
  - ✅ Peuvent consulter les discussions publiques (si implémenté)
  
- **Contrats**:
  - ❌ Ne peuvent pas créer de contrats
  - ❌ Ne peuvent pas signer de contrats
  - ✅ Peuvent consulter les contrats publics (si implémenté)

**Cas d'usage**: Utilisateurs qui naviguent sur la plateforme sans participer activement aux transactions.

---

## Matrice des permissions

| Action | Artiste/Musicien/Scénariste | Publisher | Sponsor | Utilisateur Normal |
|--------|---------------------------|-----------|---------|-------------------|
| **Créer Discussion Type A** | ❌ | ✅ | ✅ | ❌ |
| **Créer Discussion Type B** | ✅ | ❌ | ✅ | ❌ |
| **Envoyer messages** | ✅ | ✅ | ✅ | ❌ |
| **Créer Contrat** | ✅ | ✅ | ✅ | ❌ |
| **Signer Contrat** | ✅ | ✅ | ✅ | ❌ |
| **Modifier Contrat** | ✅ (si créateur) | ✅ (si créateur) | ✅ (si créateur) | ❌ |
| **Consulter** | ✅ (ses propres) | ✅ (ses propres) | ✅ (ses propres) | ⚠️ (limité) |

---

## Flux de travail par type

### Workflow Type A - Publication Rights (Publisher/Sponsor → Artiste)

1. **Publisher ou Sponsor** crée une discussion de type A
2. Sélectionne un **produit existant** dans la liste
3. Spécifie l'**artiste** propriétaire du produit
4. Négocie les **termes** via messages
5. Crée un **contrat** depuis la discussion
6. Les deux parties **signent** le contrat
7. Les droits sont transférés selon les termes

### Workflow Type B - Custom Order (Artiste → Sponsor ou Artiste → Artiste)

1. **Artiste, Musicien ou Scénariste** crée une discussion de type B
2. Spécifie le **sponsor ou autre artiste** destinataire
3. Décrit l'**œuvre personnalisée** souhaitée
4. Négocie les **spécifications et prix**
5. Crée un **contrat** depuis la discussion
6. Les deux parties **signent**
7. L'artiste crée l'œuvre après signature

---

## Restrictions et validation

### Au niveau du formulaire
- Le formulaire de discussion affiche **uniquement les types autorisés** selon l'utilisateur
- Les champs obligatoires changent selon le type (produit pour Type A)

### Au niveau du contrôleur
- Vérification avant affichage du formulaire
- Validation lors de la soumission
- Messages d'erreur personnalisés par type d'utilisateur

### Au niveau de la base de données
- Contraintes d'intégrité référentielle
- Validation des statuts de contrat
- Horodatage de toutes les actions

---

## Messages d'erreur personnalisés

Chaque type d'utilisateur reçoit des messages d'erreur adaptés:

- **Artiste essayant de créer Type A**: 
  > "Les artistes peuvent uniquement créer des discussions de type 'Custom Order'."

- **Publisher essayant de créer Type B**:
  > "Les publishers peuvent uniquement créer des discussions de type 'Publication Rights'."

- **Utilisateur normal**:
  > "Les utilisateurs normaux ne peuvent pas créer de discussions. Veuillez contacter un artiste ou un sponsor."

---

## Implémentation technique

### Service de permissions
```php
App\Service\PermissionService
```

Méthodes principales:
- `canCreateDiscussion(User $user): bool`
- `canCreateDiscussionType(User $user, string $type): bool`
- `canViewDiscussion(User $user, Discussion $discussion): bool`
- `canSendMessage(User $user, Discussion $discussion): bool`
- `canCreateContrat(User $user): bool`
- `canSignContrat(User $user, Contrat $contrat): bool`

### Intégration dans les contrôleurs
- `DiscussionController`: Vérifie les permissions à chaque action
- `ContratController`: Vérifie les permissions avant création/modification/signature

### Intégration dans les formulaires
- `DiscussionType`: Filtre les types de discussion disponibles
- `ContratType`: Adapte les champs selon le contexte

---

## Évolutions futures possibles

1. **Rôles administratifs**: Ajout d'un admin avec tous les droits
2. **Permissions granulaires**: Système de permissions plus fin (ACL)
3. **Délégation**: Permettre aux utilisateurs de déléguer certaines actions
4. **Audit trail**: Traçabilité complète de toutes les actions
5. **Permissions temporaires**: Accès limité dans le temps
6. **Groupes d'utilisateurs**: Gestion de permissions par groupe

---

## Tests recommandés

### Tests fonctionnels
- ✅ Artiste ne peut créer que Type B
- ✅ Publisher ne peut créer que Type A
- ✅ Sponsor peut créer les deux types
- ✅ Utilisateur normal est bloqué partout
- ✅ Messages d'erreur corrects selon le type

### Tests d'intégration
- ✅ Workflow complet Type A
- ✅ Workflow complet Type B
- ✅ Signature de contrats par différents types
- ✅ Modification impossible après signature

---

## Configuration

Les types d'utilisateurs sont définis dans:
- Fichier: `templates/home/singinpage.html.twig`
- Champ: `user_type` (select dropdown)
- Valeurs possibles:
  - `artiste`
  - `musicien`
  - `scénariste`
  - `publisher`
  - `sponsor`
  - `utilisateur`

Pour modifier les permissions, éditer:
```
src/Service/PermissionService.php
```
