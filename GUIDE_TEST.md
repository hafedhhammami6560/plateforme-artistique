# 🚀 Guide de Test - Module Gestion Contrats et Discussions

## ✅ Configuration Terminée !

**Base de données :** `artconnect`  
**URL de l'application :** `http://localhost:8008`  
**Serveur :** Démarré sur le port 8008

---

## 🔑 Comptes de Test

### 1. **Artiste**
```
Email    : artist@artconnect.com
Password : password
Type     : ROLE_ARTIST
```
**Peut :**
- Voir ses discussions
- Répondre aux messages
- Signer des contrats
- Accéder au BackOffice

---

### 2. **Publisher**
```
Email    : publisher@artconnect.com
Password : password
Type     : ROLE_PUBLISHER
```
**Peut :**
- Créer des discussions
- Proposer des contrats
- Activer des contrats signés
- Accéder au BackOffice

---

### 3. **Administrateur**
```
Email    : admin@artconnect.com
Password : password
Type     : ROLE_ADMIN
```
**Peut :**
- Tout voir et gérer
- Accéder à l'Admin Dashboard
- Voir toutes les discussions et contrats
- Accéder au BackOffice

---

## 📍 URLs Importantes

### **Authentification**
```
🔐 Connexion      : http://localhost:8008/login
🚪 Déconnexion    : http://localhost:8008/logout
```

### **FrontOffice** (Interface Utilisateur)
```
🏠 Accueil            : http://localhost:8008/
💬 Mes Discussions    : http://localhost:8008/discussions
📄 Mes Contrats       : http://localhost:8008/contracts
```

### **BackOffice** (Gestion Avancée)
```
📊 Dashboard          : http://localhost:8008/backoffice
💬 Gestion Discussions: http://localhost:8008/backoffice/discussions
📄 Gestion Contrats   : http://localhost:8008/backoffice/contracts
📈 Rapports           : http://localhost:8008/backoffice/contracts/reports
```

### **Administration** (ROLE_ADMIN uniquement)
```
🛡️ Admin Dashboard    : http://localhost:8008/admin
💬 Toutes Discussions : http://localhost:8008/admin/discussions
📄 Tous Contrats      : http://localhost:8008/admin/contracts
```

---

## 🧪 Scénarios de Test

### **Scénario 1 : Connexion et Navigation**
1. ✅ Aller sur `http://localhost:8008/login`
2. ✅ Se connecter avec `artist@artconnect.com` / `password`
3. ✅ Vérifier la redirection vers le BackOffice Dashboard
4. ✅ Tester la sidebar de navigation
5. ✅ Se déconnecter

---

### **Scénario 2 : Publisher - Créer une Discussion**
1. ✅ Se connecter avec `publisher@artconnect.com` / `password`
2. ✅ Aller sur `http://localhost:8008/discussions`
3. ✅ Cliquer sur "Nouvelle Discussion"
4. ✅ Sélectionner un produit et envoyer un message initial
5. ✅ Vérifier que la discussion apparaît dans la liste

---

### **Scénario 3 : Artiste - Répondre à une Discussion**
1. ✅ Se connecter avec `artist@artconnect.com` / `password`
2. ✅ Aller sur `http://localhost:8008/discussions`
3. ✅ Cliquer sur une discussion existante
4. ✅ Envoyer un message de réponse
5. ✅ Vérifier l'affichage du chat en temps réel

---

### **Scénario 4 : Publisher - Créer et Proposer un Contrat**
1. ✅ Se connecter avec `publisher@artconnect.com` / `password`
2. ✅ Aller dans une discussion active
3. ✅ Cliquer sur "Créer un Contrat"
4. ✅ Remplir les termes et le taux de commission (10-50%)
5. ✅ Proposer le contrat
6. ✅ Vérifier le statut "Proposé"

---

### **Scénario 5 : Artiste - Signer un Contrat**
1. ✅ Se connecter avec `artist@artconnect.com` / `password`
2. ✅ Aller sur `http://localhost:8008/contracts`
3. ✅ Ouvrir un contrat avec statut "Proposé"
4. ✅ Cliquer sur "Signer le Contrat"
5. ✅ Cocher les confirmations et signer
6. ✅ Vérifier le statut "Signé"

---

