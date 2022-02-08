# Remplacer les données d'un flux de tuiles vectorielles

!> Le Géotuileur vous permet de mettre à jour un flux de données en remplaçant l'intégralité de son contenu.

Cette partie met en évidence le principe de mise à jour d’un flux (ajout de nouvelles données, mise à jour des données d’une base, etc...). Pour cela, il faut se rendre sur le tableau de bord de votre espace de travail, et cliquer sur **Mettre à jour un flux**.

![Mettre à jour un flux](./img/replace-data/mettre-a-jour.png)

Choisissez dans la liste déroulante de vos flux, lequel vous souhaitez mettre à jour et laissez vous guider.

## Déposer de nouvelles données

Vous allez passer par les mêmes étapes que lors de la création du flux mais sans avoir à tout reparamétrer. Le Géotuileur par du principe que vous mettez à jour avec des données dans le même format et la même projection que lors de votre première publication et que vous voulez générer des tuiles vectorielles aux même échelles et selon les mêmes critères de sélection d'attributs ou de généralisation.

Une fois le fichier choisi, au clic sur **Etape suivante**, vos données sont successivement :

- téléversées
- vérifiées
- intégrées en base

Puis la génération de la pyramide de tuiles est lancée dans la foulée. Il vous est demandé de ne pas fermer la fenêtre de votre navigateur tant que vous n'êtes pas paravenu à cette dernière étape. Le message sur l'écran vous informe explicitement lorsque vous pouvez quitter et laisser la génération se terminer seule.

## Valider la mise à jour

Vous allez ensuite retrouver votre pyramide actualisée dans les **actions à terminer** sur votre tableau de bord.

Cliquez sur **Visualiser** pour obtenir une vue qui compare côte à côte votre flux de données publié et sa version actualisée.

Vous pouvez ainsi contrôler le nouveau flux avant de le valider et de lui faire écraser la version précédente.

## Fonctionnalités à venir

> Prochainement le Géotuileur permettra des mises à jour partielles d'un flux de tuiles vectorielles pour diffuser plus facilement des données très volumineuses ou produites à des rythmes différents selon les territoires.
