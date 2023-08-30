# Géotuileur

[![License: AGPL-3.0](https://img.shields.io/badge/License-AGPL--3.0-blue.svg)](LICENSE)

Ce projet constitue un portail web pour l'API entrepôt, dédié à la publication de données sous forme de pyramides de tuiles vectorielles.

Ce dépôt ne contient que le code source du portail et ne contient pas le code de l'API entrepôt.

Ce code est sous licence Affero GPL v3.0.

## ❗ Note importante

**Le Géotuileur a été déployé et testé dans le cadre d'une expérimentation entre l'été 2022 et l'été 2023. L'expérimentation est terminée et les fonctionnalités sont ou seront reprises dans d'autres projets. Ce dépôt est par conséquent archivé et les issues ou PR encore référencées ne seront plus traitées.**

## Fonctionnalités clés

-   Accès aux Espaces de travail (`datastores`) de l'utilisateur
-   Livraison de données vecteur (`uploads`) et intégration en base de données (`stored_data` de type `VECTOR-DB`)
-   Génération de pyramide de tuiles vectorielles (`stored_data` de type `ROK4-PYRAMID-VECTOR`) avec possibilité de générer des échantillons (pyramide sur une emprise géographique limitée plutôt que sur toute l'étendue des données)
-   Choix de plusieurs paramétrages de généralisation préconfigurés (l'API utilise le logiciel [Tippecanoe](https://github.com/mapbox/tippecanoe) pour effectuer la génération des tuiles)
-   Publication de pyramides de tuiles vectorielles sous forme de flux TMS (`configurations` et `offerings`) avec aide au partage de ces flux
-   Personnalisation du rendu des flux avec un système de gestion de styles (rendus publics via le système d'`annexes` de l'API)
-   Visualisation des travaux en cours et des flux publiés et gestion de l'espace de stockage.

## Utilisation

La documentation utilisateur se trouvant dans [docs/user](docs/user) constitue la rubrique d'aide en ligne. Elle est déployée avec le site et mise en forme avec [docsify](https://github.com/docsifyjs/docsify).

## Documentation développeur

Voir la [documentation développeur](docs/developer/README.md).

## Déploiement

Le Géotuileur est déployé à l'adresse : [https://www.ign.fr/geoplateforme/geotuileur/](https://www.ign.fr/geoplateforme/geotuileur/)

## Logiciels associés

Les fonctionnalités du Géotuileur ont été intégrées dans un plugin QGIS : [Getotuileur - QGIS Python Plugins Repository](https://plugins.qgis.org/plugins/geotuileur/)

Le code source de ce plugin, développé par Oslandia, est disponible sur [gitlab.com/Oslandia/qgis/ign-geotuileur](https://gitlab.com/Oslandia/qgis/ign-geotuileur).