### **Scénario 6 : BackOffice - Voir les Analytics**
1. ✅ Se connecter (artiste ou publisher)
2. ✅ Aller sur `http://localhost:8008/backoffice`
3. ✅ Voir les statistiques du dashboard
4. ✅ Cliquer sur "Gestion Discussions"
5. ✅ Ouvrir l'analytics d'une discussion
6. ✅ Vérifier : temps de réponse moyen, engagement, activité par jour

---

### **Scénario 7 : BackOffice - Rapports Financiers**
1. ✅ Se connecter (artiste ou publisher)
2. ✅ Aller sur `http://localhost:8008/backoffice/contracts`
3. ✅ Cliquer sur "Rapport Financier" d'un contrat
4. ✅ Voir les données : commission, durée, dates
5. ✅ Aller sur `http://localhost:8008/backoffice/contracts/reports`
6. ✅ Voir les rapports globaux et contrats expirant bientôt

---

### **Scénario 8 : Admin - Gestion Globale**
1. ✅ Se connecter avec `admin@artconnect.com` / `password`
2. ✅ Aller sur `http://localhost:8008/admin`
3. ✅ Voir le dashboard admin avec statistiques globales
4. ✅ Aller sur "Toutes Discussions" et "Tous Contrats"
5. ✅ Vérifier l'accès à TOUTES les données (pas seulement les siennes)

---

## 🎯 Points Clés à Vérifier

### **Sidebar BackOffice**
- ✅ Navigation fluide entre les modules
- ✅ Sections distinctes : BackOffice / FrontOffice / Admin (si ROLE_ADMIN)
- ✅ Lien de déconnexion fonctionnel
- ✅ Responsive (mobile)

### **Permissions (Voters)**
- ✅ Un artiste ne peut pas éditer une discussion d'un autre artiste
- ✅ Un publisher ne peut pas signer un contrat (seul l'artiste peut)
- ✅ Seul l'admin peut voir TOUTES les discussions/contrats
- ✅ Les actions interdites n'affichent pas de boutons

### **Statuts de Workflow**
**Discussion :**
- ✅ `pending` → `active` → `closed` → peut rouvrir

**Contrat :**
- ✅ `draft` → `proposed` → `signed` → `active` → `terminated`

### **Interface de Chat**
- ✅ Messages alignés à droite (envoyés) et à gauche (reçus)
- ✅ Auto-scroll vers le bas
- ✅ Badge "Proposition de contrat" visible
- ✅ Formulaire d'envoi avec checkbox "Proposition de contrat"

### **Rapports et Analytics**
- ✅ Statistiques correctes sur le dashboard
- ✅ Charts/graphiques d'activité
- ✅ Alertes pour contrats expirant < 30 jours
- ✅ Moyenne de commission calculée

---

## 📊 Données de Test Disponibles

**6 Artistes :**
- Marie Dubois, Pierre Martin, Sophie Bernard, Lucas Thomas, Emma Petit, Test Artiste

**4 Publishers :**
- Galaxy Arts, Creative Co, Digital Dreams, Test Publisher

**10 Produits :**
- Peintures, Sculptures, Photos, Art Digital

**15 Discussions :**
- Statuts variés : pending, active, closed, archived

**8 Contrats :**
- Statuts : draft, proposed, signed, active, terminated
- Commissions : 15% à 35%

---

## ⚠️ Résolution de Problèmes

### **Erreur "Access Denied"**
→ Vérifiez que vous êtes connecté avec le bon compte (artiste/publisher/admin)

### **Page 404**
→ Vérifiez l'URL, essayez `http://localhost:8008` au lieu de `localhost:8008`

### **Serveur ne démarre pas**
```bash
# Arrêter tous les serveurs
symfony server:stop

# Redémarrer sur le port 8008
php -S localhost:8008 -t public
```

### **Erreur de base de données**
```bash
# Réinitialiser la base de données
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

---

## 🎉 Bon Test !

Le module est **100% fonctionnel** avec :
- ✅ Authentification complète
- ✅ Données de test réalistes
- ✅ 3 niveaux d'accès (FrontOffice / BackOffice / Admin)
- ✅ Sidebar de navigation professionnelle
- ✅ Analytics et rapports financiers
- ✅ Système de permissions complet

**Profitez de votre test ! 🚀**
