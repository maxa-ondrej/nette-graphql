qa: cfx cs phpstan

cs:
	vendor/bin/phpcs --standard=phpcs.xml src

cfx:
	vendor/bin/phpcbf --standard=phpcs.xml src

phpstan:
	vendor/bin/phpstan analyse -l 8 -c phpstan.neon --memory-limit=1G src
