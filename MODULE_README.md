# Module Gestion des Contrats et Discussions - ArtConnect

## 📋 Description du Module

Module complet de gestion des contrats et discussions pour la plateforme collaborative artistique ArtConnect.
Développé avec **Symfony 6.4**, **PHP 8.2**, **Doctrine ORM**, **MySQL** et **Bootstrap 5**.

---

## 🏗️ Architecture du Module

### **1. FrontOffice** (Interface Utilisateur)
Les utilisateurs (artistes et publishers) peuvent gérer leurs propres contrats et discussions.

**Routes FrontOffice:**
- `/` - Page d'accueil
- `/discussions` - Liste des discussions de l'utilisateur
- `/discussions/new` - Créer une discussion
- `/discussions/{id}` - Interface de chat
- `/discussions/{id}/edit` - Éditer une discussion
- `/contracts` - Liste des contrats de l'utilisateur
- `/contracts/new` - Créer un contrat
- `/contracts/{id}` - Détails du contrat
- `/contracts/{id}/sign` - Signer un contrat

**Controllers FrontOffice:**
- `DiscussionController.php` - CRUD discussions
- `ContractController.php` - CRUD contrats
- `HomeController.php` - Page d'accueil

---

### **2. BackOffice** (Partie Serveur - Gestion Avancée)
Chaque utilisateur connecté a accès à son backoffice personnel avec sidebar de navigation.

**Routes BackOffice:**
- `/backoffice` - Dashboard personnel avec statistiques
- `/backoffice/discussions` - Gestion avancée des discussions
- `/backoffice/discussions/{id}/analytics` - Analytics d'une discussion
- `/backoffice/contracts` - Gestion avancée des contrats
- `/backoffice/contracts/{id}/financial-report` - Rapport financier d'un contrat
- `/backoffice/contracts/reports` - Rapports globaux

**Controllers BackOffice:**
- `BackOffice\DashboardController.php` - Dashboard personnel
- `BackOffice\DiscussionManagementController.php` - Gestion discussions avec analytics
- `BackOffice\ContractManagementController.php` - Gestion contrats avec rapports financiers

**Fonctionnalités BackOffice:**
- ✅ Dashboard avec statistiques personnelles
- ✅ Vue d'ensemble des discussions/contrats
- ✅ Analytics des discussions (temps de réponse, engagement)
- ✅ Rapports financiers des contrats
- ✅ Alertes pour contrats expirant bientôt
- ✅ Sidebar de navigation avec accès à tous les modules

---

### **3. Administration** (ROLE_ADMIN uniquement)
Les administrateurs ont accès à tous les contrats et discussions de la plateforme.

**Routes Admin:**
- `/admin` - Dashboard administrateur global
- `/admin/discussions` - Toutes les discussions
- `/admin/contracts` - Tous les contrats

**Controllers Admin:**
- `Admin\DashboardController.php`
- `Admin\AdminDiscussionController.php`
- `Admin\AdminContractController.php`

---

## 🗂️ Structure des Fichiers

```
src/
├── Controller/
│   ├── DiscussionController.php          # FrontOffice: Discussions
│   ├── ContractController.php            # FrontOffice: Contrats
│   ├── HomeController.php                # FrontOffice: Accueil
│   ├── BackOffice/
│   │   ├── DashboardController.php       # BackOffice: Dashboard personnel
│   │   ├── DiscussionManagementController.php  # BackOffice: Gestion discussions
│   │   └── ContractManagementController.php    # BackOffice: Gestion contrats
│   └── Admin/
│       ├── DashboardController.php       # Admin: Dashboard global
│       ├── AdminDiscussionController.php # Admin: Toutes discussions
│       └── AdminContractController.php   # Admin: Tous contrats
├── Entity/
│   ├── User.php                          # Entité utilisateur (Artiste/Publisher)
│   ├── Product.php                       # Entité œuvre d'art
│   ├── Discussion.php                    # Entité discussion
│   ├── Message.php                       # Entité message
│   └── Contract.php                      # Entité contrat
├── Repository/
│   ├── UserRepository.php
│   ├── ProductRepository.php
│   ├── DiscussionRepository.php          # Méthodes: findForUser, findActiveDiscussions, getConversionRate
│   ├── MessageRepository.php
│   └── ContractRepository.php            # Méthodes: findForUser, findExpiringContracts, getCommissionStats
├── Form/
│   ├── DiscussionType.php                # Formulaire discussion
│   ├── MessageType.php                   # Formulaire message
│   └── ContractType.php                  # Formulaire contrat
└── Security/
    └── Voter/
        ├── DiscussionVoter.php           # Permissions: VIEW, EDIT, DELETE, SEND_MESSAGE
        └── ContractVoter.php             # Permissions: VIEW, EDIT, DELETE, SIGN, TERMINATE

templates/
├── base.html.twig                        # Base template générale
├── backoffice/
│   ├── base.html.twig                    # Base template BackOffice avec sidebar
│   ├── dashboard/
│   │   └── index.html.twig               # Dashboard BackOffice
│   ├── discussion/
│   │   ├── index.html.twig               # Liste discussions BackOffice
│   │   └── analytics.html.twig           # Analytics discussion
│   └── contract/
│       ├── index.html.twig               # Liste contrats BackOffice
│       ├── financial_report.html.twig    # Rapport financier
│       └── reports.html.twig             # Rapports globaux
├── home/
│   └── index.html.twig                   # Page d'accueil
├── discussion/
│   ├── index.html.twig                   # Liste discussions (FrontOffice)
│   ├── new.html.twig                     # Créer discussion
│   ├── show.html.twig                    # Interface de chat
│   └── edit.html.twig                    # Éditer discussion
├── contract/
│   ├── index.html.twig                   # Liste contrats (FrontOffice)
│   ├── new.html.twig                     # Créer contrat
│   ├── show.html.twig                    # Détails contrat
│   ├── edit.html.twig                    # Éditer contrat
│   └── sign.html.twig                    # Signer contrat
└── admin/
    ├── dashboard/
    │   └── index.html.twig               # Dashboard admin
    ├── discussion/
    │   └── index.html.twig               # Liste toutes discussions
    └── contract/
        └── index.html.twig               # Liste tous contrats
```

