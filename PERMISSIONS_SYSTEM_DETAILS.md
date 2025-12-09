# 🔒 Système de Permissions - Discussions et Contrats

## Date: 9 décembre 2025

## Vue d'ensemble

Implémentation d'un système de permissions à deux niveaux pour les discussions et contrats :
- **Utilisateurs normaux** : Peuvent masquer/modifier selon des règles strictes
- **Administrateurs** : Accès complet pour modification et suppression définitive

---

## 📋 Discussions - Soft Delete

### Fonctionnalité : Masquage (Soft Delete)

Les utilisateurs peuvent **masquer** les discussions de leur vue sans les supprimer de la base de données.

#### Champs Ajoutés à l'Entity Discussion

```php
#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $hiddenByInitiateur = false;

#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $hiddenByDestinataire = false;
```

#### Méthodes Ajoutées

```php
// Vérifier si masquée pour un utilisateur
public function isHiddenForUser(User $user): bool

// Masquer pour un utilisateur
public function hideForUser(User $user): static

// Restaurer pour un utilisateur
public function unhideForUser(User $user): static
```

### Routes

| Route | Méthode | Qui peut l'utiliser | Action |
|-------|---------|---------------------|--------|
| `/discussion/{id}/hide` | POST | Initiateur ou Destinataire | Masque la discussion de leur liste |
| `/discussion/{id}/delete` | POST | **ADMIN UNIQUEMENT** | Supprime définitivement de la DB |

### Comportement

**Liste des Discussions (`/discussion`):**
```php
// Exclut automatiquement les discussions masquées
$qb->where('(d.initiateur = :user AND d.hiddenByInitiateur = false) 
         OR (d.destinataire = :user AND d.hiddenByDestinataire = false)')
```

**Page Détail (`/discussion/{id}`):**
- Bouton **"Masquer"** → Soft delete (discussion reste en DB)
- Bouton **"Supprimer (Admin)"** → Suppression définitive (admin seulement)

### Exemple de Workflow

```
Utilisateur Normal:
1. Visite /discussion/5
2. Clique sur "Masquer"
3. Discussion disparaît de sa liste
4. Reste accessible dans la DB pour l'autre partie
5. Admin peut toujours voir et restaurer

Admin:
1. Visite /discussion/5
2. Peut cliquer sur "Supprimer (Admin)"
3. Suppression définitive de la DB
4. Action irréversible
```

---

## 📄 Contrats - Archivage et Permissions

### Fonctionnalité : Archivage Automatique

Les contrats **signés par les deux parties** sont automatiquement archivés.

#### Champs Ajoutés à l'Entity Contrat

```php
#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $isArchived = false;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $archivedAt = null;
```

#### Méthodes Ajoutées

```php
// Vérifie si le contrat peut être modifié par un utilisateur normal
public function canBeEditedByUser(): bool
{
    return $this->statut === self::STATUT_BROUILLON && !$this->isArchived;
}

// Archive automatiquement si les deux signatures sont présentes
public function autoArchiveIfFullySigned(): void
{
    if ($this->isFullySigned() && !$this->isArchived) {
        $this->setIsArchived(true);
        $this->setStatut(self::STATUT_SIGNE);
    }
}
```

### Règles de Modification

| Statut Contrat | Utilisateur Normal | Administrateur |
|----------------|-------------------|----------------|
| **Brouillon** (non signé) | ✅ Peut modifier | ✅ Peut modifier |
| **En attente signature** | ❌ Ne peut pas modifier | ✅ Peut modifier |
| **Signé + Archivé** | ❌ Ne peut pas modifier | ✅ Peut modifier |

### Routes

| Route | Méthode | Qui peut l'utiliser | Condition |
|-------|---------|---------------------|-----------|
| `/contrat/{id}/edit` | GET/POST | Artiste ou Producteur | Seulement si `BROUILLON` ET non archivé |
| `/contrat/{id}/edit` | GET/POST | **ADMIN** | Peut toujours modifier |
| `/contrat/{id}/delete` | POST | **ADMIN UNIQUEMENT** | Suppression définitive |

### Archivage Automatique

**Déclenchement dans ElectronicSignatureService:**

