# Comment rédiger la documentation

La documentation exposée sur le site est uniquement la **documentation utilisateur** qui se trouve dans le dossier `/docs/user`.

Elle est rédigée en markdown et mise en forme par [docsify](https://docsify.js.org/).

Vous trouverez ici quelques consignes pour maintenir cette documentation utilisateur à jour.

## Structure et chapitrage de la documentation

Le contenu de la page d'accueil de la documentation se trouve dans le fichier `README.md`.

La table des matières se trouve dans le fichier `_sidebar.md`. Elle doit être tenue à jour si les fichiers vers lesquels elle pointe changent de nom, sont supprimés ou si de nouveaux fichiers sont ajoutés.

La documentation est découpée en petits fichiers `.md` qui doivent tous commencer par un titre de niveau 1 (commençant par un seul `#`) portant le même intitulé que le lien qui pointe vers le fichier dans la table des matières (ce n'est pas une obligation technique mais c'est moins confus pour l'utilisateur).

```md
# Titre
```

Au cas où un des liens internes à la documentation soit cassé, le fichier `_404.md` décrit le contenu d'une page d'erreur.
    
Il est recommandé de ne pas numéroter les titres, si l'ordre et donc le numéro change, alors l'ancre va changer aussi et les éventuels liens qui auront été créés vers cette ancre seront cassés. Pour la même raison il est recommandé d'éviter les titres à rallonge, plus susceptibles d'être modifiés au cours de la vie de la documentation.

Pour garantir qu'une ancre (id) d'un titre n'évolue pas même si on le modifie, il est possible de fixer explicitement cette ancre :

```md
# Mon super long titre à rallonge :id=super-titre
```

On pointera ainsi vers cette ancre avec le lien `chemin/fichier?id=super-titre` au lieu de `chemin/fichier?id=mon-super-long-titre-à-rallonge`

Evitez de définir plus de 3 niveaux de titre (maximum `### Titre de niveau 3`).

## Liens

Les liens vers des documents qui font sortir du contexte de l'aide en ligne doivent s'ouvrir dans un nouvel onglet (par exemple les liens vers des fichiers pdf).

```md
[Nom du fichier](docs/pdf/fichier.pdf ':target=_blank :ignore')
```

NB : pour les liens externes, c'est le cas par défaut. Il n'est pas utile de préciser la cible.

## Les images

Privilégiez des images dans des résolutions raisonnables pour limiter leur poids et compatibles avec leur taille d'affichage (960px de large est un maximum).

Placez toutes les images dans le seul dossier `img`, nommez les images de façon explicite et évitez espaces, caractères spéciaux et majuscules dans les noms. Lorsque vous supprimez ou renommez une image, veillez à supprimer ou modifier son utilisation dans les fichiers .md.

Mettez un texte alternatif pour les images. C'est une bonne pratique d'accessibilité et si l'image ne s'affiche pas correctement, ça permet à l'utilisateur de savoir qu'il devrait y avoir une image à cet endroit et ce qu'elle est censée lui montrer. Ce texte alternatif se place entre les crochets et optionnellement vous pouvez définir un titre visible au survol avec la souris :

```md
![Texte alternatif de l'image](../img/echantillon.jpg 'Title de l\'image (visible au survol avec la souris)')
```

## Texte enrichi

Ne vous limitez pas aux paragraphes monolithiques et aux listes. Il est possible de mettre du texte en gras (`**texte**` ou `__texte__`), en italique (`*texte*` ou `_texte_`), barré (`~~texte~~`), de faire des listes à puces ou numérotées, des cases cochées façon checklist, d'utiliser des [emojis](https://github.com/ikatyang/emoji-cheat-sheet/blob/master/README.md)...

[Documentation de référence](https://docs.github.com/en/github/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax)