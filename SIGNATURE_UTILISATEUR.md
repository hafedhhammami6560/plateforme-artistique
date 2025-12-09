# 📝 Système de Signature Électronique Utilisateur

## Vue d'ensemble

Le système permet aux utilisateurs d'enregistrer leur signature électronique une seule fois dans leur profil, puis de l'utiliser automatiquement pour tous leurs contrats.

## Fonctionnalités

### 1. Enregistrement de la Signature

**Page:** `http://127.0.0.1:8000/profile/edit`

#### Canvas de Signature
- Canvas HTML5 interactif pour dessiner la signature
- Support souris et tactile (mobile)
- Dimensions: 620x200 pixels
- Format de stockage: Base64 PNG

#### Actions disponibles
- **Dessiner:** Cliquer/toucher et glisser pour tracer la signature
- **Effacer:** Bouton pour nettoyer le canvas
- **Supprimer:** Bouton pour supprimer la signature enregistrée
- **Enregistrer:** Soumettre le formulaire pour sauvegarder

### 2. Utilisation dans les Contrats

#### Page Liste des Contrats (`/contrat`)
Pour chaque contrat en attente de signature:

**Si l'utilisateur a une signature enregistrée:**
```twig
[Bouton "Signer"] - Signature immédiate
```

**Si l'utilisateur n'a PAS de signature:**
```twig
[Bouton "Ajouter signature"] - Redirige vers profile/edit
```

#### Page Détail du Contrat (`/contrat/{id}`)

**Avec signature enregistrée:**
- Message: "Votre signature enregistrée sera utilisée"
- Bouton vert: "Signer Électroniquement"

**Sans signature:**
- Alerte warning: "Vous devez d'abord enregistrer votre signature électronique"
- Bouton orange: "Enregistrer ma signature" → redirige vers profil

### 3. Historique des Signatures

Les signatures enregistrées sont affichées dans l'historique du contrat avec:
- L'image de la signature
- La date de création de la signature
- Toutes les métadonnées de sécurité

## Architecture Technique

### Base de Données

#### Table `user`
```sql
ALTER TABLE user 
ADD signature_electronique LONGTEXT DEFAULT NULL,
ADD signature_created_at DATETIME DEFAULT NULL;
```

### Entité User

```php
#[ORM\Column(type: 'text', nullable: true)]
private ?string $signatureElectronique = null;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $signatureCreatedAt = null;

public function hasSignature(): bool
{
    return $this->signatureElectronique !== null;
}

public function setSignatureElectronique(?string $signatureElectronique): static
{
    $this->signatureElectronique = $signatureElectronique;
    
    if ($signatureElectronique !== null) {
        $this->signatureCreatedAt = new \DateTimeImmutable();
    }
    
    return $this;
}
```

### Service ElectronicSignatureService

#### Intégration de la Signature
```php
public function signContract(Contrat $contract, User $user, string $ipAddress, string $userAgent): Signature
{
    // ... code existant ...
    
    // Métadonnées enrichies
    $metadata = [
        'contract_version' => $contract->getUpdatedAt()?->format('Y-m-d H:i:s'),
        'contract_hash' => hash('sha256', $contract->getConditionsTexte()),
        'user_email' => $user->getEmail(),
        'signing_method' => 'electronic',
        'platform' => 'Plateforme Artistique',
    ];
    
    // Ajouter la signature si disponible
    if ($user->hasSignature()) {
        $metadata['user_signature'] = $user->getSignatureElectronique();
        $metadata['signature_created_at'] = $user->getSignatureCreatedAt()?->format('Y-m-d H:i:s');
    }
    
    $signature->setMetadata($metadata);
    
    // ... suite du code ...
}
```

### Controller ProfileController

```php
#[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, ...): Response
{
    if ($request->isMethod('POST')) {
        // Traiter la signature
        $signatureData = $request->request->get('signature_electronique');
        if ($signatureData !== null) {
            if (!empty($signatureData)) {
                // Valider le format base64
                if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signatureData)) {
                    $user->setSignatureElectronique($signatureData);
                    $this->addFlash('success', 'Votre signature électronique a été enregistrée.');
                } else {
                    $this->addFlash('error', 'Format de signature invalide.');
                }
            } elseif ($request->request->has('clear_signature')) {
                // Supprimer la signature
                $user->setSignatureElectronique(null);
                $user->setSignatureCreatedAt(null);
                $this->addFlash('success', 'Votre signature électronique a été supprimée.');
            }
        }
        
        // ... reste du code ...
    }
}
```

### Templates

#### edit.html.twig (Canvas JavaScript)
```javascript
const canvas = document.getElementById('signature-canvas');
const ctx = canvas.getContext('2d');
const signatureInput = document.getElementById('signature_electronique');

// Configuration du dessin
ctx.strokeStyle = '#000';
ctx.lineWidth = 2;
ctx.lineCap = 'round';

// Événements souris
canvas.addEventListener('mousedown', (e) => { /* ... */ });
canvas.addEventListener('mousemove', (e) => { /* ... */ });
canvas.addEventListener('mouseup', () => {
    signatureInput.value = canvas.toDataURL('image/png');
});

// Événements tactiles
canvas.addEventListener('touchstart', (e) => { /* ... */ });
canvas.addEventListener('touchmove', (e) => { /* ... */ });
```

