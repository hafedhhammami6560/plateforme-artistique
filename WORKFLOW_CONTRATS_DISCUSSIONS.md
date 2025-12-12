# Workflow Discussions et Contrats

## 🎯 Vue d'ensemble

Le système de discussions et contrats fonctionne selon un workflow collaboratif où deux parties négocient les termes d'un contrat avant de le finaliser et de le signer.

## 📋 Processus étape par étape

### 1. Création d'une Discussion

#### Type automatique selon les rôles

Le **type de discussion est automatiquement déterminé** selon l'initiateur et le destinataire :

| Initiateur | Destinataire | Type résultant |
|------------|--------------|----------------|
| **Sponsor** | N'importe qui | Type B (Custom Order) |
| **Publisher** | N'importe qui | Type A (Publication Rights) |
| **Visiteur/Utilisateur** | N'importe qui | Type B (Custom Order) |
| **Artiste/Musicien/Scénariste** | Publisher | Type A (Publication Rights) |
| **Artiste/Musicien/Scénariste** | Sponsor/Visiteur | Type B (Custom Order) |

#### Champs de la discussion

- **Titre** : Sujet de la discussion
- **Destinataire** : L'utilisateur avec qui négocier
- **Message initial** : Premier message de la conversation
- **projet** (optionnel pour Type A) : projet existant concerné

> ⚠️ **Important** : Le champ "Type de discussion" n'est plus visible dans le formulaire - il est déterminé automatiquement !

---

### 2. Échange de Messages

Une fois la discussion créée, les deux parties peuvent :

- ✉️ Échanger des messages pour discuter des détails
- 📝 Négocier les termes du futur contrat
- 🤝 S'accorder progressivement sur les conditions

**Statut de la discussion** : `en_cours`

---

### 3. Création de Brouillons de Contrats

À chaque étape où les parties s'accordent sur certains détails, elles peuvent créer un **brouillon de contrat**.

#### Comment créer un brouillon ?

1. Dans la page de discussion, cliquer sur **"Créer un nouveau brouillon de contrat"**
2. Remplir les informations du contrat :
   - Prix
   - Date de début et de fin
   - Conditions détaillées
   - projet (si Type A)

3. Le brouillon est enregistré avec le statut `brouillon`

#### Caractéristiques des brouillons

- ✅ **Modifiables** : Peuvent être édités tant qu'ils ne sont pas finalisés
- 📊 **Multiples** : Plusieurs brouillons peuvent coexister pour une même discussion
- 🔗 **Liés** : Chaque brouillon est lié à la discussion d'origine
- 📅 **Historisés** : Liste chronologique visible dans la discussion

**Statut du contrat** : `brouillon`

---

### 4. Itération et Ajustements

Les parties continuent à :

1. Échanger des messages
2. Créer de nouveaux brouillons avec les modifications
3. Comparer les différentes versions
4. Converger vers un accord final

---

### 5. Finalisation du Contrat

Une fois que toutes les parties sont d'accord sur tous les détails :

1. Un des brouillons est marqué comme **"contrat final"**
2. Le contrat passe au statut `final`
3. Il devient le contrat officiel lié à la discussion
4. Plus aucune modification n'est possible

**Statut du contrat** : `final` → `en_attente_signature`

---

### 6. Signatures

Les deux parties doivent signer le contrat final :

#### Ordre des signatures

1. **Première signature** : Une des parties signe
   - Statut : `en_attente_signature`
   - Le contrat devient **non-modifiable**

2. **Deuxième signature** : L'autre partie signe
   - Statut : `signe`
   - Le contrat est **officiellement actif**

#### Qui peut signer ?

- **Artiste** (ou Musicien, Scénariste) : Signe en tant que créateur
- **Client** (Publisher ou Sponsor) : Signe en tant que commanditaire

**Statut final du contrat** : `signe`

---

## 🎨 Interface Utilisateur

### Page Discussion

La page de discussion affiche :

