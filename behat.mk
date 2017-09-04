RMQ_PORT=15672
RMQ_VHOST=behat
RMQ_USER=guest

-include .settings.mk

export RMQ_PORT
export COMPOSE_PROJECT_NAME=puzzle-amqp

CONTAINER_SOURCE_PATH=/usr/src/puzzle-amqp


#------------------------------------------------------------------------------
# Helpers
#------------------------------------------------------------------------------
init: up wait configure

wait:
	sleep 5

#------------------------------------------------------------------------------
# Containers management
#------------------------------------------------------------------------------
up: config
	docker-compose -f docker/docker-compose.yml up -d

build: config
	docker-compose -f docker/docker-compose.yml build

rebuild: build up

down:
	docker-compose -f docker/docker-compose.yml down --volumes

#------------------------------------------------------------------------------
# RabbitMQ configuration
#------------------------------------------------------------------------------
rabbitmqctl = docker exec --tty -i puzzle-amqp-rabbitmq rabbitmqctl $1

configure:
	$(call rabbitmqctl, add_vhost ${RMQ_VHOST})
	$(call rabbitmqctl, set_permissions -p ${RMQ_VHOST} ${RMQ_USER} ".*" ".*" ".*")
	$(call cli_exec,vendor/bin/rabbit --host=rabbitmq --port=15672 --password=guest vhost:mapping:create config/rabbitmq.yml)

clean-configuration:
	$(call rabbitmqctl, delete_vhost ${RMQ_VHOST})

reconfigure: clean-configuration configure

#------------------------------------------------------------------------------
# Behat test suite
#------------------------------------------------------------------------------
cli_exec = docker run -it --rm \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 --link puzzle-amqp-rabbitmq:rabbitmq \
	                 --net puzzleamqp_default \
	                 puzzle-amqp/app-server \
	                 $1

behat: reconfigure run-behat

run-behat:
	$(call cli_exec,vendor/bin/behat )

debug-behat:
	$(call cli_exec,vendor/bin/behat -v)

full-test: init run-behat down

poc-send: 
	$(call cli_exec, php test.php)
poc-receive: 
	$(call cli_exec, php test-receive.php)

analyze: 
	$(call cli_exec, /php-meminfo/analyzer/bin/analyzer query -f "class~Binary" -v var/poc.json)
	
connect:
	$(call cli_exec, /bin/bash)
