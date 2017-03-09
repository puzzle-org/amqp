#------------------------------------------------------------------------------
# PHPUnit
#------------------------------------------------------------------------------
CONTAINER_NAME=puzzle/amqp/phpunit

phpunit = docker run -it --rm --name phpunit \
	                 -v ${PUZZLE_AMQP_DIR}:/usr/src/puzzle-amqp \
	                 -w /usr/src/puzzle-amqp \
	                 -u ${USER_ID}:${GROUP_ID} \
	                 ${CONTAINER_NAME} \
	                 vendor/bin/phpunit $1 $(CLI_ARGS)

phpunit: vendor/bin/phpunit create-phpunit-image
	$(call phpunit, )

phpunit-coverage: vendor/bin/phpunit create-phpunit-image
	$(call phpunit, --coverage-html=coverage/)

vendor/bin/phpunit: composer-install

create-phpunit-image:
	docker build -q -t ${CONTAINER_NAME} docker/images/phpunit/

clean-phpunit-image:
	docker rmi ${CONTAINER_NAME}

.PHONY: phpunit create-phpunit-image clean-phpunit-image