#### index.html.twig (Liste des contrats)
```twig
{% if contrat.statut == 'en_attente_signature' %}
    {% if user.hasSignature() %}
        <form method="post" action="{{ path('app_signature_sign', {id: contrat.id}) }}">
            <input type="hidden" name="_token" value="{{ csrf_token('sign_contrat_' ~ contrat.id) }}">
            <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-pencil-square"></i> Signer
            </button>
        </form>
    {% else %}
        <a href="{{ path('app_profile_edit') }}" class="btn btn-sm btn-warning">
            <i class="bi bi-exclamation-triangle"></i> Ajouter signature
        </a>
    {% endif %}
{% endif %}
```

## Flux Utilisateur

### Premier Contrat (Sans Signature)

1. **Accès au contrat** → `/contrat/{id}`
2. **Alerte affichée:** "Vous devez d'abord enregistrer votre signature"
3. **Clic sur "Enregistrer ma signature"** → Redirection vers `/profile/edit`
4. **Dessiner la signature** sur le canvas
5. **Cliquer "Enregistrer"** → Signature sauvegardée
6. **Retour au contrat** → Bouton "Signer Électroniquement" disponible
7. **Clic sur "Signer"** → Signature appliquée avec confirmation

### Contrats Suivants (Avec Signature)

1. **Accès au contrat** → `/contrat/{id}`
2. **Message:** "Votre signature enregistrée sera utilisée"
3. **Clic sur "Signer Électroniquement"** → Signature immédiate
4. **Confirmation** → Contrat signé

## Sécurité

### Validation du Format
```php
if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signatureData)) {
    // Signature valide
}
```

### Protection CSRF
- Tous les formulaires incluent un token CSRF
- Validation côté serveur obligatoire

### Traçabilité
Les métadonnées incluent:
- Date de création de la signature
- Hash SHA-256 du contrat
- IP et User-Agent au moment de la signature
- Email de l'utilisateur
- Version du contrat

### Intégrité
- Hash SHA-512 des données de signature
- Vérification d'intégrité possible à tout moment
- Détection de toute modification

## Avantages

### Pour l'Utilisateur
- ✅ Enregistrement unique de la signature
- ✅ Réutilisation sur tous les contrats
- ✅ Gain de temps considérable
- ✅ Cohérence visuelle de la signature
- ✅ Modification/suppression possible

### Pour la Plateforme
- ✅ Traçabilité complète
- ✅ Conformité légale
- ✅ Audit trail détaillé
- ✅ Sécurité renforcée
- ✅ Expérience utilisateur améliorée

## Dépendances

### Composer
Toutes les dépendances nécessaires sont déjà installées:
- ✅ `symfony/mailer: 6.4.*` (notifications)
- ✅ `symfony/form: 6.4.*` (formulaires)
- ✅ `doctrine/orm: ^2.17` (persistance)
- ✅ `symfony/security-csrf: 6.4.*` (protection CSRF)

### Frontend
- HTML5 Canvas API (natif)
- Bootstrap 5 (déjà intégré)
- Bootstrap Icons (déjà intégré)

## Tests

### Test Manuel

1. **Enregistrer une signature:**
   ```
   1. Aller sur http://127.0.0.1:8000/profile/edit
   2. Dessiner une signature dans le canvas
   3. Cliquer "Enregistrer les modifications"
   4. Vérifier le message de succès
   ```

2. **Utiliser la signature:**
   ```
   1. Aller sur http://127.0.0.1:8000/contrat
   2. Trouver un contrat en attente
   3. Cliquer sur "Signer"
   4. Confirmer dans la popup
   5. Vérifier que le contrat est signé
   ```

3. **Vérifier l'historique:**
   ```
   1. Ouvrir un contrat signé
   2. Cliquer sur "Voir l'historique des signatures"
   3. Vérifier que la signature s'affiche correctement
   ```

### Vérification Base de Données

```sql
-- Vérifier les signatures enregistrées
SELECT id, name, email, 
       LENGTH(signature_electronique) as signature_length,
       signature_created_at
FROM user 
WHERE signature_electronique IS NOT NULL;

-- Vérifier les métadonnées des signatures
SELECT s.id, s.signed_at, 
       JSON_EXTRACT(s.metadata, '$.user_signature') as has_signature,
       JSON_EXTRACT(s.metadata, '$.signature_created_at') as signature_date
FROM signature s
WHERE JSON_EXTRACT(s.metadata, '$.user_signature') IS NOT NULL;
```

## Migration

### Version20251209065507.php
```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE user ADD signature_electronique LONGTEXT DEFAULT NULL, ADD signature_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE `user` DROP signature_electronique, DROP signature_created_at');
}
```

Exécution:
```bash
php bin/console doctrine:migrations:migrate
```

## Maintenance

### Suppression des Anciennes Signatures
Si nécessaire, nettoyer les signatures de plus de 2 ans:

```php
// Command à créer si nécessaire
$twoYearsAgo = new \DateTimeImmutable('-2 years');
$this->em->createQueryBuilder()
    ->update(User::class, 'u')
    ->set('u.signatureElectronique', 'NULL')
    ->set('u.signatureCreatedAt', 'NULL')
    ->where('u.signatureCreatedAt < :date')
    ->setParameter('date', $twoYearsAgo)
    ->getQuery()
    ->execute();
```

## Support

Pour toute question ou problème:
1. Vérifier que la migration est exécutée
2. Vérifier les permissions sur le canvas
3. Vérifier la console navigateur pour erreurs JS
4. Vérifier les logs Symfony: `var/log/dev.log`

---

**Statut:** ✅ Implémenté et Opérationnel  
**Date:** 9 décembre 2025  
**Version:** 1.0
