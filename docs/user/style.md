# Gérer les styles

Par défaut, votre flux est représenté sur les cartes en filaire de couleur bleue.

Pour personnaliser ce rendu, vous devez associer un fichier de style à votre flux. Ce fichier doit décrire les couleurs, tailles de tracés, opacités... à appliquer à chacun des objets de votre flux et selon quels critères attributaires.

!> Le Géotuileur ne permet aujourd'hui d'utiliser que des fichiers de style **au format Mapbox JSON**. Il vous faut préparer ces fichiers avec un autre logiciel

## Préparer un fichier de style

?> Documentation à venir 
## Importer un fichier de style

Sur la droite de la page de partage de votre flux, vous pouvez passer à la page de gestion des styles qui lui sont associés en cliquant sur **Gérer les styles**.

![Gérer les styles](./img/style/styles.png)

Cliquez ensuite sur **Importer un style** et choisissez un fichier au format Mapbox JSON.

Nommez ce nouveau style pour le retrouver plus facilement dans la liste des styles.

![Nommer un style](./img/style/nommer-style.png)

Si le fichier de style est adapté à vos données, l'affichage de la carte se met à jour et vous devriez voir apparaitre vos données selon les représentations décrites dans le fichier de style.

!> Attention : si votre fichier de style n'est pas adapté à vos données, vous les verrez disparaitre (partiellement ou totalement) de la carte sans que ceci ne soit considéré comme une erreur. Si aucun filtre de votre fichier de style ne correspond à un objet donné, celui-ci n'est pas affiché. _Par exemple, si votre fichier de style prévoit l'affichage d'objets portant un champ nature avec pour valeur "commercial" mais qu'aucun objet ne porte ce champ avec cette valeur, votre fichier de style ne permettra pas de représenter vos données._
## Modifier ou supprimer un fichier de style

Vous pouvez supprimer un style définitivement en cliquant sur la poubelle à côté de son nom.

Vous ne pouvez pas modifier un style existant. Vous devez reprendre le fichier de style à l'aide du logiciel de votre choix et le réimporter.

## Fonctionnalités à venir

> Des travaux sont en cours pour permettre :
>  * d'importer des fichiers de style dans d'autres formats 
>  * de manipuler les styles directement dans l'interface graphique.