# Documentation développeur

## Installation et configuration

Le portail est construit sur le framework PHP Symfony. Il nécessite l'installation d'un moteur php >=7.1.3 avec les extensions `xsl`, `intl` et `sqlite` ainsi que le logiciel Yarn.

1. S'assurer que le proxy est correctement configuré (par exemple au moyen des variables d'environnement `http_proxy` et `https_proxy`)

2. Clôner le dépôt

3. Créer à la racine du projet un fichier nommé `.env.local` pour surcharger les informations du fichier `.env`. Se référer au fichier `.env` pour le compléter.

4. Installer les dépendances php (`composer install`)

5. Installer les dépendances Javascript (`yarn install`)

6. Compiler les assets (`yarn encore`)

### Spécificités de l'installation avec Docker

* Lancer les conteneurs docker : `docker-compose up -d --build --remove-orphans` ou `make up`
* Préfixe pour toutes les autres commandes : `docker exec -it web-client_backend_1 ...`

## Documentations diverses

* Plan du site Géotuileur : [sitemap.md](sitemap.md)

* Processus détaillé de création d'un flux de tuiles vectorielles [workflow.md](workflow.md)

* Consignes de rédaction de la documentation utilisateur : [docsify.md](docsify.md)

* Contenu et actions possibles depuis le tableau de bord d'un espace de travail : [dashboard.md](dashboard.md)

* Documentation détaillée de la généralisation : [generalization.md](generalization.md)

## Commandes utiles

Quelques commandes `make` ont été configurées comme raccourcis pour certaines tâches courantes :

* Voir la liste de commandes `make` disponibles : `make help`
* Voir le détail des commandes `make` dans le fichier `Makefile` à la racine du projet
* Compiler les dépendances PHP et JavaScript : `make compile-app` ou `make compile-app-prod` en production

Si vous modifiez des templates twig, vérifiez leur syntaxe avec :
```
php bin/console lint:twig templates/
```

Vous pouvez lister toutes les erreurs php avec la commande :

```
vendor/bin/phpstan analyse -c phpstan.neon
```
