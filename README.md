# Batifer TCO

Application web de calcul et d'analyse du coût total de possession (TCO) pour
les importations. Construite pour **Batifer Interfer** dans le cadre d'un
projet PFE (ENCG Béni Mellal).

L'application permet la saisie de dossiers d'importation avec 14 types de
costs, le calcul en temps réel du TCO et du ratio frais d'approche, la gestion
multi-produits, l'export CSV/JSON, et des statistiques agrégées par
fournisseur, pays, et famille de produit.

## Prérequis

| Outil | Version |
|-------|---------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 20+ |
| npm | 10+ |
| SQLite ou MySQL/MariaDB | — |

> Le projet utilise **SQLite** par défaut pour le développement local. Aucun
> serveur de base de données n'est requis pour démarrer.

## Installation

1. Clonez le dépôt et accédez au dossier du projet :

```bash
git clone <url-du-depot>
cd batifer-tco
```

2. Lancez le script d'installation automatique :

```bash
composer setup
```

Cette commande exécute dans l'ordre :
- `composer install` — installation des dépendances PHP
- copie de `.env.example` vers `.env` si le fichier n'existe pas
- `php artisan key:generate` — génération de la clé d'application
- `php artisan migrate` — exécution des migrations
- `npm install --ignore-scripts` — installation des dépendances JS
- `npm run build` — compilation des assets frontend

3. Démarrez l'environnement de développement :

```bash
composer dev
```

