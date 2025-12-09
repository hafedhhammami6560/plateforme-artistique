# 🔐 Système de Signature Électronique Sécurisée

## 📋 Vue d'ensemble

Le système de signature électronique sécurisée permet aux parties prenantes d'un contrat de le signer de manière légale et sécurisée. Chaque signature est horodatée, traçable et cryptographiquement protégée.

---

## 🏗️ Architecture

### Composants principaux

1. **Entité `Signature`** (`src/Entity/Signature.php`)
   - Stocke toutes les informations de signature
   - Horodatage, IP, hash cryptographique
   - Statuts: `valid`, `revoked`, `expired`

2. **Service `ElectronicSignatureService`** (`src/Service/ElectronicSignatureService.php`)
   - Génération de tokens sécurisés
   - Signature de contrats
   - Vérification d'intégrité
   - Génération de certificats
   - Révocation de signatures

3. **Controller `SignatureController`** (`src/Controller/SignatureController.php`)
   - Routes de signature
   - Historique des signatures
   - Demandes de signature par email

4. **Value Object `Certificate`** (`src/ValueObject/Certificate.php`)
   - Représente un certificat électronique
   - Validation et export JSON

---

## 🔑 Fonctionnalités

### 1. Génération de token de signature

```php
$token = $signatureService->generateSignatureToken($user, $contract);
```

- **Génère** : Token unique de 128 caractères (SHA-256)
- **Inclut** : User ID, Contract ID, Timestamp
- **Sécurité** : Hash cryptographique avec random_bytes()

### 2. Signature d'un contrat

```php
$signature = $signatureService->signContract(
    $contract,
    $user,
    $ipAddress,
    $userAgent
);
```

**Processus de signature** :
1. Vérification que l'utilisateur n'a pas déjà signé
2. Vérification du statut du contrat (doit être `final` ou `en_attente_signature`)
3. Génération du token unique
4. Calcul du hash de signature (SHA-512)
5. Enregistrement des métadonnées (IP, user agent, timestamp)
6. Mise à jour du statut du contrat
7. Si les 2 parties ont signé → `statut = 'signe'`

**Données de signature incluses** :
- ID du contrat et numéro
- Type et prix du contrat
- Conditions textuelles
- Dates de début/fin
- IDs des parties (artiste + client)
- ID et email du signataire

### 3. Vérification d'intégrité

```php
$isValid = $signatureService->verifySignatureIntegrity($signature);
```

- **Reconstruit** les données originales de signature
- **Recalcule** le hash et compare avec celui stocké
- **Détecte** toute modification du contrat post-signature
- **Retourne** `true` si intégré, `false` sinon

### 4. Génération de certificat

```php
$certificate = $signatureService->generateSignatureCertificate($contract);
```

**Certificat contient** :
- Numéro unique (format: `CERT-XXXX-YYYY`)
- Émetteur : `Plateforme Artistique CA`
- Sujet : Numéro et type du contrat
- Dates d'émission et d'expiration (10 ans)
- Clé publique (simulation)
- Signature cryptographique
- Métadonnées complètes

**Conditions** :
- Le contrat doit être entièrement signé (`statut = 'signe'`)
- Toutes les signatures doivent être valides

### 5. Révocation de signature

```php
$signatureService->revokeSignature($signature, $reason);
```

**Actions** :
- Change le statut à `revoked`
- Enregistre la raison de révocation
- Horodate la révocation
- Met à jour le statut du contrat si nécessaire

**Raisons courantes** :
- Fraude détectée
- Erreur de saisie
- Annulation mutuelle
- Violation des termes

### 6. Historique des signatures

```php
$history = $signatureService->getSignatureHistory($contract);
```

**Retourne un tableau** avec :
- Informations du signataire
- Dates de signature et d'expiration
- Adresse IP et statut
- Raison de révocation (si applicable)
- Métadonnées complètes

### 7. Envoi de demande de signature

```php
$success = $signatureService->sendSignatureRequest($contract, $user);
```

