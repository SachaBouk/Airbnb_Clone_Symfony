# Airbnb Clone — API Symfony

API REST d'un clone d'Airbnb réalisée avec Symfony 8.1 (TP DNMADE3 2026-2027) : des hôtes publient des logements,
des voyageurs les réservent, les notent et suivent d'autres utilisateurs.

## Sommaire

1. [Stack](#stack)
2. [Installation](#installation)
3. [Lancer le projet](#lancer-le-projet)
4. [Comptes de test](#comptes-de-test)
5. [Utiliser l'API](#utiliser-lapi)
6. [Routes](#routes)
7. [Modèle de données](#modèle-de-données)
8. [Importer des logements](#importer-des-logements)
9. [Tests et qualité du code](#tests-et-qualité-du-code)
10. [Organisation du code](#organisation-du-code)

## Stack

- PHP 8.4, Symfony 8.1
- Doctrine ORM + migrations, base **SQLite** (fichier `var/data_dev.db`, aucun serveur de base de données à lancer)
- Authentification **JWT** (LexikJWTAuthenticationBundle)
- Serializer, Validator, Security (voters), CORS (NelmioCorsBundle)
- Dev : MakerBundle, DoctrineFixturesBundle + Faker, PHPUnit, PHP CS Fixer

## Installation

Prérequis : PHP 8.4 avec l'extension `pdo_sqlite`, [Composer](https://getcomposer.org/) et, de préférence,
la [CLI Symfony](https://symfony.com/download).

```bash
git clone <url-du-depot> && cd Airbnb_Clone_Symfony
composer install

# Clés de signature des tokens JWT (non versionnées), protégées par JWT_PASSPHRASE du fichier .env
php bin/console lexik:jwt:generate-keypair

# Base de données : création du schéma puis données de test
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

> Les secrets de `.env` (`APP_SECRET`, `JWT_PASSPHRASE`) sont des valeurs de développement.
> En production, les redéfinir dans `.env.local` ou avec `php bin/console secrets:set`.

## Lancer le projet

```bash
symfony serve -d          # API sur http://127.0.0.1:8000 (arrêt : symfony server:stop)
# ou, sans la CLI Symfony :
php -S 127.0.0.1:8000 -t public
```

Vérification : `GET http://127.0.0.1:8000/api/health` doit répondre `{"status":"ok","database":"ok"}`.

## Comptes de test

Créés par les fixtures, mot de passe **`password`** pour tous :

| Email | Rôle dans les données |
| --- | --- |
| `host@example.com` | Hôte du premier logement de la liste (qui a des réservations) |
| `traveler@example.com` | Voyageur : a terminé un séjour dans ce premier logement sans le noter, et suit des auteurs d'avis |

Les fixtures créent aussi 28 autres utilisateurs, 25 logements, 12 équipements, des réservations, des avis et des abonnements.
Elles **vident la base** avant de la remplir.

## Utiliser l'API

Toutes les routes sont décrites et prêtes à être lancées dans **[`api.http`](api.http)**
(extension VS Code *REST Client* ou *httpYac*, ou client HTTP de PhpStorm). Exécuter d'abord les requêtes nommées
`listings`, `amenities`, `loginHost` et `loginTraveler` : les suivantes réutilisent leurs réponses (ids, tokens),
car les ids changent à chaque rechargement des fixtures.

### S'authentifier

```http
POST /api/login_check
Content-Type: application/json

{ "email": "host@example.com", "password": "password" }
```

Réponse : `{ "token": "eyJ...", "ttl": 3600, "user": { "id": 1, "email": "...", ... } }`.
Envoyer ensuite le token dans chaque requête qui l'exige : `Authorization: Bearer <token>` (valable 1 heure).

### Règles d'accès

- **Lecture (`GET`) publique**, sauf `/api/user` et `/api/feed`.
- **Toute écriture (`POST`, `PUT`, `DELETE`) exige un token**, sinon `401`.
- Seul **l'hôte** modifie ou supprime son logement, seul **l'auteur** modifie ou supprime son avis, sinon `403`.
- Pour **noter** un logement, il faut y avoir terminé un séjour confirmé et ne pas en être l'hôte ; un seul avis par logement.

### Pagination

Les listes acceptent `?page=` (1 par défaut) et `?limit=` (20 par défaut, 50 maximum) et renvoient :

```json
{ "total": 37, "lastPage": 2, "items": [ ... ] }
```

### Codes de réponse

`200` OK · `201` créé (en-tête `Location`) · `204` sans contenu · `400` JSON mal formé · `401` non connecté ou token invalide ·
`403` action interdite · `404` introuvable · `409` conflit (avis en double, logement avec réservations) ·
`422` données invalides (détail champ par champ dans `violations`).

## Routes

| Méthode | Route | Accès | Description |
| --- | --- | --- | --- |
| GET | `/api/health` | public | État de l'API et de la base |
| POST | `/api/login_check` | public | Connexion, renvoie le token JWT |
| GET | `/api/user` | connecté | Utilisateur connecté |
| GET | `/api/listings` | public | Logements : filtres, tri, pagination (voir ci-dessous) |
| GET | `/api/listings/{id}` | public | Détail d'un logement, avec ses avis et sa note moyenne |
| POST | `/api/listings` | connecté | Créer un logement (l'utilisateur connecté en devient l'hôte) |
| PUT | `/api/listings/{id}` | hôte | Modifier un logement (remplacement complet) |
| DELETE | `/api/listings/{id}` | hôte | Supprimer un logement (`409` s'il a des réservations ou des avis) |
| GET | `/api/amenities` | public | Équipements disponibles (ids et slugs) |
| GET | `/api/reviews` | public | Fil des derniers avis, tous logements |
| GET | `/api/feed` | connecté | Fil des avis des personnes que je suis |
| POST | `/api/listings/{id}/reviews` | voyageur ayant séjourné | Noter un logement (1 à 5) |
| PUT | `/api/reviews/{id}` | auteur | Modifier son avis |
| DELETE | `/api/reviews/{id}` | auteur | Supprimer son avis |
| POST | `/api/users/{id}/follow` | connecté | Suivre un utilisateur |
| DELETE | `/api/users/{id}/follow` | connecté | Ne plus suivre un utilisateur |
| GET | `/api/users/{id}/followers` | public | Abonnés d'un utilisateur |
| GET | `/api/users/{id}/following` | public | Abonnements d'un utilisateur |

### Filtres de `GET /api/listings`

| Paramètre | Exemple | Effet |
| --- | --- | --- |
| `title` | `villa` | Titre contenant ce texte (insensible à la casse) |
| `city`, `country` | `paris`, `france` | Ville / pays exacts (insensible à la casse) |
| `guests` | `4` | Peut accueillir au moins ce nombre de voyageurs |
| `maxPrice` | `150` | Prix par nuit maximum |
| `amenity` | `wifi` | Possède cet équipement (slug) |
| `availableFrom` + `availableTo` | `2026-12-20` / `2026-12-27` | Sans réservation en attente ou confirmée sur la période |
| `sort` | `rating`, `price_asc`, `price_desc` | Mieux notés d'abord, ou par prix (par défaut : par id) |
| `page`, `limit` | `2`, `10` | Pagination |

Chaque logement de la liste contient sa note moyenne (`averageRating`) et son nombre d'avis (`reviewCount`) ;
connecté, il contient aussi **`myRating`** : la note que l'on a donnée, ou `null`.

Exemple : `GET /api/listings?title=appartement&amenity=wifi&sort=rating&page=1&limit=5`

### Corps des requêtes d'écriture

```jsonc
// POST / PUT /api/listings
{ "title": "Cabane au bord du lac", "description": "...", "priceByNight": 95, "city": "Annecy",
  "country": "France", "maxGuests": 3, "amenityIds": [1, 2] }

// POST /api/listings/{id}/reviews et PUT /api/reviews/{id}
{ "rating": 4, "comment": "Logement très propre." }
```

## Modèle de données

| Entité | Rôle | Relations |
| --- | --- | --- |
| `User` | Hôte et/ou voyageur | **OneToMany** `listings` (logements hébergés) et `reviews` (avis écrits) ; **ManyToMany sur elle-même** `following` / `followers` (table `user_follow`) |
| `Listing` | Logement | ManyToOne `host` ; **ManyToMany** `amenities` (table `listing_amenity`) |
| `Amenity` | Équipement (Wi-Fi, piscine…) | ManyToMany `listings` |
| `Booking` | Réservation, statut `pending` / `confirmed` / `cancelled` (enum `BookingStatus`) | ManyToOne `traveler`, `listing` |
| `Review` | Avis noté de 1 à 5, unique par (auteur, logement) | ManyToOne `author`, `listing` |

Toutes les entités ont des dates `createdAt` / `updatedAt` remplies automatiquement (`TimestampableTrait`).

Le schéma évolue uniquement par migrations : `php bin/console make:migration` puis
`php bin/console doctrine:migrations:migrate`.

## Importer des logements

Contrairement aux fixtures (réservées au développement), la commande d'import peut alimenter une base de production :

```bash
php bin/console app:import-listings                    # importe data/listings.jsonl (12 logements)
php bin/console app:import-listings chemin/fichier.jsonl
```

Le fichier contient **un logement par ligne** au format JSON (voir [`data/listings.jsonl`](data/listings.jsonl)) :

```json
{"title": "Studio cosy", "description": "...", "priceByNight": 89, "city": "Paris", "country": "France", "maxGuests": 2, "host": {"email": "claire@example.com", "firstName": "Claire", "lastName": "Martin"}, "amenities": ["Wi-Fi", "Sauna"]}
```

- Le fichier est lu ligne par ligne, chaque ligne est validée ; les lignes invalides sont listées sans arrêter l'import.
- Pas de doublons : hôte retrouvé par email, équipement par nom ou slug, et un logement déjà présent
  (même titre, ville et hôte) est ignoré. Relancer l'import ne crée donc rien de plus.
- Les hôtes créés reçoivent un mot de passe aléatoire : ils ne peuvent pas se connecter tant qu'il n'est pas réinitialisé.

## Tests et qualité du code

```bash
php bin/phpunit                      # 64 tests fonctionnels (HTTP) et d'intégration, base SQLite de test recréée à chaque test
vendor/bin/php-cs-fixer fix          # normes de code Symfony (@Symfony)
php bin/console lint:container       # vérifie la configuration des services
```

## Organisation du code

```
src/
├── Command/         Commande console app:import-listings
├── Controller/      Contrôleurs fins : une ressource par contrôleur (Listing, Review, User, Amenity, Health)
├── DataFixtures/    Données de test (Faker)
├── Doctrine/        Middleware activant les clés étrangères de SQLite
├── Entity/          Entités Doctrine (+ Trait/TimestampableTrait)
├── Enum/            BookingStatus
├── EventListener/   Ajoute ttl et user à la réponse du login
├── Model/           DTO des requêtes (ListingInput, ListingSearch, ReviewInput, PaginationDTO…) et Paginator
├── Repository/      Requêtes Doctrine (recherche, fils, statistiques d'avis)
├── Security/Voter/  Droits : ListingVoter (hôte, droit de noter), ReviewVoter (auteur)
├── Serializer/      ListingNormalizer : ajoute averageRating, reviewCount et myRating
└── Service/         Logique métier : ListingManager, ReviewManager, FollowManager, ListingImporter
tests/               Tests PHPUnit (Controller, Entity, Command)
api.http             Toutes les routes, prêtes à être exécutées
data/                Fichier d'exemple pour l'import
```
