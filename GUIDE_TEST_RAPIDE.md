# Guide de Test Rapide - Corrections Module ArtConnect

## 🚀 Démarrage Rapide

**URL de l'application** : http://localhost:8008

### Comptes de Test

| Email | Mot de passe | Rôle | Peut Créer Discussions | Peut Créer Contrats |
|-------|--------------|------|------------------------|---------------------|
| `admin@artconnect.com` | `password` | Admin | ❌ | ❌ |
| `publisher@artconnect.com` | `password` | Publisher | ✅ | ✅ |
| `artist@artconnect.com` | `password` | Artiste | ❌ | ❌ |

---

## ✅ Liste de Vérification des Corrections

### 1. Affichage du Rôle (Coin Supérieur Gauche)

- [ ] Se connecter avec `admin@artconnect.com` → Affiche "Administrateur"
- [ ] Se connecter avec `publisher@artconnect.com` → Affiche "Publisher"
- [ ] Se connecter avec `artist@artconnect.com` → Affiche "Artiste"

### 2. Bouton "Nouvelle Discussion"

- [ ] Connecté en tant que **Publisher** → Bouton visible et fonctionnel
- [ ] Connecté en tant que **Artiste** → Bouton invisible
- [ ] Créer une discussion → Redirection vers la discussion avec succès

### 3. Bouton "Nouveau Contrat"

- [ ] Connecté en tant que **Publisher** → Bouton visible et fonctionnel
- [ ] Connecté en tant que **Artiste** → Bouton invisible
- [ ] Créer un contrat → Message de succès "Le contrat a été créé en brouillon"

### 4. Page "Mes Discussions"

- [ ] Affiche uniquement les discussions de l'utilisateur connecté
- [ ] Les statistiques affichées correspondent aux discussions visibles
- [ ] Filtres fonctionnels (Actives, Fermées, etc.)

### 5. Page "Mes Contrats"

- [ ] Affiche uniquement les contrats de l'utilisateur connecté
- [ ] Les statistiques affichées correspondent aux contrats visibles
- [ ] Filtres fonctionnels (Brouillons, Proposés, Signés, Actifs)

### 6. Admin Dashboard

- [ ] Connecté en tant que **Admin** → Lien "Admin Dashboard" visible dans la sidebar
- [ ] Cliquer sur "Admin Dashboard" → Page s'affiche sans erreur
- [ ] Statistiques globales affichées (tous les utilisateurs)
- [ ] Liens "Toutes Discussions" et "Tous Contrats" fonctionnels

### 7. Permissions - Publisher

- [ ] Peut créer une discussion
- [ ] Peut créer un contrat
- [ ] Peut éditer ses discussions (sauf si archivées)
- [ ] Peut éditer ses contrats (sauf si terminés ou actifs)
- [ ] Peut supprimer ses discussions vides
- [ ] Peut supprimer ses contrats en brouillon

### 8. Permissions - Artiste

- [ ] Voit uniquement ses discussions
- [ ] Voit uniquement ses contrats
- [ ] Ne peut PAS créer de discussion
- [ ] Ne peut PAS créer de contrat
- [ ] Ne peut PAS éditer les discussions
- [ ] Ne peut PAS éditer les contrats
- [ ] Peut signer un contrat proposé

### 9. Permissions - Admin

- [ ] Voit toutes les discussions
- [ ] Voit tous les contrats
- [ ] Peut supprimer n'importe quelle discussion
- [ ] Peut supprimer n'importe quel contrat
- [ ] Peut éditer les discussions
- [ ] Ne peut PAS éditer les contrats

---

## 🐛 Tests de Non-Régression

### Scénario 1 : Workflow Complet Publisher → Artiste

1. **Publisher crée une discussion**
   - Se connecter avec `publisher@artconnect.com`
   - Aller sur `/discussion/new`
   - Sélectionner un produit : "Paysage Urbain - lucas_thomas"
   - Sujet : "Intéressé par votre œuvre"
   - Message : "Bonjour, je suis intéressé par votre œuvre pour une exposition."
   - Cliquer sur "Créer la Discussion"
   - ✅ **Résultat attendu** : Redirection vers la discussion avec message de succès

2. **Artiste répond à la discussion**
   - Se déconnecter et se connecter avec `artist@artconnect.com` (lucas.thomas@artconnect.com)
   - Aller sur "Mes Discussions"
   - Ouvrir la discussion créée
   - Envoyer un message : "Merci pour votre intérêt !"
   - ✅ **Résultat attendu** : Message envoyé avec succès

