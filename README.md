# Plateforme Artistique - Communauté et Organisation

Plateforme web développée avec Symfony 6.4 pour la gestion de communautés artistiques, organisations, produits et contrats.

## 🚀 Technologies

- **PHP** 8.2
- **Symfony** 6.4
- **Doctrine ORM** 2.20
- **Twig** 3.x
- **MySQL/MariaDB** 10.11+

## 📋 Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL ou MariaDB
- Git

## 🔧 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/hafedhhammami6560/plateforme-artistique.git
cd plateforme-artistique
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de la base de données

Copier le fichier `.env` et créer votre fichier local :

```bash
cp .env .env.local
```

Éditer `.env.local` et configurer votre connexion à la base de données :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/plateforme_artistique?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 5. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 6. (Optionnel) Charger les données SQL

Si vous avez des fichiers SQL à importer :

```bash
php scripts/apply_sql.php
```

## ▶️ Démarrer le serveur

### Serveur PHP intégré

```bash
php -S localhost:8000 -t public
```

Accédez à l'application : **http://localhost:8000**

### Avec Symfony CLI (recommandé)

```bash
symfony server:start
```

## 📂 Structure du projet

```
plateforme-artistique/
├── bin/              # Scripts console
├── config/           # Configuration Symfony
├── migrations/       # Migrations de base de données
├── public/           # Point d'entrée web
├── src/
│   ├── Controller/   # Contrôleurs
│   ├── Entity/       # Entités Doctrine
│   ├── Form/         # Formulaires Symfony
│   └── Repository/   # Repositories Doctrine
├── templates/        # Templates Twig
└── var/              # Cache et logs
```

## 🎯 Fonctionnalités principales

- **Gestion de communautés** : Création, édition, suppression
- **Gestion d'organisations** : CRUD complet
- **Gestion de produits** : Catalogue de produits artistiques
- **Gestion de contrats** : Suivi des contrats
- **Discussions** : Système de messagerie
- **Feedback** : Commentaires et retours
- **Administration** : Dashboard utilisateurs
- **Statistiques** : Tableau de bord analytique

## 📝 Routes principales

- `/` - Page d'accueil
- `/communite/` - Liste des communautés
- `/organisation/` - Liste des organisations
- `/produit/` - Catalogue de produits
- `/contrat/` - Gestion des contrats
- `/discussion/` - Discussions
- `/feedback/` - Système de feedback
- `/admin/users/` - Administration des utilisateurs
- `/user/dashboard/stats` - Statistiques utilisateur

## 🛠️ Commandes utiles

### Vider le cache

```bash
php bin/console cache:clear
```

### Créer une nouvelle entité

```bash
php bin/console make:entity
```

### Créer une migration

```bash
php bin/console make:migration
```

### Vérifier la base de données

```bash
php bin/console doctrine:schema:validate
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit vos changements (`git commit -m 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📄 License

Ce projet est sous licence propriétaire.

## 👥 Auteurs

- Hafedh Hammami - [@hafedhhammami6560](https://github.com/hafedhhammami6560)

## 📞 Support

Pour toute question ou problème, ouvrez une issue sur GitHub.

---

**Version actuelle :** 1.0.0  
**Dernière mise à jour :** Décembre 2025