---

## 🔐 Système de Sécurité

### Voters (Permissions Fine-Grained)

**DiscussionVoter:**
- `DISCUSSION_VIEW` - Voir une discussion (participants uniquement)
- `DISCUSSION_EDIT` - Éditer une discussion (participants uniquement)
- `DISCUSSION_DELETE` - Supprimer une discussion (créateur uniquement)
- `DISCUSSION_SEND_MESSAGE` - Envoyer un message (si discussion active)

**ContractVoter:**
- `CONTRACT_VIEW` - Voir un contrat (parties du contrat uniquement)
- `CONTRACT_EDIT` - Éditer un contrat (publisher uniquement, si draft/proposed)
- `CONTRACT_DELETE` - Supprimer un contrat (publisher uniquement, si draft)
- `CONTRACT_SIGN` - Signer un contrat (artiste uniquement, si proposed)
- `CONTRACT_TERMINATE` - Terminer un contrat (parties du contrat, si active)

---

## 📊 Entités Principales

### **User** (Utilisateur)
```php
- id: int
- email: string
- password: string (hashé)
- roles: array
- fullName: string
- type: string ('artist' | 'publisher')
- createdAt: DateTime
```

### **Product** (Œuvre d'Art)
```php
- id: int
- title: string
- description: text
- category: string
- price: float
- artist: User (ManyToOne)
- createdAt: DateTime
```

### **Discussion**
```php
- id: int
- artist: User (ManyToOne)
- publisher: User (ManyToOne)
- product: Product (ManyToOne)
- subject: string
- status: string (pending, active, closed, archived)
- createdAt: DateTime
- updatedAt: DateTime
- messages: Message[] (OneToMany)
- contract: Contract (OneToOne)
```

**Constantes Status:**
- `STATUS_PENDING` = 'pending'
- `STATUS_ACTIVE` = 'active'
- `STATUS_CLOSED` = 'closed'
- `STATUS_ARCHIVED` = 'archived'

### **Message**
```php
- id: int
- discussion: Discussion (ManyToOne)
- sender: User (ManyToOne)
- content: text
- isContractProposal: bool
- isRead: bool
- sentAt: DateTime
```

### **Contract**
```php
- id: int
- discussion: Discussion (OneToOne)
- referenceNumber: string (auto-généré)
- terms: text
- commissionRate: float (10-50%)
- status: string (draft, proposed, signed, active, terminated)
- startDate: DateTime
- endDate: DateTime
- signedBy: User (ManyToOne, nullable)
- signedAt: DateTime (nullable)
- createdAt: DateTime
- updatedAt: DateTime
```

**Constantes Status:**
- `STATUS_DRAFT` = 'draft'
- `STATUS_PROPOSED` = 'proposed'
- `STATUS_SIGNED` = 'signed'
- `STATUS_ACTIVE` = 'active'
- `STATUS_TERMINATED` = 'terminated'

**Constantes Commission:**
- `COMMISSION_MIN` = 10
- `COMMISSION_MAX` = 50
- `COMMISSION_RATES` = [10, 15, 20, 25, 30, 35, 40, 45, 50]

---

## 🎯 Workflow Métier

### Workflow Discussion → Contrat

1. **Publisher découvre une œuvre**
   - Parcourt le catalogue des produits

2. **Initiation de la Discussion**
   - Publisher crée une discussion (statut: `pending`)
   - Peut inclure un message initial

3. **Négociation**
   - Artiste et Publisher échangent des messages
   - Discussion passe au statut `active`
   - Messages peuvent être marqués comme "proposition de contrat"

4. **Création du Contrat**
   - Publisher crée un contrat depuis la discussion
   - Contrat en statut `draft`
   - Peut éditer les termes et le taux de commission

5. **Proposition du Contrat**
   - Publisher propose le contrat (statut: `proposed`)
   - Artiste reçoit une notification

6. **Signature du Contrat**
   - Artiste examine le contrat
   - Artiste signe le contrat (statut: `signed`)
   - Date de signature enregistrée