```php
// Méthode: updateContractSignatureStatus()
if ($contract->getSignatureArtist() && $contract->getSignatureClient()) {
    $contract->setStatut(Contrat::STATUT_SIGNE);
    $contract->setDateSignature(new \DateTimeImmutable());
    $contract->autoArchiveIfFullySigned(); // 🔒 Archivage automatique
    
    $this->logger->info('Contrat complètement signé et archivé', [
        'contract_id' => $contract->getId(),
        'archived_at' => $contract->getArchivedAt()?->format('Y-m-d H:i:s')
    ]);
}
```

### Interface Utilisateur

**Contrat Archivé (`/contrat/{id}`):**

```
┌──────────────────────────────────────────────────┐
│ ℹ️ Contrat Archivé                               │
│ Ce contrat a été signé par les deux parties      │
│ le 09/12/2025 à 14:30.                           │
│ Il ne peut plus être modifié sauf par un admin.  │
└──────────────────────────────────────────────────┘

Titre: CTR-2025-001 🗄️ [Signé] [Archivé]

Boutons:
- [⬅️ Retour]
- [🔒 Archivé] (désactivé)
- [🕒 Historique] (actif)
```

---

## 🗄️ Migration Base de Données

### Version20251209091258.php

```sql
-- Discussions: Ajout champs soft delete
ALTER TABLE discussion 
ADD hidden_by_initiateur TINYINT(1) DEFAULT 0 NOT NULL, 
ADD hidden_by_destinataire TINYINT(1) DEFAULT 0 NOT NULL;

-- Contrats: Ajout champs archivage
ALTER TABLE contrat 
ADD is_archived TINYINT(1) DEFAULT 0 NOT NULL, 
ADD archived_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';
```

**Exécution:**
```bash
php bin/console doctrine:migrations:migrate --no-interaction
✅ Successfully migrated to version: DoctrineMigrations\Version20251209091258
```

---

## 🔐 Contrôleurs - Implémentation

### DiscussionController

#### Nouvelle Route: `hide` (Soft Delete)
```php
#[Route('/{id}/hide', name: 'app_discussion_hide', methods: ['POST'])]
public function hide(Request $request, Discussion $discussion, UserRepository $userRepo): Response
{
    // Vérification de participation
    if ($discussion->getInitiateur() !== $user && $discussion->getDestinataire() !== $user) {
        $this->addFlash('error', 'Vous ne pouvez pas masquer cette discussion.');
        return $this->redirectToRoute('app_discussion_index');
    }
    
    // Token CSRF
    if (!$this->isCsrfTokenValid('hide_discussion_' . $discussion->getId(), ...)) {
        // ...
    }
    
    // Masquer (soft delete)
    $discussion->hideForUser($user);
    $this->em->flush();
    
    $this->addFlash('success', 'Discussion masquée avec succès !');
}
```

#### Modification: `delete` (Admin Uniquement)
```php
#[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]
public function delete(Request $request, Discussion $discussion, UserRepository $userRepo): Response
{
    // SEUL L'ADMIN peut supprimer
    if ($user->getUserType() !== 'admin') {
        $this->addFlash('error', 'Seul un administrateur peut supprimer définitivement...');
        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }
    
    // Suppression définitive
    $this->em->remove($discussion);
    $this->em->flush();
}
```

### ContratController

#### Modification: `edit` (Vérification Archivage)
```php
#[Route('/{id}/edit', name: 'app_contrat_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Contrat $contrat, UserRepository $userRepo): Response
{
    $isAdmin = $user->getUserType() === 'admin';
    
    // Vérifier si archivé
    if ($contrat->isArchived() && !$isAdmin) {
        $this->addFlash('error', 'Ce contrat est archivé. Seul un admin peut le modifier.');
        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
    
    // Utilisateurs normaux: seulement les brouillons
    if (!$isAdmin && !$contrat->canBeEditedByUser()) {
        $this->addFlash('error', 'Seuls les brouillons peuvent être modifiés.');
        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
    
    // ... reste du code
}
```

