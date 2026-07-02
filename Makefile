SHELL := /bin/bash

DC := docker compose
API := $(DC) exec api
WEB := $(DC) exec web

.PHONY: help up down stop restart build rebuild ps logs \
        logs-api logs-web logs-nginx shell-api shell-web \
        composer-install npm-install migrate migrate-fresh \
        seed test test-api test-web type-check lint routes \
        clear health

help:
	@echo "FieldFlow development commands:"
	@echo ""
	@echo "  make up                 Start containers"
	@echo "  make down               Stop and remove containers"
	@echo "  make restart            Restart containers"
	@echo "  make build              Build images"
	@echo "  make rebuild            Rebuild images without cache"
	@echo "  make ps                 Show container status"
	@echo "  make logs               Follow all logs"
	@echo "  make logs-api           Follow API logs"
	@echo "  make logs-web           Follow frontend logs"
	@echo "  make shell-api          Open shell in API container"
	@echo "  make shell-web          Open shell in frontend container"
	@echo "  make migrate            Run database migrations"
	@echo "  make migrate-fresh      Recreate database"
	@echo "  make test               Run backend and frontend tests"
	@echo "  make type-check         Run Vue TypeScript checks"
	@echo "  make routes             Show API routes"
	@echo "  make health             Check application health"

up:
	$(DC) up -d

down:
	$(DC) down

stop:
	$(DC) stop

restart:
	$(DC) down
	$(DC) up -d

build:
	$(DC) build

rebuild:
	$(DC) build --no-cache

ps:
	$(DC) ps

logs:
	$(DC) logs -f

logs-api:
	$(DC) logs -f api

logs-web:
	$(DC) logs -f web

logs-nginx:
	$(DC) logs -f nginx

shell-api:
	$(API) bash

shell-web:
	$(WEB) sh

composer-install:
	$(API) composer install

npm-install:
	$(WEB) npm install

migrate:
	$(API) php artisan migrate

migrate-fresh:
	$(API) php artisan migrate:fresh --seed

seed:
	$(API) php artisan db:seed

test: test-api test-web

test-api:
	$(API) php artisan test

test-web:
	$(WEB) npm run test:unit -- --run

type-check:
	$(WEB) npm run type-check

lint:
	$(WEB) npm run lint

routes:
	$(API) php artisan route:list --path=api

clear:
	$(API) php artisan optimize:clear

health:
	@curl --fail --silent http://localhost:8080/api/health
	@echo