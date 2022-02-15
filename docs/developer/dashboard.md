# Contenu du tableau de bord d'un espace de travail

Le tableau de bord est divisé en 3 parties qui ne sont pas forcément toutes visibles simultanément :

* Actions à terminer
* Traitements en cours
* Mes flux publiés

Dans les 3 parties, chaque item correspond à un objet `stored_data` de l'API et c'est son type, son statut ou les tags qui lui sont associés qui vont déterminer les actions possibles à partir de cette donnée.

## Actions à terminer

-   `stored_data` de type `VECTOR-DB` :
    -   intégration :
        -   terminée avec succès : **Générer**
        -   terminée en échec : **Voir le rapport**
-   `stored_data` de type `ROK4-PYRAMID-VECTOR` :
    -   génération de pyramide :
        -   terminée avec succès : **Publier**
        -   terminée en échec : **voir le rapport**
    -   mise à jour :
        -   terminée avec succès : **Vérifier** (comparer avec la version précédente)
        -   terminée en échec : **Voir le rapport**
    -   échantillon :
        -   terminé avec succès : **Visualiser** (et valider)

La suppression de la donnée est toujours possible comme action secondaire dans cette partie.

## Traitements en cours

-   `stored_data` de type `VECTOR-DB` :
    -   intégration :
        -   en cours : **voir l'avancement**
-   `stored_data` de type `ROK4-PYRAMID-VECTOR` :
    -   génération de pyramide
        -   en cours : **voir l'avancement**
    -   mise à jour :
        -   en cours :**voir l'avancement**

Voir l'avancement est la même action que Voir le rapport dans la partie actions à terminer. Si le lien n'est pas toujours proposé, cette action est néanmoins possible pour toutes les `stored_data` (voir la route `/report` dans [sitemap.md](sitemap.md)).

Aucune action secondaire n'est possible dans cette partie.
## Mes flux publiés

- `stored_data` de type `ROK4-PYRAMID-VECTOR` portant le tag `published`. Actions possibles :
  - **visualiser** (action principale) : Consulter le flux et accéder aux URL de partage
  - Remplacer les données : Démarrer un processus de mise à jour complète des données
  - Gérer les styles : Ajouter ou supprimer des fichiers de style
  - Mettre à jour les informations de publication : Actualiser la publication en modifiant ses métadonnées (nom, titre, informations d'attribution, mots clés)
  - Dépublier : Retire la pyramide de mes flux en la conservant dans mes données, elle se retrouvera alors à nouveau dans les actions à terminer
  - Supprimer : Dépublie et supprime définitivement la pyramide de tuiles vectorielles