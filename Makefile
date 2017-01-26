all: cs unit
travis: cs travis-unit
contrib: cs unit

init:
	if [ ! -d vendor ]; then composer install; fi;

cs: init
	./vendor/bin/phpcs --standard=PSR2 src/

unit: init
	./vendor/bin/phpunit --coverage-text --coverage-html covHtml

ci: init
	./vendor/bin/phpunit --coverage-text --coverage-clover ./build/logs/clover.xml

ci-with-coverage: init
	./vendor/bin/phpunit --coverage-text --coverage-clover ./build/logs/clover.xml

ci-coverage: init
	if [ -f ./build/logs/clover.xml ]; then wget https://scrutinizer-ci.com/ocular.phar && php ocular.phar code-coverage:upload --format=php-clover ./build/logs/clover.xml; fi
