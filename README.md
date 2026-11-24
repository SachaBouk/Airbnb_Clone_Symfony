# Airbnb Clone — API Symfony

Clone de l'API d'Airbnb réalisé avec Symfony 8.1 (TP PHP 2026).

## Stack

- PHP 8.4, Symfony 8.1
- Doctrine ORM + SQLite (fichier `var/data_dev.db`, aucun serveur à lancer)
- Validator, Serializer, Security, CORS (nelmio)
- Dev : MakerBundle, DoctrineFixtures, Faker

## Installation

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start -d            # API sur http://127.0.0.1:8000
```

Tester : `GET /api/health` (voir `api.http`).

## Commandes utiles

| But | Commande |
| --- | --- |
| Créer/modifier une entité | `php bin/console make:entity` |
| Générer une migration | `php bin/console make:migration` |
| Appliquer les migrations | `php bin/console doctrine:migrations:migrate` |
| Créer un contrôleur | `php bin/console make:controller` |
| Repartir d'une base vide | `rm var/data_dev.db && php bin/console doctrine:migrations:migrate` |
| Charger les fausses données | `php bin/console doctrine:fixtures:load` |
| Lister les routes | `php bin/console debug:router` |