**Email contient** :
- Informations du contrat (numéro, montant, dates)
- Lien direct pour signer
- Rappel de sécurité
- Design professionnel avec HTML

### 8. Vérification d'expiration

```php
$hasExpired = $signatureService->checkSignatureExpiration($contract);
```

- Vérifie toutes les signatures du contrat
- Marque comme `expired` les signatures dépassées
- Commande Symfony disponible : `app:signatures:check-expiration`

---

## 🔒 Sécurité

### Mesures de sécurité

1. **Cryptographie** :
   - SHA-256 pour les tokens
   - SHA-512 pour les hashs de signature
   - `random_bytes()` pour la génération aléatoire

2. **Traçabilité** :
   - Adresse IP enregistrée
   - User Agent capturé
   - Horodatage précis (DateTimeImmutable)

3. **Intégrité** :
   - Hash incluant toutes les données du contrat
   - Vérification possible à tout moment
   - Détection automatique de modifications

4. **Non-répudiation** :
   - Token unique par signature
   - Métadonnées complètes
   - Certificat électronique

5. **Protection CSRF** :
   - Tokens CSRF sur les formulaires
   - Validation côté serveur

6. **Expiration** :
   - Signatures valables 10 ans
   - Vérification automatique par commande cron

---

## 📊 Base de données

### Table `signature`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique |
| `contrat_id` | INT | FK vers `contrat` |
| `signataire_id` | INT | FK vers `user` |
| `signature_token` | VARCHAR(255) | Token unique (UNIQUE) |
| `signature_hash` | TEXT | Hash cryptographique |
| `ip_address` | VARCHAR(45) | IPv4 ou IPv6 |
| `user_agent` | VARCHAR(255) | Navigateur/Système |
| `certificate_data` | TEXT | Données du certificat (JSON) |
| `status` | VARCHAR(50) | `valid`, `revoked`, `expired` |
| `revocation_reason` | TEXT | Raison si révoquée |
| `signed_at` | DATETIME | Date/heure de signature |
| `expires_at` | DATETIME | Date d'expiration |
| `revoked_at` | DATETIME | Date de révocation |
| `metadata` | JSON | Métadonnées supplémentaires |
| `created_at` | DATETIME | Date de création |

---

## 🚀 Utilisation

### Côté Controller

```php
use App\Service\ElectronicSignatureService;

public function signAction(
    Contrat $contrat,
    User $user,
    Request $request,
    ElectronicSignatureService $signatureService
): Response {
    $ipAddress = $request->getClientIp();
    $userAgent = $request->headers->get('User-Agent');
    
    try {
        $signature = $signatureService->signContract(
            $contrat,
            $user,
            $ipAddress,
            $userAgent
        );
        
        // Générer certificat si contrat complet
        if ($contrat->getStatut() === Contrat::STATUT_SIGNE) {
            $certificate = $signatureService->generateSignatureCertificate($contrat);
        }
        
        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        
    } catch (\RuntimeException $e) {
        $this->addFlash('error', $e->getMessage());
    }
}
```

### Côté Template

```twig
<form method="post" action="{{ path('app_signature_sign', {id: contrat.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('sign_contract_' ~ contrat.id) }}">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-pen-fill"></i> Signer Électroniquement
    </button>
</form>
```

### Vérification d'intégrité

```php
use App\Repository\SignatureRepository;
use App\Service\ElectronicSignatureService;

public function verifyAction(
    Contrat $contrat,
    SignatureRepository $signatureRepo,
    ElectronicSignatureService $signatureService
): Response {
    $signatures = $signatureRepo->findByContrat($contrat);
    
    foreach ($signatures as $signature) {
        $isValid = $signatureService->verifySignatureIntegrity($signature);
        
        if (!$isValid) {
            // Alerte : signature compromise !
        }
    }
}
```

---

## ⚙️ Commandes Symfony

### Vérifier les signatures expirées

```bash
php bin/console app:signatures:check-expiration
```

