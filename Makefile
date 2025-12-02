.PHONY: help install test test-unit test-integration docker-build docker-up docker-down docker-test import shell

# Default target
help:
	@echo "Data Feed Importer - Available commands:"
	@echo ""
	@echo "  make install          - Install dependencies"
	@echo "  make test             - Run all tests"
	@echo "  make test-unit        - Run unit tests only"
	@echo "  make test-integration - Run integration tests only"
	@echo ""
	@echo "Docker commands:"
	@echo "  make docker-up        - Start services"
	@echo "  make docker-down      - Stop services"
	@echo "  make docker-test      - Run tests in Docker"
	@echo "  make shell            - Open shell in app container"
	@echo ""
	@echo "Import command:"
	@echo "  make import FILE=path/to/file.csv"

# Local development
install:
	composer install

test:
	php vendor/bin/phpunit

test-unit:
	php vendor/bin/phpunit --testsuite=unit

test-integration:
	php vendor/bin/phpunit --testsuite=integration

# Docker commands
docker-up:
	docker compose up -d mysql

docker-down:
	docker compose down

docker-test:
	docker compose run --rm test

shell:
	docker compose run --rm --entrypoint /bin/bash app

# Import command
import:
ifndef FILE
	$(error FILE is required. Usage: make import FILE=path/to/file.csv)
endif
	docker compose run --rm app import:feed $(FILE)