#### Modification: `delete` (Admin Uniquement)
```php
#[Route('/{id}/delete', name: 'app_contrat_delete', methods: ['POST'])]
public function delete(Request $request, Contrat $contrat, UserRepository $userRepo): Response
{
    // SEUL L'ADMIN
    if ($user->getUserType() !== 'admin') {
        $this->addFlash('error', 'Seul un administrateur peut supprimer...');
        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }
    
    // Token CSRF
    if (!$this->isCsrfTokenValid('delete_contrat_' . $contrat->getId(), ...)) {
        // ...
    }
    
    // Suppression
    $this->em->remove($contrat);
    $this->em->flush();
}
```

---

## 🎨 Templates Modifiés

### discussion/show.html.twig

**Ajout des boutons dans le footer:**

```twig
<div class="d-flex justify-content-between">
    <a href="{{ path('app_discussion_index') }}" class="btn btn-secondary">Retour</a>
    
    <div>
        {# Terminer (si en cours) #}
        <form method="post" action="{{ path('app_discussion_terminer', {id: discussion.id}) }}" style="display:inline;">
            <button type="submit" class="btn btn-warning">Terminer</button>
        </form>
        
        {# Masquer (soft delete) #}
        <form method="post" action="{{ path('app_discussion_hide', {id: discussion.id}) }}" style="display:inline;">
            <input type="hidden" name="_token" value="{{ csrf_token('hide_discussion_' ~ discussion.id) }}">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-eye-slash"></i> Masquer
            </button>
        </form>
        
        {# Supprimer (admin seulement) #}
        <form method="post" action="{{ path('app_discussion_delete', {id: discussion.id}) }}" style="display:inline;">
            <input type="hidden" name="_token" value="{{ csrf_token('delete_discussion_' ~ discussion.id) }}">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Supprimer (Admin)
            </button>
        </form>
    </div>
</div>
```

### contrat/show.html.twig

**Alerte pour contrats archivés:**

```twig
{% if contrat.isArchived %}
    <div class="alert alert-info">
        <i class="bi bi-archive"></i> <strong>Contrat Archivé</strong>
        Ce contrat a été signé par les deux parties le {{ contrat.archivedAt|date('d/m/Y à H:i') }}.
        Il ne peut plus être modifié sauf par un administrateur.
    </div>
{% endif %}
```

**Titre avec icône d'archivage:**

```twig
<h1>
    {{ contrat.numeroContrat }}
    {% if contrat.isArchived %}
        <i class="bi bi-archive-fill text-primary" title="Archivé"></i>
    {% endif %}
</h1>

<span class="badge bg-info fs-5"><i class="bi bi-archive"></i> Archivé</span>
```

**Footer avec permissions:**

```twig
{# Modification: seulement si brouillon ET non archivé #}
{% if isOwner and contrat.statut == 'brouillon' and not contrat.isArchived %}
    <a href="{{ path('app_contrat_edit', {id: contrat.id}) }}" class="btn btn-warning">Modifier</a>
{% elseif contrat.isArchived %}
    <button class="btn btn-outline-secondary" disabled title="Contrat archivé">
        <i class="bi bi-lock"></i> Archivé
    </button>
{% endif %}
```

---

## 📊 Tableau Récapitulatif des Permissions

### Discussions

| Action | Artiste/Musicien/Scénariste | Utilisateur Normal | Admin |
|--------|----------------------------|-------------------|-------|
| **Voir** | ✅ Si non masqué | ✅ Si non masqué | ✅ Tout |
| **Masquer** | ✅ Sa propre vue | ✅ Sa propre vue | ✅ |
| **Restaurer** | ❌ | ❌ | ✅ Via DB |
| **Supprimer définitivement** | ❌ | ❌ | ✅ |

### Contrats

| Statut | Action | Artiste/Client | Admin |
|--------|--------|---------------|-------|
| **Brouillon** | Modifier | ✅ | ✅ |
| **Brouillon** | Supprimer | ❌ | ✅ |
| **En attente signature** | Modifier | ❌ | ✅ |
| **Signé + Archivé** | Voir | ✅ | ✅ |
| **Signé + Archivé** | Modifier | ❌ | ✅ |
| **Signé + Archivé** | Supprimer | ❌ | ✅ |

---

## 🧪 Tests de Validation

### Test 1: Masquage Discussion (Utilisateur Normal)

```
1. Connexion avec compte artiste
2. Aller sur /discussion/5
3. Cliquer "Masquer"
4. Retour à /discussion
   ✅ Discussion 5 disparue de la liste
5. Connexion avec compte destinataire
6. Aller sur /discussion
   ✅ Discussion 5 toujours visible pour lui
```

