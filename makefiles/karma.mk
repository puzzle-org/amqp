#------------------------------------------------------------------------------
# Karma
#------------------------------------------------------------------------------
config: karma ## Run karma for behat tests
	@$(DOCKER_RUN) --rm --name whalephant \
               -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
               -w ${CONTAINER_SOURCE_PATH} \
               -u ${USER_ID}:${GROUP_ID} \
               php:8.3-cli \
               ./karma hydrate -e behat

karma:
	$(eval LATEST_VERSION := $(shell curl -L -s -H 'Accept: application/json' https://github.com/niktux/karma/releases/latest | sed -e 's/.*"tag_name":"\([^"]*\)".*/\1/'))
	@echo "Latest version of Karma is ${LATEST_VERSION}"
	wget -O karma -q https://github.com/Niktux/karma/releases/download/${LATEST_VERSION}/karma.phar
	chmod 0755 karma

clean-karma:
	rm -f karma

.PHONY: config clean-karma
