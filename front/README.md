# escale — front-end React

Interface web du clone d'Airbnb, qui consomme l'API Symfony du dossier parent.
React 18 + TypeScript + Vite + React Router, icônes `lucide-react`, CSS sans framework.

## Lancer

L'API doit tourner sur `http://127.0.0.1:8000` (voir le [README du projet](../README.md)).

```bash
cd front
npm install
npm run dev        # http://127.0.0.1:5173
```

Vite redirige les appels `/api/*` vers l'API Symfony (`vite.config.ts`) : aucune configuration CORS n'est nécessaire.

`npm run build` vérifie les types (TypeScript) et produit la version de production dans `dist/`.

## Pages

| URL | Contenu | API utilisée |
| --- | --- | --- |
| `/` | Recherche (ville, dates, voyageurs), catégories d'équipements, filtres (tri, prix max, mot-clé), grille paginée « Afficher plus » | `GET /api/listings`, `GET /api/amenities` |
| `/listings/:id` | Galerie, hôte (bouton Suivre), équipements, carte de réservation, avis (publier, modifier, supprimer) ; pour l'hôte : modifier / supprimer, confirmer ou annuler les réservations | `GET /api/listings/{id}`, `/bookings`, `/reviews`, `/users/{id}/follow` |
| `/trips` | Mes voyages : statut de chaque réservation, annulation avant l'arrivée | `GET /api/bookings`, `POST /api/bookings/{id}/cancel` |
| `/host/listings/new`, `/host/listings/:id/edit` | Créer / modifier un logement (erreurs affichées sous chaque champ) | `POST` / `PUT /api/listings` |
| `/feed` | Fil des avis des personnes suivies, ou de tout le monde | `GET /api/feed`, `GET /api/reviews` |
| `/account` | Profil, abonnements et abonnés | `GET /api/user`, `/users/{id}/following`, `/followers` |

Connexion : menu en haut à droite. Les boutons « Hôte » et « Voyageur » de la fenêtre de connexion
remplissent les comptes de démonstration des fixtures (mot de passe `password`).
Le token JWT est gardé dans le `localStorage` ; à son expiration (1 h), l'utilisateur est déconnecté.

## Limites connues

- **Photos** : l'API ne stocke pas d'images ; chaque logement reçoit des photos fixes tirées d'une liste
  de photos libres Unsplash (`src/utils/images.ts`).
- **Favoris** (cœur sur les cartes) : enregistrés dans le navigateur uniquement.

## Organisation

```
src/
├── api/          client.ts (fetch + JWT + erreurs typées), types.ts (formes du JSON de l'API)
├── auth/         AuthContext : utilisateur connecté, connexion, déconnexion
├── components/   En-tête, recherche, catégories, cartes, modales, avis, carte de prix…
├── pages/        Une page par route
├── utils/        Formatage (prix, dates), photos, favoris
├── brand.ts      Nom du site (à changer ici)
└── styles.css    Tout le style (variables de couleurs en haut du fichier)
```
