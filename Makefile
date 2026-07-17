.PHONY: help test test-integration coverage clean

help: ## Show available make targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

test: ## Run phpcs, phpstan, phpunit (same as CI)
	@echo "→ phpcs (PSR-12)"
	@if [ -f vendor/bin/phpcs ] && [ -d src ]; then vendor/bin/phpcs --standard=PSR12 src/; else echo "  Skip (src/ or vendor/ not present)"; fi
	@echo "→ phpstan (level=max)"
	@if [ -f vendor/bin/phpstan ] && [ -d src ]; then vendor/bin/phpstan analyse src/ --level=max; else echo "  Skip (src/ or vendor/ not present)"; fi
	@echo "→ phpunit (unit suite)"
	@if [ -f vendor/bin/phpunit ] && [ -d test/Unit ]; then XDEBUG_MODE=off vendor/bin/phpunit --testsuite=unit --no-coverage; else echo "  Skip (test/Unit/ or vendor/ not present)"; fi

test-integration: ## Run integration tests (needs Zammad instance)
	@echo "→ Integration test setup"
	@echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL      = $${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL:-not set}"
	@echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN    = $${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN:-not set}"
	@echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME = $${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME:-not set}"
	@echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD = $${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD:-not set}"
	@if [ -z "$${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL:-}" ]; then \
		echo "  → Set ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL to enable"; \
		echo "  Example:"; \
		echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL=http://localhost:3000 \\"; \
		echo "  ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN=xxx \\"; \
		echo "  make test-integration"; \
		exit 1; \
	fi
	@if [ -z "$${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN:-}" ] && [ -z "$${ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME:-}" ]; then \
		echo "  → Set ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN or ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME for authentication"; \
		exit 1; \
	fi
	@echo "→ Running integration tests..."
	@if [ -f vendor/bin/phpunit ] && [ -d test/Integration ]; then \
		XDEBUG_MODE=off vendor/bin/phpunit --testsuite=integration --group=integration --display-skipped; \
	else \
		echo "  Skip (test/Integration/ or vendor/ not present)"; \
	fi

clean: ## Remove build artifacts
	rm -rf build/
	rm -f .phpunit.cache

coverage: ## Run unit tests with coverage report (build/coverage/html/)
	@if [ -f vendor/bin/phpunit ] && [ -d test/Unit ]; then \
		php -d xdebug.mode=coverage vendor/bin/phpunit --testsuite=unit; \
	else \
		echo "  Skip (test/Unit/ or vendor/ not present)"; \
	fi
