# Style pour le rendu cartographique des tuiles vectorielles

Le format privilégié pour appliquer un style à un flux de tuiles vectorielles est le JSON conforme aux [spécifications de style Mapbox GL](https://docs.mapbox.com/mapbox-gl-js/style-spec/), parfois appelé **MbStyle**.

C'est systématiquement un fichier dans ce format qui est téléversé en tant qu'annexe pour être rendu disponible aux utilisateurs finaux du flux.

Il est possible de soumettre des fichiers de style dans 3 formats. Voici les manipulations effectuées par le Géotuileur pour chacun d'entre-eux :

## MbStyle

Si l'utilisateur soumet un fichier MbStyle, avant de l'envoyer à l'API, le Géotuileur va s'assurer que chaque `source-layer` des `layers` qui le composent existe bien dans les tuiles vectorielles et compléter la partie `sources` avec l'URL du flux.

## QML

Si l'utilisateur dispose de fichiers QML, il doit en envoyer un par couche présente dans les tuiles vectorielles. Ils sont ensuite convertis en MbStyle avec Geostyler (notamment [geostyler-qgis-parser](https://github.com/geostyler/geostyler-qgis-parser/) et [geostyler-mapbox-parser](https://github.com/geostyler/geostyler-mapbox-parser/)) et combinés en un seul fichier avec ajout de la section `source`.

## SLD

Le processus appliqué est identique à ce qui est fait dans le cas de fichiers QML, en utilisant cette fois [geostyler-sld-parser](https://github.com/geostyler/geostyler-sld-parser/).

----

> NB : l'affichage cartographique sur le site est réalisé avec openlayers et [ol-mapbox-style](https://github.com/openlayers/ol-mapbox-style). Toutes les possibilités du format MbStyle ne sont pas pleinement exploitées par cet affichage. Une bibliothèque plus nativement dédiée au format MbStyle comme [MapLibre](https://github.com/maplibre/maplibre-gl-js) pourrait dans ce cas être une meilleure option pour visualiser le flux et son style.
