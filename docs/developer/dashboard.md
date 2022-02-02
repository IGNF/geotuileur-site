# Contenu du tableau de bord d'un espace de travail

Le tableau de bord est divisé en 3 parties qui ne sont pas forcément toutes visibles simultanément :

* Actions à terminer
* Actions en cours
* Mes flux publiés

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

## Actions en cours

-   `stored_data` de type `VECTOR-DB` :
    -   intégration :
        -   en cours : **voir l'avancement**
-   `stored_data` de type `ROK4-PYRAMID-VECTOR` :
    -   génération de pyramide
        -   en cours : **voir l'avancement**
    -   mise à jour :
        -   en cours :**voir l'avancement**

## Mes flux publiés

-   `stored_data` de type `ROK4-PYRAMID-VECTOR` portant le tag `published` :
    -   actions possible : **visualiser** (et partager), mettre à jour les informations de publication, actualiser, gérer les styles, dépublier, supprimer