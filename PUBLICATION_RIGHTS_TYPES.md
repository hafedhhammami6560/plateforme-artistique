# Types de Publication Rights

## 📋 Vue d'ensemble

Le système supporte maintenant **deux types de contrats Publication Rights** :

### 1. **Publication Rights - Single Product** (`publication_rights_single`)
Acquisition des droits sur **un seul project spécifique**.

**Cas d'usage :**
- Un Publisher veut les droits sur une chanson particulière
- Un Sponsor veut les droits exclusifs sur une œuvre d'art unique
- Licence limitée à un project identifié

**Workflow :**
1. Publisher/Sponsor sélectionne un project existant
2. Négociation sur ce project uniquement
3. Contrat lié au project spécifique
4. Le project devient "sous contrat"

### 2. **Publication Rights - Full Catalog** (`publication_rights_catalog`)
Acquisition des droits sur **tous les projects de l'artiste** (actuels et futurs).

**Cas d'usage :**
- Label musical acquiert tout le catalogue d'un artiste
- Maison d'édition acquiert tous les écrits d'un scénariste
- Galerie acquiert l'ensemble des œuvres d'un artiste
- Contrat de distribution globale

**Workflow :**
1. Publisher/Sponsor négocie un accord global
2. Pas de project spécifique sélectionné
3. Contrat couvre tous les projects existants + futurs
4. Tous les projects de l'artiste sont liés au contrat

---

## 🔧 Implémentation Technique

### Constantes dans les Entités

#### Contrat.php & Discussion.php
```php
const TYPE_PUBLICATION_RIGHTS_SINGLE = 'publication_rights_single';  // project unique
const TYPE_PUBLICATION_RIGHTS_CATALOG = 'publication_rights_catalog'; // Catalogue complet
const TYPE_CUSTOM_ORDER = 'custom_order';

// Ancien type pour rétrocompatibilité (traité comme single)
const TYPE_PUBLICATION_RIGHTS = 'publication_rights';
```

### Méthodes Helper

#### Vérifier le type de contrat
```php
// Vérifie si c'est un type Publication Rights (n'importe lequel)
$contrat->isTypePublicationRights()  // true pour single OU catalog

// Vérifie spécifiquement le sous-type
$contrat->isTypePublicationRightsSingle()   // true pour single uniquement
$contrat->isTypePublicationRightsCatalog()  // true pour catalog uniquement
$contrat->isTypeCustomOrder()               // true pour custom order
```

---

## 📝 Différences Clés

| Critère | Single Product | Full Catalog |
|---------|---------------|--------------|
| **project requis** | ✅ Oui, obligatoire | ❌ Non, pas de project |
| **Sélection project** | Dans le formulaire | N/A |
| **Portée** | 1 seul project | Tous les projects |
| **projects futurs** | ❌ Non inclus | ✅ Inclus automatiquement |
| **Prix** | Par project | Pourcentage global / Forfait |
| **Durée typique** | Court/Moyen terme | Long terme / Permanent |

---

## 🎯 Règles Métier

### Publication Rights - Single
- **Obligatoire :** Un project doit être sélectionné
- Le project doit appartenir à l'artiste ciblé
- Le project ne doit pas être déjà sous contrat actif
- Une fois signé, le project est marqué "sous contrat"

### Publication Rights - Catalog
- **Aucun project** sélectionné dans le formulaire
- Tous les projects existants de l'artiste sont couverts
- Les projects créés **après signature** sont automatiquement inclus
- Impact sur tous les projects de l'artiste

---

## 🔄 Migration depuis l'ancien système

### Rétrocompatibilité
Les contrats existants avec `type = 'publication_rights'` sont automatiquement traités comme **Single Product** :

```php
public function isTypePublicationRightsSingle(): bool
{
    return $this->type === self::TYPE_PUBLICATION_RIGHTS_SINGLE || 
           $this->type === self::TYPE_PUBLICATION_RIGHTS; // Ancien type
}
```

### Données existantes
- Les contrats anciens continuent de fonctionner
- Aucune migration de base de données nécessaire
- Le champ `type` accepte les 3 valeurs

---

## 🛠️ Prochaines Étapes (TODO)

### 1. Mettre à jour les Formulaires
- [ ] Ajouter un choix de sous-type dans `DiscussionType`
- [ ] Ajouter un choix de sous-type dans `ContratType`
- [ ] Masquer le champ "project" si type = Catalog

### 2. Mettre à jour les Contrôleurs
- [ ] `DiscussionController` : gérer les deux sous-types
- [ ] `ContratController` : validation selon le sous-type
- [ ] Vérifier la logique de sélection de project

### 3. Mettre à jour les Templates
- [ ] Afficher le sous-type correctement dans les vues
- [ ] Adapter les labels : "project Unique" vs "Catalogue Complet"
- [ ] Badges visuels différents pour Single vs Catalog

### 4. Mettre à jour les Services
- [ ] `ContratService` : logique différente selon le sous-type
- [ ] `PermissionService` : vérifier les droits par sous-type
- [ ] Gestion des projects futurs pour Catalog

### 5. Tests
- [ ] Tester création Discussion avec chaque sous-type
- [ ] Tester création Contrat avec chaque sous-type
- [ ] Vérifier qu'un project ne peut avoir qu'un contrat Single actif
- [ ] Vérifier que Catalog bloque tous les projects

---

## 📊 Exemples d'utilisation

### Exemple 1 : Licence d'une chanson
```
Type: Publication Rights - Single Product
Artiste: John Doe (Musicien)
Client: MusicLabel Inc. (Publisher)
project: "Summer Vibes" (Single)
Prix: 5000€
Durée: 2 ans
```

### Exemple 2 : Contrat d'édition complet
```
Type: Publication Rights - Full Catalog
Artiste: Jane Smith (Scénariste)
Client: BookPublisher Co. (Publisher)
project: (Aucun - tous les écrits)
Prix: 15% des revenus
Durée: 10 ans
```

### Exemple 3 : Galerie d'art exclusive
```
Type: Publication Rights - Full Catalog
Artiste: Bob Artist (Artiste)
Client: ArtGallery Ltd. (Sponsor)
project: (Aucun - toutes les œuvres)
Prix: 100 000€ forfait + 20% des ventes
Durée: 5 ans renouvelable
```

---

**Dernière mise à jour :** 12 Décembre 2025  
**Version :** 2.0  
**Status :** Implémentation partielle - Entités et documentation prêtes