Cette commande lance simultanément quatre processus :
- **server** — `php artisan serve` (serveur PHP sur `localhost:8000`)
- **queue** — `php artisan queue:listen` (worker de file d'attente)
- **logs** — `php artisan pail` (logs en temps réel)
- **vite** — `npm run dev` (serveur Vite avec HMR)

Ouvrez `http://localhost:8000` dans votre navigateur.

## Architecture

L'application suit une architecture **Laravel + Inertia.js + Vue 3** monolithique.
Le backend sert à la fois l'API et le rendu des pages via Inertia.

```
┌─────────────────────────────────┐
│         Browser (Vue 3)         │
│  ┌───────────────────────────┐  │
│  │    useCalculator.js       │  │
│  │  (TCO engine, client-side)│  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │  Pages + Components (Vue) │  │
│  └───────────┬───────────────┘  │
└──────────────┼──────────────────┘
               │ Inertia.js (HTTP)
┌──────────────▼──────────────────┐
│       Laravel (Backend)         │
│  Controllers → Eloquent Models  │
└──────────────┬──────────────────┘
               │ Eloquent ORM
┌──────────────▼──────────────────┐
│      SQLite / MySQL             │
└─────────────────────────────────┘
```

### Répartition des responsabilités

| Concerne | Client (Vue) | Serveur (Laravel) |
|----------|-------------|-------------------|
| Calcul TCO | ✅ Recalcul temps réel | ❌ |
| Rendu formulaires | ✅ | ❌ |
| Graphiques | ✅ Chart.js | ❌ |
| Persistance données | ❌ | ✅ Eloquent → BDD |
| Export CSV/JSON | ❌ | ✅ PHP |
| Statistiques agrégées | ❌ | ✅ SQL SUM/AVG/COUNT |
| Validation | ❌ | ✅ Laravel Validator |

### Stack technique

| Couche | Technologie | Version |
|--------|------------|---------|
| Backend | Laravel | 13.x |
| Frontend | Vue 3 | 3.5+ |
| Pont Backend/Frontend | Inertia.js | 3.1+ |
| CSS | Tailwind CSS | 3.4+ |
| Build | Vite | 8.x |
| Graphiques | Chart.js | 4.5+ |
| Routes nommées (JS) | Ziggy | 2.6+ |
| Tests PHP | PHPUnit | 12.x |
| Tests JS | Vitest | 4.x |
| Formatage PHP | Laravel Pint | 1.27+ |

## Structure du projet

```
batifer-tco/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php          # Contrôleur de base
│   │       ├── DossierController.php   # CRUD dossiers
│   │       ├── StatsController.php     # Statistiques agrégées
│   │       ├── ExportController.php    # Export CSV/JSON
│   │       └── SettingController.php   # Paramètres (seuils)
│   ├── Models/
│   │   ├── Dossier.php                 # Dossier d'importation
│   │   ├── CoutDossier.php             # Coûts par dossier (pivot)
│   │   ├── Produit.php                 # Produits multi-par dossier
│   │   ├── Setting.php                 # Configuration (seuils)
│   │   └── User.php                    # Utilisateur (Laravel)
│   └── Providers/
├── config/
│   ├── database.php                    # Configuration BDD
│   └── ...                             # Autres configs Laravel
├── database/
│   ├── database.sqlite                 # Base SQLite (défaut)
│   ├── migrations/                     # Schéma de la BDD
│   │   ├── 0001_*_create_users_table.php
│   │   ├── 2026_*_create_dossiers_table.php
│   │   ├── 2026_*_create_cout_dossiers_table.php
│   │   ├── 2026_*_create_produits_table.php
│   │   └── 2026_*_create_settings_table.php
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── js/
│   │   ├── app.js                      # Point d'entrée Vue/Inertia
│   │   ├── Pages/                      # Pages Inertia (une par vue)
│   │   │   ├── Saisie.vue              # Saisie de dossier
│   │   │   ├── Analyse.vue             # Analyse TCO
│   │   │   ├── Dossiers.vue            # Liste des dossiers
│   │   │   ├── Statistiques.vue        # Stats globales
│   │   │   ├── ParProduit.vue          # Stats par produit
│   │   │   ├── ParFournisseur.vue      # Stats par fournisseur
│   │   │   ├── Parametres.vue          # Configuration seuils
│   │   │   └── Export.vue              # Page d'export
│   │   ├── Components/                 # Composants Vue réutilisables
│   │   │   ├── Layout.vue              # Shell (header, tabs, profile)
│   │   │   ├── Sidebar.vue             # Formulaire de saisie
│   │   │   ├── KpiGrid.vue             # Grille de KPIs
│   │   │   ├── ParetoChart.vue         # Diagramme de Pareto
│   │   │   ├── PieChart.vue            # Camembert Chart.js
│   │   │   ├── GaugeList.vue           # Jauges de statut
│   │   │   ├── AnomalyTable.vue        # Tableau des anomalies
│   │   │   ├── SynthesePanel.vue       # Panneau de synthèse
│   │   │   ├── PrixUnitairePanel.vue   # Prix unitaire multi-produit
│   │   │   ├── DossierCard.vue         # Carte dossier
│   │   │   ├── SeuilsForm.vue          # Formulaire des seuils
│   │   │   ├── Modal.vue               # Dialogue modal
│   │   │   └── Toast.vue               # Notifications toast
│   │   ├── composables/
│   │   │   └── useCalculator.js        # Moteur de calcul TCO
│   │   └── lib/
│   │       ├── constants.js            # COSTS_DEF, PAYS_ALE, etc.
│   │       ├── formatters.js           # fmt(), fmtK(), pct()
│   │       └── formatters.test.js      # Tests formatters
│   ├── css/
│   └── views/
│       └── app.blade.php               # Template Blade racine
├── routes/
│   ├── web.php                         # Routes principales
│   └── console.php                     # Commandes artisan
├── tests/
│   ├── Feature/                        # Tests fonctionnels
│   ├── Unit/                           # Tests unitaires
│   └── js/                             # Tests JavaScript (Vitest)
├── tailwind.config.js                  # Design tokens (couleurs, fonts)
├── vite.config.js                      # Config build Vite
├── composer.json                       # Dépendances PHP + scripts
├── package.json                        # Dépendances JS
└── phpunit.xml                         # Config tests PHP
```

## Modèle de données

### Schéma relationnel

```
dossiers (1) ──< cout_dossiers (N)
dossiers (1) ──< produits (N)
settings (clé-valeur)
```

### Table `dossiers`

Dossier principal d'importation.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint PK | Identifiant auto-incrémenté |
| `ref` | varchar(50) | Référence dossier (ex: "001/26") |
| `frs` | varchar(100) | Nom du fournisseur |
| `pays` | varchar(50) | Pays d'origine |
| `incoterm` | varchar(3) | Incoterm (CFR, FOB, EXW...) |
| `famille` | varchar(50) | Famille de produit |
| `devise` | varchar(3) | Devise (EUR, USD, MAD...) |
| `unite` | varchar(20) | Unité de mesure (KG, T...) |
| `cert_origine` | varchar(10) | Certificat d'origine (oui/non/na) |
| `px_devise` | decimal(12,2) | Prix en devise étrangère |
| `taux` | decimal(8,4) | Taux de change vers MAD |
| `qte` | decimal(12,2) | Quantité |
| `notes` | text | Observations |
| `fret_montant_orig` | decimal(12,2) | Montant fret (devise origine) |
| `fret_devise_orig` | varchar(3) | Devise du fret |
| `fret_taux_orig` | decimal(8,4) | Taux de change du fret |
| `user_id` | varchar(50) | Identifiant utilisateur (index) |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de modification |

### Table `cout_dossiers`

Coûts associés à chaque dossier (14 types possibles).

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint PK | Identifiant |
| `dossier_id` | bigint FK | Référence vers `dossiers.id` |
| `cout_id` | varchar(20) | Code du coût (fret, assur, douane...) |
| `montant` | decimal(12,2) | Montant en MAD |

Contrainte unique : `(dossier_id, cout_id)`.

### Table `produits`

Produits multiples par dossier.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint PK | Identifiant |
| `dossier_id` | bigint FK | Référence vers `dossiers.id` |
| `description` | varchar(255) | Description du produit |
| `quantite` | decimal(12,2) | Quantité |
| `unite` | varchar(20) | Unité de mesure |
| `ratio` | decimal(6,4) | Fraction de répartition |

### Table `settings`

Configuration de l'application (seuils).

| Colonne | Type | Description |
|---------|------|-------------|
| `key` | varchar(50) PK | Clé du paramètre |
| `value` | json | Valeur (JSON) |

Clés utilisées :
- `seuilsData` — seuils par type de coût
- `ratioSeuils` — seuils globaux du ratio FA `{ ok, warn, bad }`

## Connexion à la base de données

### SQLite (défaut, développement)

Le projet utilise SQLite par défaut. Aucune configuration supplémentaire n'est
nécessaire. La base est stockée dans `database/database.sqlite`.

Les tests PHPUnit utilisent SQLite en mémoire (`:memory:`).

### MySQL / MariaDB (production)

Pour passer à MySQL, modifiez le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=batifer_tco
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

Puis exécutez les migrations :

```bash
php artisan migrate
```

Les connexions suivantes sont préconfigurées dans `config/database.php` :
- `sqlite` — défaut
- `mysql` — MySQL 8+
- `mariadb` — MariaDB
- `pgsql` — PostgreSQL
- `sqlsrv` — SQL Server

## Routes

### Pages (Inertia)

| Méthode | URL | Vue | Description |
|---------|-----|-----|-------------|
| GET | `/` | Saisie.vue | Saisie de dossier |
| GET | `/analyse` | Analyse.vue | Analyse TCO |
| GET | `/dossiers` | Dossiers.vue | Liste des dossiers |
| GET | `/statistiques` | Statistiques.vue | Statistiques globales |
| GET | `/produits` | ParProduit.vue | Stats par produit |
| GET | `/fournisseurs` | ParFournisseur.vue | Stats par fournisseur |
| GET | `/parametres` | Parametres.vue | Configuration seuils |
| GET | `/export` | Export.vue | Page d'export |

### API CRUD

| Méthode | URL | Action | Description |
|---------|-----|--------|-------------|
| GET | `/dossiers` | index | Liste des dossiers |
| POST | `/dossiers` | store | Créer un dossier |
| GET | `/dossiers/{id}` | show | Détail d'un dossier |
| PUT/PATCH | `/dossiers/{id}` | update | Modifier un dossier |
| DELETE | `/dossiers/{id}` | destroy | Supprimer un dossier |

### Autres endpoints

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/settings` | Sauvegarder les paramètres |
| GET | `/export/csv` | Télécharger CSV |
| GET | `/export/json` | Télécharger JSON |

## Guide de développement

### Ajouter une nouvelle page

1. Créez le fichier Vue dans `resources/js/Pages/` :

```vue
<template>
  <div>
    <!-- Votre contenu -->
  </div>
</template>

<script setup>
// Props reçues du contrôleur via Inertia
const props = defineProps({})
</script>
```

Le layout (`Layout.vue`) est appliqué automatiquement à toutes les pages.

2. Ajoutez la route dans `routes/web.php` :

```php
Route::get('/ma-page', fn () => Inertia::render('MaPage'))->name('ma-page');
```

3. Ajoutez le lien dans la barre de navigation (`Sidebar.vue` ou `Layout.vue`).

### Ajouter un nouveau composant

1. Créez le fichier dans `resources/js/Components/`
2. Utilisez-le dans une page ou un autre composant :

```vue
<script setup>
import MonComposant from '@/Components/MonComposant.vue'
</script>
```

### Modifier le schéma de la base de données

1. Créez une migration :

```bash
php artisan make:migration ajouter_colonne_exemple_dossiers
```

2. Éditez le fichier généré dans `database/migrations/` :

```php
Schema::table('dossiers', function (Blueprint $table) {
    $table->string('nouvelle_colonne')->nullable();
});
```

3. Exécutez la migration :

```bash
php artisan migrate
```

4. Mettez à jour le modèle Eloquent (`app/Models/Dossier.php`) pour ajouter la
   colonne dans `$fillable`.

5. Mettez à jour la validation dans le contrôleur concerné.

### Ajouter un nouveau type de coût

Les 14 types de coûts sont définis dans `resources/js/lib/constants.js`
(COSTS_DEF). Pour en ajouter un :

1. Ajoutez une entrée dans le tableau `COSTS_DEF` :

```js
{ id: 'nouveau', label: 'Nouveau Coût', color: '#hex', type: 'struct',
  seuil_ok: 1, seuil_warn: 2, seuil_bad: 4 }
```

2. Le système de coûts est dynamique — les entrées `cout_dossiers` sont créées
   à la volée via le champ `cout_id` (varchar). Aucune migration nécessaire.

### Calcul TCO — Formules

Le moteur de calcul est dans `resources/js/composables/useCalculator.js` :

- **TCO** = `px_mad + somme(tous les coûts)`
- **Frais d'approche** = `somme(coûts) - fret`
- **Ratio FA** = `frais / (px_mad + fret) × 100`
- **Prix MAD** = `px_devise × taux`
- **Fret** (si incoterm EXW/FOB/FCA/FAS) = `fret_montant_orig × fret_taux_orig`

### Design tokens

Les couleurs et polices sont centralisées dans `tailwind.config.js` :

```js
colors: {
  navy:    '#16316b',   // Couleur principale
  accent:  '#00a887',   // Vert accent
  danger:  '#d93025',   // Rouge erreur
  warn:    '#f59e0b',   // Orange avertissement
  ok:      '#0f9d58',   // Vert succès
  // ...
},
fontFamily: {
  mono: ['"Space Mono"', ...defaultTheme.fontFamily.mono],
  sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
}
```

## Tests

### Tests PHP (PHPUnit)

```bash
# Exécuter tous les tests
composer test

# Ou directement
php artisan test

# Tests avec couverture
vendor/bin/phpunit --coverage-text
```

Les tests sont organisés dans `tests/Feature/` (tests fonctionnels) et
`tests/Unit/` (tests unitaires).

### Tests JavaScript (Vitest)

```bash
npx vitest run
```

Les tests JS se trouvent dans `resources/js/lib/` et `tests/js/`.

### Formatage du code

```bash
# PHP — Laravel Pint
vendor/bin/pint

# JS/CSS — pas de config Prettier, formatage manuel
```

## Scripts disponibles

| Commande | Description |
|----------|-------------|
| `composer setup` | Installation complète du projet |
| `composer dev` | Lance server + queue + logs + vite |
| `composer test` | Exécute les tests PHPUnit |
| `npm run dev` | Serveur Vite avec HMR |
| `npm run build` | Compilation des assets pour la production |
| `php artisan serve` | Serveur PHP seul |
| `php artisan migrate` | Exécute les migrations |
| `php artisan migrate:rollback` | Annule la dernière migration |
| `php artisan tinker` | Console interactive Laravel |

## Variables d'environnement

Les variables principales à configurer dans `.env` :

| Variable | Défaut | Description |
|----------|--------|-------------|
| `APP_NAME` | Laravel | Nom de l'application |
| `APP_ENV` | local | Environnement (local/production) |
| `APP_KEY` | auto | Clé de chiffrement |
| `APP_DEBUG` | true | Mode debug |
| `DB_CONNECTION` | sqlite | Pilote BDD (sqlite/mysql/pgsql) |
| `DB_DATABASE` | — | Nom de la base |
| `DB_HOST` | 127.0.0.1 | Hôte BDD |
| `DB_PORT` | 3306 | Port BDD |
| `DB_USERNAME` | root | Utilisateur BDD |
| `DB_PASSWORD` | — | Mot de passe BDD |
| `CACHE_STORE` | database | Driver de cache |
| `SESSION_DRIVER` | database | Driver de session |
| `QUEUE_CONNECTION` | database | Driver de file d'attente |
