PHP_IMAGE = eu.gcr.io/basekit-ci/basekit-php-base:8.4.21-fpm-alpine3.22-1
BASE_PATH = "/var/basekit"
PLOVR_URL="https://storage.googleapis.com/google-code-archive-downloads/v2/code.google.com/plovr/plovr-81ed862.jar"

build: clean twig.js twig.dev.js

clean:
	rm -f twig.js
	rm -f twig.dev.js

twig.js: bin/plovr
	java -jar bin/plovr build Resources/config/compile.js

twig.dev.js: bin/plovr
	java -jar bin/plovr build Resources/config/compile_dev.js

bin/plovr:
	wget $(PLOVR_URL) -O bin/plovr

test: vendor node_modules
	node tests-js/json-rpc.js &
	./vendor/bin/phpunit
	./node_modules/.bin/mocha --require tests-js/bootstrap.js tests-js/twig/* tests-js/twig/*/*

node_modules:
	npm install

vendor:
	composer install

phpcs: vendor
	./vendor/bin/phpcs --standard=PSR2 --error-severity=1 src
	./vendor/bin/phpcs --standard=PSR2 --error-severity=1 tests

.PHONY: build clean test phpcs

composer_update: ## Run composer update
	docker run -e COMPOSER_HOME=$(HOME)/.composer -u $(shell id -u $$USER) -v $(HOME):$(HOME) -v $(CURDIR):$(BASE_PATH) -w $(BASE_PATH) --rm $(PHP_IMAGE) php composer.phar update $(PACKAGE) --ignore-platform-req=php+

phpunit: ## Run phpunit tests. Use GREP to filter tests (e.g. make phpunit GREP=MyTest)
	docker compose run --rm php php -d memory_limit=-1 -d xdebug.default_enable=false ./vendor/bin/phpunit

