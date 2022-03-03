# Généralisation cartographique des données diffusées sous forme de tuiles vectorielles

## Objectif

La généralisation a pour objectif de proposer aux utilisateurs du Géotuileur une représentation de leurs données fidèle et faisant sens aux petites et moyennes échelles tout en conservant des performances d'affichage acceptables.

L'enjeu de performance est important dans la mesure où les tuiles vectorielles sont une alternative au WFS (Web Feature Service).


## Définitions

La **généralisation** consiste en une synthèse de l'information, comparable à un résumé de texte. Il s'agit de :

* Réduire la quantité d'information
* Mettre en valeur l'information la plus importante
* Rester fidèle à l'information intitiale
* Respecter une bonne lecture de l'information

Ceci peut être réalisé par des opérations de **sélection**, **schématisation** ou **harmonisation**.

* **Sélection qualitative** : on ne retient que les éléments les plus importants (pour se repérer, pour décrire un phénomène)
* **Sélection quantitative** : on ne garde que les éléments les plus importants en terme de taille d'objet ou de classe
* **Schématisation structurale** : on élimine les détails nuisibles, on réunit les objets semblables trop petits, on accentue les détails remarquables
* **Schématisation conceptuelle** : on change le mode de représation graphique (par exemple une surface devient un point, une surface devient une ligne-réseau en hydrographie, un groupe de point devient une surface)
* **Harmonisation** : on maintien les relations entre les classes d'objets.


## Contexte technique

La génération des pyramides de tuiles vectoriellesest effectuée par un outil intégré à la suite Rok4generation du projet [ROK4](https://github.com/rok4/rok4). Il s'appuie principalement sur le logiciel [Tippecanoe](https://github.com/mapbox/tippecanoe) de Mapbox qui a naturellement imposé son format de données pour les tuiles vectorielles.


## Les paramètres de Tippecanoe

Tippecanoe possède un certain nombre d'options de généralisation automatique. Nous avons cherché à proposer sur le Géotuileur les options de généralisation les plus pertinentes en fonction de 2 critères :

* Le poids des tuiles générées pour conserver des performances acceptables (La limite par défaut de Tippecanoe est de 500ko).
* Visuellement la donnée conserve son sens.

Tippecanoe travaille tuile par tuile et non à l'échelle du jeu de données complet.

Concrètement les opérations de généralisation se traduisent par les principaux paramètres suivants :

* `-aL` ou `--grid-low-zooms` : étend les polygones à une grille (selon la résolution `-D8`) ou les supprime
* `-ab` ou `--detect-shared-borders` : simplifie les formes de manière à conserver les limites partagées entre deux objets 
* `-an` ou `--drop-smallest-as-needed` : supprime les plus petits éléments pour rester dans la limite des 500k/tuile
* `-aD` ou `--coalesce-densest-as-needed` : regroupe les éléments les plus représentés (densité dans la tuile) pour rester sous la limite des 500k/tuile
* `-ac` ou `--coalesce` : regroupe les éléments de mêmes attributs
* `-S` ou `--simplification` : simplifie les formes (ligne ou polygone) selon un facteur
* `-pn` ou `--no-simplification-of-shared-nodes` : conserve les nœuds partagés entre plusieurs objets d'un réseau

L'ensemble des paramètres disponibles est consultable dans la [documentation de référence de Tippecanoe](https://github.com/mapbox/tippecanoe/blob/master/README.md).

Possibilités de généralisation par type de données :

| Type de données | Possibilités de généralisation |
| --- | --- |
| Points |  Eviter la superposition de ponctuels |
| Lignes d'un réseau | Garder la continuité - Simplifier les tracés - Conserver la topologie |
| Lignes limites | Simplifier les contours en conservant les partages de primitives |
| Surfaces type occupation du sol | Simplifier les contours en conservant les partages de primitive - Ne pas créer de trous / bufferiser - Supprimer les petites surfaces - Agréger les surfaces de mêmes attributs |
| Surfaces type bâti | Agréger les surfaces de mêmes attributs - Supprimer les petites surfaces - Bufferiser |


## Les paramètres proposés par le Géotuileur

Le Géotuileur propose des combinaisons de paramètres figées, non panachables. Les illustrations proposées dans l'interface exagèrent volontairement les effets de la généralisation pour mieux les saisir.

Concrètement, à l'écran, ces effets pourraient être amoindris dans le cas d'un traitement sur des données légères (moins d'attributs ou peu de géométries) et donc davantage respectueux des formes originelles.

Certains paramètres sont plus efficaces si les données ont peut d'attributs. Il est donc recommandé de ne sélectionner que les attributs nécessaires à l'utilisateur final. C'est pour cette raison que l'interface du Géotuileur ne propose pas de bouton "cocher tout" lorsque l'utilisateur doit choisir quels attributs conserver dans sa pyramide.

| Nom | Combinaison de paramètres Tippecanoe | Explication détaillée |
| --- | --- | --- |
| Simplification de formes hétérogènes | `-S10` |  |
| Simplification de réseau | `-pn -S15` | On simplifie en conservant les nœuds du réseau |
| Simplification de données linéaires | `-an -S15` | On simplifie en supprimant les petits objets |
| Schématisation de données surfaciques | `-aL -D8 -S15` | On schématise en conservant la couverture de la zone |
| Sélection de données surfaciques | `-ac -aD -an -S15` | On sélectionne les données les plus représentatives en supprimant les plus petites. Ce paramètre est plus pertinent si on choisit de conserver moins de 3 attributs |
| Fusion attributaire de données surfaciques | `-ac -an -S10` | On fusionne les objets qui ont les mêmes valeurs d'attribut, en simplifiant les formes et en supprimant les petites surfaces. Ce paramètre est plus pertinent si on choisit de conservers moins de 3 attributs. |
| Harmonisation de données surfaciques | `-ab -S20` | On simplifie les formes en conservant les limites partagées entre 2 surfaces |