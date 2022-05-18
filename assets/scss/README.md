L'organisation des styles suit le **Pattern 7-1** (7 dossiers, 1 fichier) (Référence : https://sass-guidelin.es/)

Le fichier principal est `main.scss` et il ne doit contenir que des `@use` (NB : [`@import` est découragé par l"équipe de Sass](https://sass-lang.com/documentation/at-rules/import)).

Pour la lisibilité de `main.scss` :

* les underscores et l'extension des fichiers `.scss`peut être oubliée,
* une ligne par fichier,
* une ligne vide après chaque dossier,
* pas d'import de tout un dossier avec `folder/*` car l'ordre est important en CSS.

Les `@use` doivent être dans cet ordre :

1. `abstracts/` (variables, fonctions, mixins et placeholders sass)
2. `vendors/` (styles tiers, jquery-ui, bootstrap...)
3. `base/` (styles typographiques et normalisation)
4. `layout/` (grille, pied de page, en-tête...)
5. `components/` (boutons, badges, menus déroulants...)
6. `pages/` (styles spécifiques à certaines pages de l'application)
7. `themes/` (thèmes pour tout le site, pour les admins par exemple)

NB : En ce qui concerne la documentation incluse dans une iframe, une feuille de style séparée se trouve dans le dossier `../css` pour personnaliser Vue.