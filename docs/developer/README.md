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

-   Lancer les conteneurs docker : `docker-compose up -d --build --remove-orphans` ou `make up`
-   Préfixe pour toutes les autres commandes : `docker exec -it web-client_backend_1 ...`

## Tests avec Cypress

### Prérequis

Activer l'environnement de test :

```ini
# .env.local
APP_ENV=test
```

```sh
php bin/console cache:clear
```

Configurer le `baseUrl` dans [cypress.config.js](../../cypress.config.js) specifique à votre installation :

```js
baseUrl: "http://localhost:8080",
```

Lancer si première utilisation de cypress :

```sh
yarn cypress install
```

> Configuration supplémentaire pour Linux sous WSL2 : https://nickymeuleman.netlify.app/blog/gui-on-wsl2-cypress

### Lancement des tests

Ouvrir l'interface de pilotage de cypress :

```sh
yarn cypress open
```

Lancer les tests (mode `headless`, sans visuel) :

```sh
# le mode headless (--headless) est activé par défaut
yarn cypress run --browser firefox
```

Lancer les tests (mode `headed`, avec visuel) :

```sh
yarn cypress run --browser firefox --headed
```

> Le navigateur sur lequel on souhaite exécuter les tests doit être installé sur votre machine.
>
> Liste de navigateurs supportés : https://docs.cypress.io/guides/guides/launching-browsers#Browsers

### Astuces

Pour le bon fonctionnement de l'intellisense de votre IDE pour cypress, configurer le fichier `jsconfig.json` ainsi :

```json
"include": ["./node_modules/cypress", "cypress/**/*.js"]
```

> En savoir plus : https://docs.cypress.io/guides/tooling/IDE-integration#Writing-Tests

Structure des tests :

```js
// cypress/e2e/example.cy.js

describe("Description d'une suite de tests", () => {
    context("Un ensemble de tests qui sont regroupés par un contexte particulier (par ex. utilisateur connecté ou non) (optionnel)", () => {
        it("(it ou specify) un scenario représenté par une suite de tâches et vérification d'un comportement attendu (quelque chose qui se passe quand l'utilisateur effectue une action)", () => {
            ...
        })
    })
})
```
Voir les exemples dans [/cypress/e2e](../../cypress/e2e/)

> En savoir plus : https://docs.cypress.io/guides/core-concepts/writing-and-organizing-tests#Test-Structure

## Documentations diverses

-   Plan du site Géotuileur : [sitemap.md](sitemap.md)

-   Processus détaillé de création d'un flux de tuiles vectorielles [workflow.md](workflow.md)

-   Consignes de rédaction de la documentation utilisateur : [docsify.md](docsify.md)

-   Contenu et actions possibles depuis le tableau de bord d'un espace de travail : [dashboard.md](dashboard.md)

-   Documentation détaillée de la généralisation : [generalization.md](generalization.md)

## Commandes utiles

Quelques commandes `make` ont été configurées comme raccourcis pour certaines tâches courantes :

-   Voir la liste de commandes `make` disponibles : `make help`
-   Voir le détail des commandes `make` dans le fichier `Makefile` à la racine du projet
-   Compiler les dépendances PHP et JavaScript : `make compile-app` ou `make compile-app-prod` en production

Si vous modifiez des templates twig, vérifiez leur syntaxe avec :

```
php bin/console lint:twig templates/
```

Vous pouvez lister toutes les erreurs php avec la commande :

```
vendor/bin/phpstan analyse -c phpstan.neon
```
