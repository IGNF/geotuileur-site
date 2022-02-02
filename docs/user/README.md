# Qu'est-ce qu'un flux de tuiles vectorielles ?

Le Géotuileur vous permet de générer des **flux de tuiles vectorielles** à partir de **données vecteur**.

Un flux, ou service, de tuiles vectorielles met à disposition des utilisateurs des objets vecteurs selon un prédécoupage géographique régulier : des **tuiles**. Il s'agit d'un service standardisé par l'OGC (Open Geospatial Consortium) sous le nom TMS ([Tile Map Service](https://wiki.osgeo.org/wiki/Tile_Map_Service_Specification)).

Le découpage géographique en tuiles est **multi-échelle**. Les tuiles sont mises à disposition jusqu'à une vingtaine d'échelles différentes, on parle alors de **niveaux**.

Au niveau zéro, la Terre entière est représentée sur une seule tuile. Au niveau n+1, il y a 4 fois plus de tuiles qu'au niveau n (Chaque tuile du niveau n est découpée en 4 au niveau n+1). L'ensemble des tuiles vectorielles pour une donnée correspond à une **pyramide** de tuiles vectorielles.

Vous trouverez une description plus complète du principe sur le site [geoservices.ign.fr](https://geoservices.ign.fr/documentation/services/api-et-services-ogc/vecteur-tuile-tmswmts).

**Avantages**

* **Rapidité d’accès à la donnée :** les tuiles vectorielles sont récupérées par les applications clientes sans que cela n’implique de calcul côté serveur car les tuiles sont complètement précalculée, ce qui rend l'accès au donné plus rapide qu'au travers d'un flux WFS. L'étape de génération des tuiles par contre peut prendre du temps, mais une seule fois.

* **Facilité de modification du style :** les données étant côté client au format vecteur, le client peut appliquer le style de son choix sans nouvelle interaction avec le serveur, sans recharger les données.

* **Interaction directe des utilisateurs avec les objets :** l'utilisateur peut accéder aux attributs des objets au clic ou au survol. Le style peut être modifié au survol ou à la sélection... L'application cliente a une plus grande liberté d'interaction que si les données étaient en format image.

* **Un rendu plus esthétique** : les moteurs de rendu de données vecteurs donnent des visualisations plus lisses sans effet de pixélisation à n'importe quelle échelle.

**Inconvénients**

Certains des avantages des tuiles vectorielles peuvent être considérés comme des inconvénients pour certains utilisateurs :

* l'application cliente doit supporter le dessin vectoriel : ce n'est pas un problème avec les appareils récents, mais cela peut en être un avec les plus anciens.

* l'utilisation des flux nécessite davantage de configuration côté application cliente pour symboliser la donnée et gérer les interactions. Cela signifie plus de configuration dans un SIG ou plus de développement dans une application web.
