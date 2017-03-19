RMQ_PORT=5672

-include .settings.mk

export RMQ_PORT
export COMPOSE_PROJECT_NAME=puzzle-amqp

CONTAINER_SOURCE_PATH=/usr/src/puzzle-amqp

cli_exec = docker run -it --rm --name puzzle-amqp-cli \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 --link puzzle-amqp-rabbitmq:rabbitmq \
	                 --net puzzleamqp_default \
	                 puzzle-amqp/app-server \
	                 $1

validate: init full-test

init: up wait config

wait:
	sleep 5

config:
	$(call cli_exec,vendor/bin/rabbit --host=rabbitmq --port=15672 --password=guest vhost:mapping:create dev/rabbitmq.yml)

full-test: test-send test-worker

test-send:
	$(call cli_exec,php dev/send.php)

test-worker:
	$(call cli_exec,php dev/worker.php)

up: 
	docker-compose -f docker/docker-compose.yml up -d

build:
	docker-compose -f docker/docker-compose.yml build

rebuild: build up

down:
	docker-compose -f docker/docker-compose.yml down --volumes
