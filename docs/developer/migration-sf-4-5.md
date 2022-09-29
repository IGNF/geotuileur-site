# Migration de Symfony 4 vers 5

> https://symfony.com/doc/4.4/setup/upgrade_major.html

Etapes principales :

-   se débarrasser de toutes les dépréciations de code (des tests phpunit ou le profiler de Symfony pourraient aider à les repérer)
-   mettre à jour les recettes flex
-   composer.json

```diff
-         "symfony/*": "4.4.*",
+         "symfony/*": "5.4.*",
-         "symfony/*": "^4.4,
+         "symfony/*": "^5.4",

...

"extra": {
      "symfony": {
          "allow-contrib": false,
-       "require": "4.4.*"
+       "require": "5.4.*"
      }
  }
```

-   composer update "symfony/\*" [--with-all-dependencies]
-   composer update
-   se débarrasser de toutes les dépréciations de code à nouveau
-   mettre à jour les recettes flex
