#------------------------------------------------------------------------------
# PHPUnit
#------------------------------------------------------------------------------
IMAGE_NAME=puzzle/amqp/phpunit
CONTAINER_SOURCE_PATH=/usr/src/puzzle-amqp

phpunit = $(DOCKER_RUN) --rm --name phpunit \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 -u ${USER_ID}:${GROUP_ID} \
	                 ${IMAGE_NAME} \
	                 vendor/bin/phpunit $1 $(CLI_ARGS)

phpunit: vendor/bin/phpunit create-phpunit-image ## Run unit tests
	$(call phpunit, )

phpunit-dox: vendor/bin/phpunit create-phpunit-image
	$(call phpunit, --testdox)

phpunit-coverage: vendor/bin/phpunit create-phpunit-image ## Run unit tests with coverage report
	$(call phpunit, --coverage-html=coverage/)

vendor/bin/phpunit: composer-install

#------------------------------------------------------------------------------

create-phpunit-image: docker/images/phpunit/Dockerfile
	docker build -q -t ${IMAGE_NAME} docker/images/phpunit/

#------------------------------------------------------------------------------

.PHONY: clean-phpunit
clean-phpunit: clean-phpunit-image

clean-phpunit-image:
	-rm docker/images/phpunit/Dockerfile
	docker rmi ${IMAGE_NAME}

.PHONY: phpunit phpunit-dox phpunit-coverage create-phpunit-image clean-phpunit-image
