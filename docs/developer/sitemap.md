# Plan du site

Ce plan liste les routes visibles de l'utilisateur (c'est-à-dire les pages réelles du site Géotuileur).

## Pages accessibles sans authentification

- `/` : Page d'accueil
- `/mentions-legales` : Mentions légales
- `/accessibilite` : Accessibilité, état de conformité au RGAA
- `/cgu` : Conditions générales d'utilisation
- `/gestion-des-cookies` : Gestion des cookies
- `/nous-ecrire` : Formulaire de contact
    - `/merci` : Page de réussite suite à l'utilisation du formulaire de contact
- `/doc` : Aide en ligne (expose le contenu du dossier `/docs/user` avec docsify. [Voir le principe](./docsify.md))
- `/viewer` : Visualiseur cartographique pour partager les flux de tuiles vectorielles

## Compte utilisateur

-   `/mon-compte` : Informations personnelles de l'utilisateur connecté. Les informations sont en lecture seule.

## Espaces de travail

-   `/datastores`  

    -   `/` : La liste des espaces de travail auxquels l'utilisateur connecté a accès. Dans la documentation de l'API le mot "entrepôt" est utilisé en lieu et place d'espace de travail. En réalité un espace de travail sur le Géotuileur est un entrepôt + la communauté à laquelle il est associé.
    
    - `/create-sandbox` : Créer un espace de travail de test (route appelée une seule fois lors de la première tentative d'accès à un espace de test s'il n'y en a pas encore pour l'utilisateur. Tout nouvel appel est redirigé vers l'espace de test existant de l'utilisateur). Un espace de test offre les mêmes possibilités que n'importe quel espace de travail mais avec des quotas limités.   

    - `/{datastoreId}` : Racine pour un espace de travail

        - `/` : Tableau de bord : vue synthétique des actions à terminer, actions en cours et flux publiés dans l'espace de travail mentionné dans l'URL (l'espace de travail peut-être un espace de test ou un espace de travail de production sans distinction)
        - `/members` : Liste les utilisateurs membres d'un espace de travail
            - Si l'utilisateur a les `community_rights`, alors il peut ajouter un utilisateur (connaissant son uuid) ou en supprimer un sauf le `supervisor` de la `community` ou lui-même.
        - `/manage-storage` : Consultation du détail de tous les éléments contenus dans les différents systèmes de stockages de l'espace de travail (fichiers, bases de données...).
        - `/uploads`
            - `/add` : Charger des données (le point de départ pour créer un flux)
            - `/{uploadId}` :
                - `/` : Détail d'une livraison
                - `/integration` : Résultats de tout le déroulé du processus depuis l'envoi de fichiers à l'API jusqu'à la création d'un stored_data VECTOR-DB via le traitement d'intégration
                - `/delete` : Supprime une livraison

        - `/stored_data`
            - `{storedDataId}/report` : rapport complet sur la création de cette donnée avec étapes intermédiaires (cette route est valide pour toutes les `stored_data`, aussi bien pour les bases de données vecteur que pour les pyramides de tuiles vectorielles même si elle n'est pas toujours proposée dans l'interface)

        - `/pyramid`
            - `/add?vectordbId=#` : Créer une pyramide de tuiles vectorielles à partir d'une stored_data VECTOR-DB
            - `/{pyramidId}` :
                - `/` : Résultats de la génération d'un flux
                - `/publish` : Publier un flux à partir d'une stored_data ROK4-PYRAMID-VECTOR sur un géoservice
                - `/share` : Visualiser et partager le flux de tuiles vectorielles
                - `/styles` : Gérer les styles associés à la pyramide
                  - `/add` : Ajoute un fichier de style
                  - `/change-default` : modifie le style affecté par défaut à une pyramide
                  - `/remove` : supprime un fichier de style
                  - `/download` : télécharge un fichier de style
                - `/update` : Mettre à jour la pyramide (démarrage d'une nouvelle création de flux destiné à remplacer l'existant)
                - `/update-publish` : Mettre à jour les informations de publication (concrètement cette action dépublie et republie le flux)
                - `/update-compare` : Compare 2 versions d'une pyramide et permet de valider la nouvelle version ou de conserver l'ancienne
                - `/sample-check` : Prévisualise un échantillon (une pyramide de tuiles vectorielles générée sur une petite zone) avant de permettre le lancement de la génération sur l'emprise complète des données