**Actions** :
- Parcourt toutes les signatures valides
- Vérifie la date d'expiration
- Marque comme `expired` si dépassée
- Log les changements

**Usage recommandé** : Cron journalier
```cron
0 2 * * * /usr/bin/php /path/to/project/bin/console app:signatures:check-expiration
```

---

## 📧 Notifications Email

### Template d'email de signature

L'email envoyé contient :
- **Header** : Logo et titre "Demande de Signature Électronique"
- **Corps** :
  - Salutation personnalisée
  - Informations du contrat
  - Bouton CTA "Signer le Contrat"
  - Avertissement de sécurité
- **Footer** : Copyright et mentions légales

### Personnalisation

Modifier dans `ElectronicSignatureService::generateSignatureRequestEmail()` :
- Couleurs (`#05468b`, `#28a745`)
- Textes et messages
- URL du bouton

---

## 🎯 Routes disponibles

| Route | Méthode | Description |
|-------|---------|-------------|
| `/signature/contrat/{id}/signer` | POST | Signer un contrat |
| `/signature/contrat/{id}/historique` | GET | Historique des signatures |
| `/signature/demande-signature/{id}` | POST | Envoyer demande par email |

---

## 🔍 Exemple de flux complet

```
1. Contrat créé (statut: brouillon)
   ↓
2. Marquer comme final (statut: final)
   ↓
3. Artiste signe électroniquement
   - Token généré
   - Hash calculé
   - IP/UserAgent enregistrés
   - Statut: en_attente_signature
   ↓
4. Email envoyé au client
   ↓
5. Client signe électroniquement
   - Token généré
   - Hash calculé
   - IP/UserAgent enregistrés
   - Statut: signe
   ↓
6. Certificat généré automatiquement
   - Numéro unique
   - Données cryptées
   - Valide 10 ans
   ↓
7. Contrat actif et exécutoire
```

---

## 📈 Métriques et Logs

### Logs générés

- **Info** : Signatures créées, certificats générés
- **Warning** : Signatures révoquées, expirations
- **Error** : Échecs de vérification d'intégrité

### Vérification des logs

```bash
tail -f var/log/dev.log | grep signature
```

---

## 🛡️ Conformité Légale

### eIDAS (Règlement européen)

Le système implémente les principes du règlement eIDAS :
- ✅ Identification du signataire
- ✅ Horodatage fiable
- ✅ Intégrité du document
- ✅ Non-répudiation
- ✅ Traçabilité complète

### Valeur juridique

Les signatures électroniques ont la même valeur qu'une signature manuscrite si :
1. L'identité du signataire est vérifiée ✅
2. Le document ne peut être modifié après signature ✅
3. Le lien entre signature et signataire est garanti ✅

---

## 🔧 Personnalisation

### Modifier la durée de validité

```php
// Dans ElectronicSignatureService.php
private const SIGNATURE_VALIDITY_YEARS = 10; // Changer ici
```

### Changer l'émetteur du certificat

```php
private const CERTIFICATE_ISSUER = 'Votre CA'; // Changer ici
```

### Personnaliser les métadonnées

```php
$signature->setMetadata([
    'custom_field' => 'valeur personnalisée',
    'environment' => 'production',
    // ...
]);
```

---

## 🎓 Bonnes pratiques

1. **Vérifier régulièrement l'intégrité** des signatures
2. **Logger tous les événements** liés aux signatures
3. **Sauvegarder les certificats** en lieu sûr
4. **Informer les utilisateurs** de la valeur juridique
5. **Mettre en place une cron** pour les expirations
6. **Tester le processus** en environnement de staging
7. **Documenter les révocations** avec des raisons claires

---

## 📞 Support

Pour toute question technique :
- Consulter les logs : `var/log/`
- Vérifier les migrations : `php bin/console doctrine:migrations:status`
- Tester la commande : `php bin/console app:signatures:check-expiration -v`

---

**© 2025 Plateforme Artistique - Système de Signature Électronique Sécurisée**