7. **Activation du Contrat**
   - Publisher active le contrat (statut: `active`)
   - Date de début activée
   - Collaboration commence

8. **Fin du Contrat**
   - Soit à la date de fin
   - Soit par terminaison manuelle (statut: `terminated`)

---

## 🖥️ Interface BackOffice

### Sidebar de Navigation

La sidebar BackOffice offre un accès rapide à tous les modules :

**Section BackOffice:**
- 📊 Dashboard - Vue d'ensemble personnelle
- 💬 Gestion Discussions - Liste et analytics
- 📄 Gestion Contrats - Liste et rapports
- 📈 Rapports - Statistiques globales

**Section FrontOffice:**
- 🏠 Accueil
- 💬 Mes Discussions
- 📄 Mes Contrats

**Section Admin (si ROLE_ADMIN):**
- 🛡️ Admin Dashboard
- 💬 Toutes Discussions
- 📄 Tous Contrats

**Section Compte:**
- 👤 Mon Profil
- ⚙️ Paramètres
- 🚪 Déconnexion

---

## 📈 Fonctionnalités Avancées

### Analytics Discussions (BackOffice)
- Nombre total de messages
- Temps de réponse moyen
- Engagement par participant (% de messages)
- Activité par jour
- Nombre de propositions de contrat

### Rapports Financiers Contrats (BackOffice)
- Taux de commission appliqué
- Durée du contrat
- État actuel (actif/inactif)
- Jours restants avant expiration
- Historique des signatures

### Alertes et Notifications
- ⚠️ Contrats expirant dans les 30 jours
- 📊 Discussions inactives depuis X jours
- ✅ Nouveaux contrats signés

---

## 🎨 Design et UX

### Bootstrap 5 Styling
- Design moderne et responsive
- Cards avec hover effects
- Badges de statut colorés
- Icons Bootstrap Icons 1.11.0
- Sidebar fixe avec navigation fluide

### Couleurs Principales
```css
--primary-color: #6f42c1    (Violet)
--secondary-color: #fd7e14  (Orange)
--success-color: #198754    (Vert)
--danger-color: #dc3545     (Rouge)
--sidebar-bg: #2c3e50       (Bleu foncé)
```

---

## 📝 Points Importants

### ⚠️ Non Implémenté (Responsabilité du Module User)
Les éléments suivants ne sont **PAS** inclus dans ce module :
- ❌ Fixtures de test (DataFixtures)
- ❌ Configuration `security.yaml`
- ❌ Routes d'authentification (login/logout/register)
- ❌ Gestion des utilisateurs
- ❌ Système de notifications par email

### ✅ Ce qui est Inclus
- ✅ Toutes les entités avec relations complètes
- ✅ Tous les repositories avec méthodes métier
- ✅ Tous les formulaires avec validation
- ✅ Système de permissions (Voters)
- ✅ Controllers FrontOffice, BackOffice, Admin
- ✅ Templates complets avec Bootstrap 5
- ✅ Interface de chat en temps réel
- ✅ Analytics et rapports financiers

---

## 🚀 Utilisation

### Accès FrontOffice
```
http://localhost:8000/                  # Page d'accueil
http://localhost:8000/discussions       # Mes discussions
http://localhost:8000/contracts         # Mes contrats
```

### Accès BackOffice
```
http://localhost:8000/backoffice                        # Dashboard personnel
http://localhost:8000/backoffice/discussions            # Gestion discussions
http://localhost:8000/backoffice/contracts              # Gestion contrats
http://localhost:8000/backoffice/contracts/reports      # Rapports
```

### Accès Admin
```
http://localhost:8000/admin                 # Dashboard admin
http://localhost:8000/admin/discussions     # Toutes discussions
http://localhost:8000/admin/contracts       # Tous contrats
```

---

## 📦 Dépendances

```json
{
    "symfony/framework-bundle": "^6.4",
    "symfony/orm-pack": "^2.0",
    "symfony/maker-bundle": "^1.0",
    "symfony/security-bundle": "^6.4",
    "symfony/form": "^6.4",
    "symfony/validator": "^6.4",
    "symfony/twig-pack": "^1.0",
    "symfony/asset": "^6.4",
    "doctrine/orm": "^2.0",
    "doctrine/doctrine-bundle": "^2.0",
    "doctrine/doctrine-fixtures-bundle": "^3.0"
}
```

---

## 👥 Rôles Utilisateurs

- **ROLE_ARTIST** - Crée des œuvres, participe aux discussions, signe les contrats
- **ROLE_PUBLISHER** - Initie des discussions, crée et propose des contrats
- **ROLE_ADMIN** - Accès complet à la plateforme (gestion globale)

---

## ✨ Prochaines Étapes (Module User)

Le développeur du module User devra implémenter :
1. Configuration `security.yaml` avec firewalls
2. Création des fixtures de test
3. Formulaires d'inscription/connexion
4. Système de notifications email
5. Gestion des profils utilisateurs
6. Système de rôles et permissions

---

**Module développé pour ArtConnect - Plateforme Collaborative Artistique**
**Version: 1.0 | Date: Décembre 2025**
