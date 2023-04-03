.DEFAULT_GOAL := help

PHP_CS_RULES=@Symfony
PHP_MD_RULES=./phpmd.xml

.PHONY: help
help:
	@echo "<!> Only for dev purposes <!>"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: check-todolist
check-todolist: ## List all the "TODO" left in files with line numbers
	grep -rn "TODO" ./src ./templates ./tests ./assets/js

.PHONY: check-rules
check-rules: ## Check code rules in twig and php files using phpmd and phpstan
	@echo "-- Checking coding rules using Twig Lint Command"
	-php bin/console lint:twig --show-deprecations templates/
	@echo "-- Checking services autowiring config"
	-php bin/console lint:container
	@echo "-- Checking coding rules using phpmd (see @SuppressWarning to bypass control)"
	-vendor/bin/phpmd src text $(PHP_MD_RULES)
	@echo "-- Checking coding rules using phpstan"
	-vendor/bin/phpstan analyse -c phpstan.neon --xdebug

.PHONY: fix-style
fix-style: ## Fix code style in all php files according to the standard Symfony practices using php-cs-fixer
	@echo "-- Fixing coding style using php-cs-fixer..."
	vendor/bin/php-cs-fixer fix src --rules $(PHP_CS_RULES) --using-cache=no
	vendor/bin/php-cs-fixer fix tests --rules $(PHP_CS_RULES) --using-cache=no

.PHONY: compile-app
compile-app: ## Update and package all php and javascript dependencies
	composer update
	php bin/console assets:install
	yarn install
	yarn encore dev

.PHONY: cc
cc:
	php bin/console cache:clear

.PHONY: up
up: ## Launch docker containers
	docker-compose up -d --build --remove-orphans

.PHONY: down
down: ## Take down docker containers
	docker-compose down --remove-orphans

.PHONY: bash
bash: ## Enter with bash into the php docker container
	docker exec -it geotuileur-site_app_dev_1 bash

.PHONY: d
d: ## Run any command in the php docker container
	docker exec -it geotuileur-site_app_dev_1 $(c)
