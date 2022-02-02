# Contribuer

> :exclamation: Politique d'accueil des contributions en cours de définition :exclamation:

Merci de l'intérêt que vous portez au Géotuileur ! Toute forme de contribution est bienvenue.

## Contributions autres que du code

Il y a plus d'une façon de contribuer. Si vous avez des questions ou des sujets à aborder en rapport avec le Géotuileur, n'hésitez pas à ouvrir une **issue**. Des templates sont prévus pour les signalements d'anomalie ou les demandes d'évolution et vous pouvez partir d'une feuille blanche si vous préférez.

Le Géotuileur possède également son propre formulaire de contact que vous pouvez utiliser sur une instance déployée.

## Signaler une anomalie ou suggérer une évolution

[Ouvrez une issue](https://github.com/IGNF/geotuileur-site/issues/new/choose) sur Github.

## Modifier le code et la documentation

Si vous savez utiliser Git et GitHub, vous pouvez directement contribuer au code et à la documentation du projet.

Voici quelques étapes pas à pas :

* Créez un compte sur GitHub
* Installez Git sur votre poste de travail
* Configurez Git avec vos noms et email
* Faites un fork de ce dépôt `votre_compte_github/geotuileur-site`
* Clônez votre fork (en utilisant SSH ou HTTPS au choix)
* Dans votre répertoire local, ajoutez le dépôt principal du Géotuileur comme source `upstream` (en utilisant l'URL HTTPS)
* Vous pouvez vérifier l'état de vos remotes avec la commande `git remote -v` et vous devriez avoir :

    ```
    origin	git@github.com:votre_compte_github/geotuileur-site.git (fetch)
    origin	git@github.com:votre_compte_github/geotuileur-site.git (push)
    upstream	https://github.com/IGNF/geotuileur-site.git (fetch)
    upstream	https://github.com/IGNF/geotuileur-site.git (push)
    ```

    > :exclamation: Il est important que `origin` pointe sur votre fork du dépôt et pas sur le dépôt principal.

* Mettez à jour avant de créer une branche

    ```
    git checkout master
    ```

* Téléchargez les mises à jour de toutes les branches `upstream`

    ```
    git fetch upstream
    ```

* Mettez à jour votre branche master locale pour qu'elle soit au même niveau que la branche master du dépôt principal

    ```
    git rebase upstream/master
    ```

Si la commande `rebase` vous envoie un message d'erreur parce que vous avez des changements locaux non commités, placez les dans le "stash"

```
git stash
```

Maintenant vous pouvez utiliser rebase puis réappliquer vos changements

```
git rebase upstream/master
git stash apply
```

Créez une nouvelle branche avec l'outil dont vous avez l'habitude sur votre dépôt local et travaillez dans cette nouvelle branche.

Ajoutez, commitez et pushez vos changements dans une nouvelle branche sur votre dépôt.

```
git push origin new-feature
```

### Créez une pull request

Quand vous effectuez le push, Github va vous répondre avec l'URL à utiliser pour créer une nouvelle pull request. Vous pouvez suivre cette URL ou la visiter plus tard sur GitHub. En vous rendant sur votre branche `new-feature`, GitHub va faire apparaitre un bouton pour créer une pull request.

### Après la création d'une pull request

L'équipe du Géotuileur va examiner votre pull request. Si besoin, des modifications pourront vous être demandées.

Une fois vos changements acceptés, l'équipe décidera s'il est plus approprié de merger votre branche, fusionner tous les commits en un seul ou bien effectuer un rebase de tous les commits à l'identique sur la branche master.

## Petite note juridique

Il est important que toute contribution ne contienne que du code dont la licence est clairement établie et compatible avec la licence du Géotuileur.