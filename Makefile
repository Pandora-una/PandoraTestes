.PHONY: install test test-1x

install:
	docker compose run --rm pandora-testes composer install --no-progress

test: # PHP 8.4, vendor deste repositório
	@[ -d vendor ] || $(MAKE) install
	docker compose run --rm pandora-testes vendor/bin/phpunit --fail-on-deprecation --fail-on-notice --fail-on-warning
