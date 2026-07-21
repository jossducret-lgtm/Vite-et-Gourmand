# Vite & Gourmand

Application Symfony 7.4 de commande de menus traiteur pour **Vite & Gourmand** (Bordeaux).

Projet ECF – TP Développeur Web et Web Mobile.

**Site de démonstration :** https://viteetgourmand.net

---

## Sommaire

1. [Stack technique](#stack-technique)
2. [Prérequis](#prérequis)
3. [Installation locale (Laragon / Windows)](#installation-locale-laragon--windows)
4. [Comptes de démonstration](#comptes-de-démonstration)
5. [Fonctionnalités](#fonctionnalités)
6. [Rôles et espaces](#rôles-et-espaces)
7. [Emails (Mailpit)](#emails-mailpit)
8. [MongoDB (stats admin)](#mongodb-stats-admin)
9. [Variables d'environnement](#variables-denvironnement)
10. [Déploiement (Hostinger)](#déploiement-hostinger)
11. [Git workflow](#git-workflow)
12. [Structure du projet](#structure-du-projet)
13. [Livrables ECF](#livrables-ecf)
14. [Documentation](#documentation)

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Symfony 7.4, PHP 8.2+ |
| ORM | Doctrine |
| BDD relationnelle | MySQL 8 (utf8mb4) |
| BDD NoSQL | MongoDB 7 (stats commandes) |
| Front | Twig, Bootstrap 5, CSS custom |
| Emails | Symfony Mailer + Mailpit (dev) / SMTP (prod) |
| Auth | Symfony Security + SymfonyCasts Reset Password |
| Déploiement | Apache (`.htaccess`), Docker optionnel |

---

## Prérequis

- PHP 8.2+ avec extensions : `ctype`, `iconv`, `pdo_mysql`, `mongodb`, `intl`
- Composer 2
- MySQL 8 (Laragon recommandé sous Windows)
- MongoDB 7 (script local ou Atlas)
- Mailpit (emails en local)
- Git

Optionnel : Docker Compose, Symfony CLI, Node.js

---

## Installation locale (Laragon / Windows)

### 1. Cloner et installer les dépendances

```bash
git clone <url-du-repo>
cd Vite-et-Gourmand
composer install
```

### 2. Configurer l'environnement

Copier / adapter `.env` (ou créer `.env.local`) :

```env
APP_ENV=dev
APP_SECRET=changez_moi
DEFAULT_URI=http://127.0.0.1:8000

DATABASE_URL="mysql://root:@127.0.0.1:3306/viteetgourmand?serverVersion=8.4.3&charset=utf8mb4"
MONGODB_URL="mongodb://127.0.0.1:27017"
MAILER_DSN=smtp://127.0.0.1:1025
COMPANY_EMAIL="contact@vitegourmand.fr"
```

Sous Laragon, le mot de passe MySQL root est souvent **vide**.

### 3. Base MySQL (UTF-8)

**Git Bash / CMD** (le `<` ne fonctionne pas dans PowerShell) :

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS viteetgourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root --default-character-set=utf8mb4 viteetgourmand < database/schema.sql
mysql -u root --default-character-set=utf8mb4 viteetgourmand < database/seed.sql
```

**PowerShell :**

```powershell
$mysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"  # adapter la version

& $mysql -u root -e "CREATE DATABASE IF NOT EXISTS viteetgourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cmd /c "`"$mysql`" -u root --default-character-set=utf8mb4 viteetgourmand < database\schema.sql"
cmd /c "`"$mysql`" -u root --default-character-set=utf8mb4 viteetgourmand < database\seed.sql"
```

Puis :

```bash
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all --no-interaction
```

Réimporter les données de démo (accents) :

```bash
mysql -u root --default-character-set=utf8mb4 viteetgourmand < database/reseed-utf8.sql
mysql -u root --default-character-set=utf8mb4 viteetgourmand < database/seed.sql
```

### 4. MongoDB local

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-mongodb.ps1
php bin/console app:mongo:sync-orders
```

### 5. Mailpit

Lancer Mailpit (Laragon / Docker) :

- UI : http://127.0.0.1:8025  
- SMTP : `127.0.0.1:1025`

### 6. Démarrer l'application

```bash
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public
```

Application : http://127.0.0.1:8000

---

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | `jose@vitegourmand.fr` | `Admin123!@` |
| Employé | `employe@vitegourmand.fr` | `Employe123!@` |
| Client | `client@example.com` | `Client123!@` |

---

## Fonctionnalités

### Public
- Page d'accueil (menus mis en avant, horaires)
- Catalogue des menus avec filtres AJAX (thème, régime, personnes)
- Fiche menu (plats, allergènes, stock)
- Contact + mentions légales, CGV, confidentialité
- Charte graphique et accessibilité RGAA (skip link, labels, contraste)

### Client (`ROLE_USER`)
- Inscription / connexion / déconnexion
- Mot de passe oublié (email + lien sécurisé)
- Commande (prix, livraison, réduction 10 %, stock)
- Espace Mon compte (profil, historique, annulation)
- Avis après commande livrée / terminée

### Employé (`ROLE_EMPLOYEE`)
- Dashboard et gestion des commandes (statuts)
- CRUD plats, menus, thèmes / événements, horaires
- Upload d'images de menus
- Modération des avis
- Layout back-office partagé

### Admin (`ROLE_ADMIN`)
- Dashboard (employés, messages, top menu)
- Gestion des employés (création, activation / désactivation)
- Messages de contact
- Statistiques MongoDB (graphiques CA / commandes)

### Emails transactionnels
- Bienvenue, confirmation commande, commande terminée
- Retour matériel, contact, compte employé créé, reset mot de passe

---

## Rôles et espaces

| Rôle | Accès |
|------|--------|
| Visiteur | Pages publiques |
| `ROLE_USER` | `/mon-compte`, commande |
| `ROLE_EMPLOYEE` | `/employe` (+ droits USER) |
| `ROLE_ADMIN` | `/admin` (+ droits EMPLOYEE) |

Hiérarchie : `ADMIN` > `EMPLOYEE` > `USER`

---

## Emails (Mailpit)

En développement, tous les mails partent vers Mailpit (`MAILER_DSN=smtp://127.0.0.1:1025`).

Consulter : http://127.0.0.1:8025

En production : SMTP Hostinger (voir déploiement).

---

## MongoDB (stats admin)

- Collection : `vite_gourmand.order_stats`
- Sync automatique à la création / mise à jour de commande (`OrderStatsSubscriber`)
- Sync manuelle : `php bin/console app:mongo:sync-orders`
- Lecture admin : `/admin/statistiques`

### Purger les stats Mongo

```bash
# Via PHP (si mongosh absent)
php -r 'require "vendor/autoload.php"; (new MongoDB\Client("mongodb://127.0.0.1:27017"))->selectCollection("vite_gourmand","order_stats")->drop(); echo "OK\n";'
php bin/console app:mongo:sync-orders
```

### Note production (Hostinger mutualisé)

Sur hébergement **mutualisé** Hostinger, les connexions TLS sortantes vers **MongoDB Atlas** peuvent être bloquées (erreur `TLS handshake failed`).  
Dans ce cas : démontrer Mongo en local, ou déployer sur **VPS**.

---

## Variables d'environnement

| Variable | Rôle | Exemple local |
|----------|------|----------------|
| `APP_ENV` | Environnement | `dev` / `prod` |
| `APP_SECRET` | Clé Symfony | chaîne aléatoire |
| `DEFAULT_URI` | URL absolue (emails, liens) | `http://127.0.0.1:8000` |
| `DATABASE_URL` | MySQL | `mysql://root:@127.0.0.1:3306/viteetgourmand?...` |
| `MONGODB_URL` | MongoDB | `mongodb://127.0.0.1:27017` ou Atlas `mongodb+srv://...` |
| `MAILER_DSN` | SMTP | `smtp://127.0.0.1:1025` |
| `COMPANY_EMAIL` | Expéditeur / contact | `contact@vitegourmand.fr` |

**Ne jamais committer** `.env` avec des secrets de production.

---

## Déploiement Hostinger

### Préparation

1. PHP 8.3 + extensions `pdo_mysql`, `mongodb`, `intl`
2. Base MySQL créée dans hPanel
3. Boîte mail Hostinger (SMTP)
4. MongoDB Atlas (cluster gratuit) — si le mutualisé le permet
5. Document root = `public/` **ou** `.htaccess` racine qui redirige vers `public/`

### Fichiers Apache

- `public/.htaccess` : front controller Symfony (**obligatoire**, nom avec le point)
- `.htaccess` (racine) : redirection vers `public/` si besoin

Attention Windows/FTP : ne pas uploader `htaccess` sans le `.`

### `.env` production (exemple)

```env
APP_ENV=prod
APP_SECRET=cle_secrete_longue
DEFAULT_URI=https://viteetgourmand.net

DATABASE_URL="mysql://USER:PASS@localhost:3306/NOM_BDD?serverVersion=8.0&charset=utf8mb4"
MONGODB_URL="mongodb+srv://USER:PASS@cluster.mongodb.net/?retryWrites=true&w=majority"
MAILER_DSN="smtp://contact%40viteetgourmand.net:PASS@smtp.hostinger.com:465?encryption=ssl"
COMPANY_EMAIL="contact@viteetgourmand.net"
```

Dans `MAILER_DSN`, encoder `@` → `%40` (et les caractères spéciaux du mot de passe).

### Après upload

```bash
composer install --no-dev --optimize-autoloader   # ou uploader vendor/
# Importer schema.sql + seed.sql via phpMyAdmin
php bin/console cache:clear --env=prod
php bin/console app:mongo:sync-orders
chmod -R 775 var public/uploads
```

### Checklist

- [ ] HTTPS actif
- [ ] `APP_ENV=prod`
- [ ] Document root / `.htaccess` OK
- [ ] Login admin José
- [ ] Commande + email SMTP
- [ ] Stats Mongo (si réseau autorisé)
- [ ] Uploads menus OK

Voir aussi `docs/deploiement.md` et `Dockerfile`.

---

## Git workflow

| Branche | Rôle |
|---------|------|
| `main` | Production |
| `dev` | Intégration |
| `feature/*` | Une fonctionnalité |

Exemple de branches :

- `feature/bugfixes-stabilisation`
- `feature/database-sql-seed`
- `feature/emails-mailpit` / password-reset
- `feature/backoffice-admin-ux` (admin, Mongo, polish employé, UX, README)

Workflow : branche depuis `dev` → développements → merge `dev` → tests → merge `main`.

---

## Structure du projet

```
Vite-et-Gourmand/
├── assets/                 # CSS, Stimulus
├── config/                 # Symfony (security, messenger, services…)
├── database/
│   ├── schema.sql          # Schéma MySQL
│   ├── seed.sql            # Données de démo
│   └── reseed-utf8.sql     # Truncate avant réimport UTF-8
├── docs/                   # Livrables ECF
├── migrations/             # Migrations Doctrine
├── public/                 # Document root web
│   ├── index.php
│   ├── .htaccess
│   └── uploads/
├── scripts/
│   └── start-mongodb.ps1   # MongoDB portable Windows
├── src/
│   ├── Command/            # app:mongo:sync-orders
│   ├── Controller/
│   ├── Entity/
│   ├── Form/
│   ├── Repository/
│   ├── Security/           # UserChecker
│   └── Service/            # Email, Mongo, pricing…
├── templates/
├── .htaccess               # Redirect racine → public (Hostinger)
├── Dockerfile
├── compose.yaml
└── README.md
```

---

## Livrables ECF

- [x] Application Symfony fonctionnelle
- [x] `database/schema.sql` + `database/seed.sql`
- [x] Documentation technique
- [x] Manuel utilisateur
- [x] Charte graphique
- [x] Gestion de projet (Kanban / Git)
- [x] Schéma BDD / diagramme de classes (images dans `docs/`)
- [x] Déploiement (Hostinger + Docker)

---

## Documentation

| Fichier | Contenu |
|---------|---------|
| `docs/documentation-technique.md` | Architecture, sécurité, Mongo |
| `docs/manuel-utilisateur.md` | Guide client / employé / admin |
| `docs/charte-graphique.md` | Identité visuelle |
| `docs/gestion-projet.md` | Méthode Kanban, branches |
| `docs/deploiement.md` | Docker / PaaS / checklist |

---

## Auteurs

Projet ECF – Vite & Gourmand (traiteur à Bordeaux).