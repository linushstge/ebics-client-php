#WIN_ETH_DRIVER := 'Ethernet adapter Ethernet'

ifdef WIN_ETH_DRIVER
WIN_ETH_IP := $(shell ipconfig.exe | grep ${WIN_ETH_DRIVER} -A3 | cut -d':' -f 2 | tail -n1 | sed -e 's/\s*//g')
endif

DC := cd docker && $(shell command -v docker-compose || echo "docker compose") -p ebics-client-php

docker-up u start:
	$(DC) up -d;
	@if [ "$(WIN_ETH_IP)" ]; then $(DC) exec php-cli-ebics-client-php sh -c "echo '$(WIN_ETH_IP) host.docker.internal' >> /etc/hosts"; fi

docker-down d stop:
	$(DC) down

docker-build build:
	$(DC) build --no-cache

docker-install install:
	$(DC) exec php-cli-ebics-client-php composer install

docker-php php:
	$(DC) exec php-cli-ebics-client-php /bin/bash

check:
	$(DC) exec php-cli-ebics-client-php ./vendor/bin/phpcbf
	$(DC) exec php-cli-ebics-client-php ./vendor/bin/phpcs
	$(DC) exec php-cli-ebics-client-php ./vendor/bin/phpstan --xdebug
	$(DC) exec php-cli-ebics-client-php ./vendor/bin/phpunit

credentials-pack:
	$(DC) exec php-cli-ebics-client-php zip -P $(pwd) -r ./tests/_data.zip ./tests/_data/

credentials-unpack:
	unzip -P $(pwd) ./tests/_data.zip -d .