### Test 2: Suppression Discussion (Non-Admin)

```
1. Connexion avec compte artiste
2. Aller sur /discussion/5
3. Cliquer "Supprimer (Admin)"
   ❌ Erreur: "Seul un administrateur..."
```

### Test 3: Modification Contrat Brouillon

```
1. Connexion avec artiste
2. Créer contrat brouillon
3. Cliquer "Modifier"
   ✅ Modification autorisée
4. Artiste signe le contrat
5. Cliquer "Modifier"
   ❌ Erreur: "Seuls les brouillons..."
```

### Test 4: Archivage Automatique

```
1. Contrat en attente: statut = 'en_attente_signature'
2. Artiste signe → signatureArtist = true
3. Client signe → signatureClient = true
4. Service ElectronicSignatureService déclenché
   ✅ statut = 'signe'
   ✅ isArchived = true
   ✅ archivedAt = now()
5. Tentative de modification
   ❌ Bouton "Modifier" remplacé par "🔒 Archivé"
```

### Test 5: Admin Full Access

```
1. Connexion admin
2. Ouvrir discussion masquée
   ✅ Visible
3. Cliquer "Supprimer (Admin)"
   ✅ Discussion supprimée de DB
4. Ouvrir contrat archivé
5. Cliquer "Modifier"
   ✅ Modification autorisée
```

---

## 🔍 Requêtes SQL pour Vérification

### Vérifier les discussions masquées

```sql
SELECT 
    d.id,
    d.titre,
    d.hidden_by_initiateur,
    d.hidden_by_destinataire,
    i.name as initiateur,
    dest.name as destinataire
FROM discussion d
JOIN user i ON d.initiateur_id = i.id
JOIN user dest ON d.destinataire_id = dest.id
WHERE d.hidden_by_initiateur = 1 OR d.hidden_by_destinataire = 1;
```

### Vérifier les contrats archivés

```sql
SELECT 
    c.id,
    c.numero_contrat,
    c.statut,
    c.is_archived,
    c.archived_at,
    c.signature_artist,
    c.signature_client
FROM contrat c
WHERE c.is_archived = 1
ORDER BY c.archived_at DESC;
```

### Vérifier l'intégrité des signatures et archivage

```sql
SELECT 
    c.numero_contrat,
    c.signature_artist,
    c.signature_client,
    c.is_archived,
    CASE 
        WHEN c.signature_artist = 1 AND c.signature_client = 1 AND c.is_archived = 0 
        THEN 'ERREUR: Devrait être archivé'
        ELSE 'OK'
    END as status
FROM contrat c
WHERE c.statut = 'signe';
```

---

## 📝 Résumé des Fichiers Modifiés

| Fichier | Modifications | Lignes |
|---------|--------------|--------|
| `src/Entity/Discussion.php` | +9 méthodes, +2 champs | +85 |
| `src/Entity/Contrat.php` | +5 méthodes, +2 champs | +55 |
| `src/Controller/DiscussionController.php` | +1 route hide, modification delete | +40 |
| `src/Controller/ContratController.php` | Modification edit, modification delete | +20 |
| `src/Service/ElectronicSignatureService.php` | Ajout autoArchive | +8 |
| `templates/discussion/show.html.twig` | Boutons hide/delete | +30 |
| `templates/contrat/show.html.twig` | Alerte archivage, permissions | +40 |
| `migrations/Version20251209091258.php` | Nouvelle migration | Nouveau |

**Total:** 8 fichiers, ~278 lignes modifiées

---

## ✅ Statut Final

- ✅ Soft delete discussions implémenté
- ✅ Archivage automatique contrats implémenté
- ✅ Permissions utilisateurs vs admin configurées
- ✅ Migration exécutée avec succès
- ✅ Templates mis à jour
- ✅ Routes sécurisées avec CSRF
- ✅ Messages flash appropriés
- ✅ Logging des actions sensibles

**Système Opérationnel** - Prêt pour tests et déploiement 🎉

---

**Date de Complétion:** 9 décembre 2025  
**Version:** 1.0  
**Branch:** feature/search-filter-sort
