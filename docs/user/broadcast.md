# Diffusez votre flux

Une fois votre flux publié, vous êtes redirigé sur une page de partage qui liste les différents liens permettant de diffuser votre carte ou votre flux de tuiles vectorielles à vos différentes communautés d'utilisateurs.

?> :+1: Toutes les adresses mentionnées sur cette page sont accessibles sans authentification et peuvent donc être partagées à des utilisateurs non-inscrits sur le Géotuileur.

!> A ce niveau, aucun style n'est associé à votre flux. Les données apparaissent donc en bleu sous forme filaire. Ceci peut suffire à certains de vos utilisateurs mais si vous avez besoin d'une symbolisation différente, procédez à la [personnalisation](./style.md) de vos données avant de les partager.
## Comment partager une carte ?

Par **carte** on entend une représentation graphique et navigable des données, consultable sans connaissance technique particulière. Cette carte propose votre flux en superposition de la couche **Plan IGN**. Ceci n'est pas modifiable pour le moment.

### Partagez le lien public vers la carte

Il vous suffit de copier-coller le lien public vers la carte et le partager sans le modifier.

À noter : un utilisateur qui ouvre ce lien est positionné au centre de l'étendue de vos données. Si ce centrage par défaut ne vous convient pas, il est conseillé d'ouvrir ce lien vous-même et de vous déplacer sur la carte jusqu'à trouver un positionnement satisfaisant. Copiez-collez ensuite le contenu de la barre d'adresse du navigateur : il s'agira toujours du lien public vers la carte, cette fois suivi d'une position et d'une échelle sous la forme `#map={z}/{lat}/{lon}` (par exemple `#map=15/49.7732/4.7127`). C'est ce lien complet qu'il vous faut envoyer à vos utilisateurs.

### Intégrez la carte sur votre site web

Le lien public (avec ou sans précision de positionnement et d'échelle) est intégrable dans n'importe quel site web dans une iframe dont vous pouvez fixer les dimensions (dans cet exemple : 600 par 400 pixels) :

```html
<iframe width="600" height="400" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"
        sandbox="allow-forms allow-scripts allow-same-origin" src="{url de la carte}">
</iframe>
```

## Comment partager les informations techniques du flux ?

Les utilisateurs avancés peuvent partager l'adresse (URL) du flux TMS directement ainsi que la ou les URL des fichiers de styles associée(s). Ils utiliseront ces informations avec un SIG qui comprend ce type d'information ou dans un développement.

Exemple d'utilisation avec OpenLayers :

[Affichage d'un flux de tuiles vectorielles avec OpenLayers](//jsfiddle.net/pprevautel/wsqu7e90/209/embedded/ ':include :type=iframe width=100% height=300px')

## Où diffuser le flux ?

Les outils de partage sont limités à la copie de lien dans le presse-papier. Vous êtes ensuite libre de partager les différentes URL par mail, messagerie instantanée, réseaux sociaux ou publications sur des sites institutionnels.

## Comment utiliser le flux de tuiles vectorielles dans différents outils ?

### Dans QGIS :

[Voir notre tutoriel](tutos/vectortiles-in-qgis.md)

### Dans le Géoportail

[Voir notre tutoriel](tutos/vectortiles-in-geoportail.md)

### Dans ArcGis :

La suite de logiciels ArcGis ne permet pas pour l'instant d'intégrer un flux de tuiles vectorielles via une URL externe aux serveurs ESRI. 

### Dans Mapbox :

L'outil en ligne Mapbox ne permet pas non plus d'intégrer un flux de tuiles vectorielles via une URL. Pour intégrer votre style personnalisé dans Mapbox, il faut enregistrer son fichier GeoJSON et se rendre dans **Add new layer** puis **Upload data**. Dans **Select a file**, sélectionnez le fichier GeoJSON que vous voulez insérer dans Mapbox.


