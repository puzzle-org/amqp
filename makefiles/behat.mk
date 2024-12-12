#------------------------------------------------------------------------------
# Behat
#------------------------------------------------------------------------------
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
init: composer-install up wait configure

wait:
	sleep 10

#------------------------------------------------------------------------------
# Containers management
#------------------------------------------------------------------------------
up: config
	docker compose -f docker/docker-compose.yml up -d

build: config
	docker compose -f docker/docker-compose.yml build

rebuild: build up

down:
	docker compose -f docker/docker-compose.yml down --volumes

#------------------------------------------------------------------------------
# RabbitMQ configuration
#------------------------------------------------------------------------------
rabbitmqctl = $(DOCKER_EXEC) puzzle-amqp-rabbitmq rabbitmqctl $1

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
cli_exec = $(DOCKER_RUN) --rm \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 --network 'container:puzzle-amqp-rabbitmq' \
	                 puzzle-amqp/app-server \
	                 $1

full-test: init run-behat down ## Run behat tests (and manage containers)

behat: reconfigure run-behat ## Run behat tests with already existing containers

run-behat:
	$(call cli_exec,vendor/bin/behat )

debug-behat:
	$(call cli_exec,vendor/bin/behat -v)