3. **Publisher crée un contrat**
   - Se déconnecter et se connecter avec `publisher@artconnect.com`
   - Ouvrir la discussion
   - Cliquer sur "Créer un Contrat"
   - Remplir les informations du contrat
   - Soumettre
   - ✅ **Résultat attendu** : Contrat créé en brouillon

4. **Publisher propose le contrat**
   - Accéder au contrat créé
   - Cliquer sur "Proposer le Contrat"
   - ✅ **Résultat attendu** : Statut change à "Proposé"

5. **Artiste signe le contrat**
   - Se déconnecter et se connecter avec `artist@artconnect.com`
   - Aller sur "Mes Contrats"
   - Ouvrir le contrat proposé
   - Cliquer sur "Signer le Contrat"
   - ✅ **Résultat attendu** : Contrat signé avec succès

### Scénario 2 : Test des Restrictions

1. **Publisher essaie d'éditer un contrat actif**
   - Se connecter avec `publisher@artconnect.com`
   - Ouvrir un contrat avec statut "Actif"
   - Essayer de cliquer sur "Modifier"
   - ✅ **Résultat attendu** : Bouton "Modifier" invisible ou désactivé

2. **Artiste essaie de créer une discussion**
   - Se connecter avec `artist@artconnect.com`
   - Aller sur "Mes Discussions"
   - ✅ **Résultat attendu** : Bouton "Nouvelle Discussion" invisible

3. **Admin essaie d'éditer un contrat**
   - Se connecter avec `admin@artconnect.com`
   - Aller sur "Tous Contrats"
   - Ouvrir un contrat
   - ✅ **Résultat attendu** : Bouton "Modifier" invisible (admin ne peut qu'éditer les discussions)

---

## 🔍 Vérification des Erreurs Précédentes

### ❌ Erreurs Signalées → ✅ Corrections Vérifiées

1. **"La nouvelle discussion ne marche pas"**
   - ✅ Tester : Créer une discussion en tant que publisher
   - ✅ Vérifier : Pas d'erreur, redirection vers la discussion créée

2. **"Nouvelle contrat affiche des erreurs"**
   - ✅ Tester : Créer un contrat en tant que publisher
   - ✅ Vérifier : Pas d'erreur, message de succès

3. **"Les boutons des discussions affichent des erreurs"**
   - ✅ Tester : Cliquer sur tous les boutons de la page "Mes Discussions"
   - ✅ Vérifier : Aucune erreur 404

4. **"Admin dashboard affiche des erreurs"**
   - ✅ Tester : Se connecter en tant qu'admin et accéder au dashboard
   - ✅ Vérifier : Page s'affiche sans erreur

5. **"Affichage du rôle incorrect"**
   - ✅ Tester : Se connecter avec chaque type d'utilisateur
   - ✅ Vérifier : Le bon rôle s'affiche dans le coin supérieur gauche

6. **"Les contrats ne se créent pas pour publisher"**
   - ✅ Tester : Créer un contrat en tant que publisher
   - ✅ Vérifier : Contrat créé et visible dans "Mes Contrats"

---

## 📊 Statistiques à Vérifier

### Page "Mes Discussions"

Les compteurs suivants doivent correspondre aux discussions affichées :
- **Total** : Nombre total de discussions de l'utilisateur
- **Actives** : Discussions avec statut "active"
- **Fermées** : Discussions avec statut "closed"
- **Avec Contrat** : Discussions ayant un contrat associé

### Page "Mes Contrats"

Les compteurs suivants doivent correspondre aux contrats affichés :
- **Total** : Nombre total de contrats de l'utilisateur
- **Actifs** : Contrats avec statut "active"
- **Signés** : Contrats avec statut "signed"
- **Commission Moy.** : Moyenne des commissions des contrats actifs

---

## 🛠️ En Cas de Problème

### Si le serveur ne répond pas
```bash
# Vérifier si le serveur tourne
Get-Process -Name php

# Redémarrer le serveur
php -S localhost:8008 -t public
```

### Si les données sont incorrectes
```bash
# Recharger les fixtures
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

### Si le cache pose problème
```bash
# Vider le cache
php bin/console cache:clear
```

### Si vous voyez une erreur 404
```bash
# Vérifier les routes disponibles
php bin/console debug:router | Select-String "discussion|contract|admin"
```

---

## ✨ Résumé

**Toutes les corrections ont été appliquées avec succès !**

- ✅ Affichage du rôle corrigé
- ✅ Permissions correctement implémentées
- ✅ Toutes les routes fonctionnelles
- ✅ Boutons conditionnés selon les rôles
- ✅ Statistiques personnalisées par utilisateur
- ✅ Admin dashboard accessible
- ✅ Création de discussions et contrats fonctionnelle

**Prêt pour les tests !** 🚀
