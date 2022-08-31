# Remplacer les données d'un flux de tuiles vectorielles



Pour mettre à jour un flux (ajout de nouvelles données, mise à jour des données d’une base, etc...), rendez-vous sur le tableau de bord de votre espace de travail, et cliquez sur **Mettre à jour un flux** :

![Mettre à jour un flux](./img/replace-data/mettre-a-jour.png)

Puis le flux à mettre à jour dans la liste déroulante et laissez-vous guider.

!> Le Géotuileur vous permet de mettre à jour un flux de données en remplaçant l'intégralité de son contenu.

## Déposer de nouvelles données

Vous allez suivre les mêmes étapes que lors de la création du flux mais sans avoir à tout reparamétrer. Le Géotuileur part du principe que vous mettez à jour avec des données dans le même format et la même projection que lors de votre première publication et que vous voulez générer des tuiles vectorielles aux mêmes échelles et selon les mêmes critères de sélection d'attributs ou de généralisation.

!> Attention, il est très important pour que la mise à jour se déroule sans encombre, que les données aient la même structure : même noms de tables et de champs, que les données qui ont servi à la première génération.

Une fois le fichier choisi, au clic sur **Etape suivante**, vos données sont successivement :

- téléversées,
- vérifiées,
- intégrées en base.

Puis la génération de la pyramide de tuiles est lancée dans la foulée. Il vous est demandé de ne pas fermer la fenêtre de votre navigateur tant que vous n'êtes pas parvenu à cette dernière étape. Un message vous informe lorsque vous pouvez quitter et laisser la génération se terminer seule.

## Valider la mise à jour

Vous allez ensuite retrouver votre pyramide actualisée dans les **actions à terminer** sur votre tableau de bord.

Cliquez sur **Visualiser** pour obtenir une vue comparative de votre flux de données publié et sa version actualisée.

Vous pouvez ainsi contrôler le nouveau flux avant de le valider et d'écraser la version précédente.

## Fonctionnalités à venir

> Prochainement le Géotuileur permettra des mises à jour partielles d'un flux de tuiles vectorielles pour diffuser plus facilement des données très volumineuses ou produites à des rythmes différents selon les territoires.
