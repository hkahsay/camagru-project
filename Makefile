APP_NAME = camagru

DOCKER = docker compose -f ./docker-compose.yml -p $(APP_NAME)_prod

all: start_prod

build:
	$(DOCKER) build

start_prod:
	$(DOCKER) up -d --build

setup:
	$(DOCKER) run --rm setup

migrate: setup

ps:
	$(DOCKER) ps

logs:
	$(DOCKER) logs --tail=42 -ft

logsnginx:
	$(DOCKER) logs nginx

logssetup:
	$(DOCKER) logs setup

restart:
	$(DOCKER) restart


restart_nginx:
	$(DOCKER) restart nginx

stop:
	$(DOCKER) down

stop_prod:
	$(DOCKER) down

down:
	$(DOCKER) down

clean: down
	$(DOCKER) down --volumes

re: clean all


.PHONY: all build start_prod setup migrate ps logs logsnginx logssetup restart restart_nginx stop stop_prod down clean re
