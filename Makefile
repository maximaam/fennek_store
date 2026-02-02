# Makefile for Symfony

# Variables
PHP := php
CONSOLE := bin/console
COMPOSER := composer
SYMFONY := symfony
YARN := yarn
DOCKER_COMPOSE := docker-compose

.DEFAULT_GOAL := help

## —— Symfony Commands ———————————————————————————————————————————————————

install: ## Install PHP & JS dependencies
	$(COMPOSER) install
	$(YARN) install

start: ## Start local Symfony server
	$(SYMFONY) server:start -d

stop: ## Stop local Symfony server
	$(SYMFONY) server:stop

cache-clear: ## Clear Symfony cache
	$(PHP) $(CONSOLE) cache:clear

cache-warmup: ## Warm up Symfony cache
	$(PHP) $(CONSOLE) cache:warmup

cc: cache-clear ## Alias for cache-clear

mk-mig: ## Make migration migrations
	$(PHP) $(CONSOLE) make:migration

migrate: ## Run Doctrine migrations - @hides the echo of the command
	$(PHP) $(CONSOLE) doctrine:migrations:migrate

sql: ## Run sql queries
	@$(PHP) $(CONSOLE) dbal:run-sql "$(SQL)"

# php bin/console dbal:run-sql "SELECT VERSION();"

fixtures: ## Load Doctrine fixtures
	$(PHP) $(CONSOLE) doctrine:fixtures:load --no-interaction

lint: ## Run linters
	$(PHP) $(CONSOLE) lint:yaml config
	$(PHP) $(CONSOLE) lint:twig templates

test: ## Run PHPUnit tests
	$(PHP) bin/phpunit

## —— Docker (optional) —————————————————————————————————————————————————————

dc-up: ## Start Docker containers
	$(DOCKER_COMPOSE) up -d

dc-down: ## Stop Docker containers
	$(DOCKER_COMPOSE) down

## —— Help —————————————————————————————————————————————————————————————

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

liip-cc: ## Clears uploaded images cache
	$(PHP) $(CONSOLE) liip:imagine:cache:remove

# https://ux.symfony.com/icons?set=bi&query=
icon-import: ## Import icons from the BI lib using SF UX
	$(PHP) $(CONSOLE) ux:icons:import bi:$(ICON)
