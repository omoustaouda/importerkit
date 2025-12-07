.PHONY: help composer-install docker-up docker-build docker-down test shell import demo ensure-env

# Default target
help:
	@echo "ImporterKit - Available commands:"
	@echo ""
	@echo "  make composer-install - Install/update dependencies inside Docker"
	@echo "  make docker-up        - Start services"
	@echo "  make docker-down      - Stop services"
	@echo "  make test             - Run tests inside Docker"
	@echo "  make demo             - Run demo import with sample data (GTIN lenient)"
	@echo "  make shell            - Open shell in app container"
	@echo ""
	@echo "Import:"
	@echo "  make import FILE=/path/to/feed.csv"

# Needed before docker compose reads variables
ensure-env:
	@test -f .env || cp .env.example .env

composer-install: docker-up docker-build
	docker compose run --rm --entrypoint composer app install

docker-build: ensure-env
	docker compose build app

docker-up: ensure-env
	docker compose up -d mysql

docker-down:
	docker compose down

test: composer-install
	docker compose run --rm test

shell:
	docker compose run --rm --entrypoint /bin/bash app

import: composer-install
ifndef FILE
	$(error FILE is required. Usage: make import FILE=path/to/file.csv)
endif
	docker compose run --rm app import:feed $(FILE)

demo: composer-install
	docker compose run --rm demo
