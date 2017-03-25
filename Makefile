HOST_SOURCE_PATH=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

export USER_ID
export GROUP_ID

# Spread cli arguments for composer & phpunit
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),composer phpunit))
    CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    $(eval $(CLI_ARGS):;@:)
endif

#------------------------------------------------------------------------------
# Composer
#------------------------------------------------------------------------------

# Add ignore platform reqs for composer install & update
COMPOSER_ARGS=
ifeq (composer, $(firstword $(MAKECMDGOALS)))
    ifneq (,$(filter install update,$(CLI_ARGS)))
        COMPOSER_ARGS=--ignore-platform-reqs
    endif
endif

composer: composer.phar
	php composer.phar $(CLI_ARGS) $(COMPOSER_ARGS)

composer-install: composer.phar
	php composer.phar install --ignore-platform-reqs

composer.phar:
	curl -sS https://getcomposer.org/installer | php

clean: remove-deps
	rm -f composer.lock
	rm -f composer.phar

remove-deps:
	rm -rf vendor

#------------------------------------------------------------------------------
# Karma
#------------------------------------------------------------------------------
config: karma
	./karma hydrate -e behat

karma:
	$(eval LATEST_VERSION := $(shell curl -L -s -H 'Accept: application/json' https://github.com/niktux/karma/releases/latest | sed -e 's/.*"tag_name":"\([^"]*\)".*/\1/'))
	@echo "Latest version of Karma is ${LATEST_VERSION}"
	wget -O karma -q https://github.com/Niktux/karma/releases/download/${LATEST_VERSION}/karma.phar
	chmod 0755 karma


-include phpunit.mk
-include behat.mk

.PHONY: composer composer-install clean remove-deps config
