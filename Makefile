COMPOSE_ENV := --env-file docker/.env
COMPOSE_PROD := docker compose $(COMPOSE_ENV) -f docker/compose.yaml
COMPOSE_DEV := $(COMPOSE_PROD) -f docker/compose.dev.yaml

.PHONY: up up-prod build build-prod down down-prod ps ps-prod logs logs-prod config config-prod

up:
	$(COMPOSE_DEV) up -d

up-prod:
	$(COMPOSE_PROD) up -d

build:
	$(COMPOSE_DEV) build

build-prod:
	$(COMPOSE_PROD) build

down:
	$(COMPOSE_DEV) down

down-prod:
	$(COMPOSE_PROD) down

ps:
	$(COMPOSE_DEV) ps

ps-prod:
	$(COMPOSE_PROD) ps

logs:
	$(COMPOSE_DEV) logs -f

logs-prod:
	$(COMPOSE_PROD) logs -f

config:
	$(COMPOSE_DEV) config

config-prod:
	$(COMPOSE_PROD) config
