#------------------------------------------------------------------------------
# Composer
#------------------------------------------------------------------------------

COMPOSER_VERSION?=2

#------------------------------------------------------------------------------

#
# COMPOSER_OPTION
#    This variable defines options always passed to composer.
#
#    Variables explanations :
#        --no-plugins : do not execute nor prompt for plugin authorization at deps installation/update.
#                       Avoid composer updating the "allow-plugins" list in composer.json by itself
#
COMPOSER_OPTIONS=--no-plugins

#------------------------------------------------------------------------------

composer = $(DOCKER_RUN) --rm \
                -v ${HOST_SOURCE_PATH}:/var/www/app \
                -v ~/.cache/composer:/tmp/composer \
                -e COMPOSER_CACHE_DIR=/tmp/composer \
                -w /var/www/app \
                -u ${USER_ID}:${GROUP_ID} \
                composer:${COMPOSER_VERSION} ${COMPOSER_OPTIONS} $(COMPOSER_INTERACTIVE) $1 $2

# Spread cli arguments
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),composer))
    CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    $(eval $(CLI_ARGS):;@:)
endif

# Add ignore platform reqs for composer install & update
COMPOSER_ARGS=
ifeq (composer, $(firstword $(MAKECMDGOALS)))
    ifneq (,$(filter install update require,$(CLI_ARGS)))
        COMPOSER_ARGS=--ignore-platform-reqs
    endif
endif

#------------------------------------------------------------------------------

.PHONY: composer
composer: -composer-init ## Run composer
	$(call composer, $(CLI_ARGS), $(COMPOSER_ARGS))

.PHONY: composer-install
composer-install: -composer-init vendor/ ## Install dependencies via composer

# If you want to edit (and commit) a library directly in vendor/
# just rm -rf vendor/<lib_path> + make composer-install-sources
.PHONY: composer-install-sources
composer-install-sources: -composer-init
	$(call composer, install, --ignore-platform-reqs --prefer-source)

.PHONY: composer-update
composer-update: -composer-init
	$(call composer, update, --ignore-platform-reqs)

.PHONY: composer-dumpautoload
composer-dumpautoload: -composer-init
	$(call composer, dumpautoload)

.PHONY: composer-version
composer-version: -composer-init
	$(call composer, --version)

#------------------------------------------------------------------------------
# Non PHONY targets
#------------------------------------------------------------------------------

vendor/: composer.json
	@$(call composer, install, --ignore-platform-reqs)

#------------------------------------------------------------------------------

.PHONY: -composer-init
-composer-init: ~/.cache/composer

~/.cache/composer:
	mkdir -p ~/.cache/composer

#------------------------------------------------------------------------------

.PHONY: clean-composer
clean-composer:
	-rm -f composer.lock
	-rm -rf vendor