```
┌─────────────────────────────────────────┐
│ 📝 Titre de la discussion               │
│ Badge: Type A ou Type B                 │
├─────────────────────────────────────────┤
│ Informations :                          │
│ - Initiateur                            │
│ - Destinataire                          │
│ - projet (si Type A)                   │
├─────────────────────────────────────────┤
│ 💬 Messages                             │
│ [Liste chronologique des messages]      │
│ [Formulaire de réponse]                 │
├─────────────────────────────────────────┤
│ 📄 Contrats et Brouillons              │
│                                         │
│ ℹ️ Workflow expliqué                    │
│                                         │
│ ✅ Contrat final (si lié)               │
│                                         │
│ 📋 Brouillons créés (X)                 │
│ ├─ Brouillon #1 - BROUILLON - 500€     │
│ ├─ Brouillon #2 - BROUILLON - 600€     │
│ └─ Brouillon #3 - FINAL - 650€         │
│                                         │
│ [+ Créer un nouveau brouillon]          │
└─────────────────────────────────────────┘
```

### Page Contrat

Chaque brouillon/contrat affiche :

- **Badge de statut** : BROUILLON / FINAL / SIGNÉ
- **Détails** : Prix, dates, conditions
- **Actions** :
  - Éditer (si brouillon)
  - Marquer comme final (si brouillon et accord)
  - Signer (si final et non encore signé par l'utilisateur)

---

## 🔒 Règles de Sécurité

### Permissions

- Seuls les **participants** de la discussion peuvent :
  - Voir la discussion
  - Envoyer des messages
  - Créer des brouillons de contrats
  - Signer le contrat final

### Restrictions

- ❌ Un brouillon ne peut être édité après la première signature
- ❌ Un contrat signé ne peut plus être modifié
- ❌ Une discussion terminée ne peut plus recevoir de messages

---

## 📊 États des Contrats

| Statut | Description | Actions possibles |
|--------|-------------|-------------------|
| `brouillon` | Version de travail | Éditer, Supprimer, Marquer comme final |
| `final` | Accord trouvé, prêt à signer | Signer |
| `en_attente_signature` | 1 signature sur 2 | Signer (pour l'autre partie) |
| `signe` | Les 2 parties ont signé | Consulter uniquement |

---

## 💡 Bonnes Pratiques

1. **Communication claire** : Utilisez les messages pour expliquer chaque modification
2. **Versions progressives** : Créez un brouillon à chaque accord partiel
3. **Nomenclature** : Numérotez vos brouillons dans les messages (v1, v2, etc.)
4. **Validation finale** : Relisez le contrat final avant de signer
5. **Archive** : Gardez trace de tous les brouillons pour référence

---

## 🚀 Exemple de Workflow Complet

```
1️⃣ Publisher crée une discussion avec un Artiste
   → Type A (Publication Rights) automatiquement déterminé
   
2️⃣ Échange de 5 messages pour discuter du projet
   
3️⃣ Premier brouillon créé : 500€, 6 mois
   → Statut : brouillon
   
4️⃣ 3 messages supplémentaires : négociation du prix
   
5️⃣ Deuxième brouillon créé : 600€, 6 mois
   → Statut : brouillon
   
6️⃣ 2 messages : accord sur la durée
   
7️⃣ Troisième brouillon créé : 650€, 12 mois
   → Statut : brouillon
   
8️⃣ Message : "OK pour ce contrat, on signe ?"
   
9️⃣ Marquer le brouillon #3 comme final
   → Statut : final
   
🔟 Artiste signe le contrat
   → Statut : en_attente_signature
   
1️⃣1️⃣ Publisher signe le contrat
   → Statut : signe ✅
   
✨ Contrat actif et exécutoire !
```

---

## 📱 Navigation

### Pour créer une discussion
```
Menu → Discussions → Nouvelle Discussion
```

### Pour créer un brouillon
```
Discussions → [Ouvrir une discussion] → Créer un nouveau brouillon
```

### Pour voir tous les contrats
```
Menu → Contrats → Liste des Contrats
```

### Pour filtrer par statut
```
Contrats → Filtrer par : Brouillon / Final / Signé
```

---

## 🎯 Objectifs du Système

✅ **Transparence** : Toutes les négociations sont tracées
✅ **Flexibilité** : Possibilité de créer plusieurs versions
✅ **Sécurité** : Contrats signés non-modifiables
✅ **Collaboration** : Workflow à deux parties équilibré
✅ **Historique** : Conservation de tous les brouillons

---

## 🆘 Support

Pour toute question sur le workflow :
- Consultez `USER_ACCESS_RIGHTS.md` pour les permissions
- Consultez `PERMISSIONS_SYSTEM.md` pour les rôles
- Consultez `GUIDE_UTILISATION_CONTRATS.md` pour les détails techniques
