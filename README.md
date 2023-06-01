## Usage

* git clone git@github.com:oleksandr-andrushchenko/test.symfony-slack-webhook-email.git
* cd test.symfony-slack-webhook-email
* docker compose up -d --build
* docker compose exec php bash
* Create App & Webhooks: https://api.slack.com/messaging/webhooks
* Update SLACK_* variables in .env file
* Test: php bin/console mailer:test "oleksandr.andrushchenko1988@gmail.com" --body="Thank you!"
* [Check your channel](img.png)

## History

* cd ~/Projects
* docker run --rm -v $(pwd):/app composer/composer create-project symfony/skeleton:"6.3.*@dev" test.traject
* sudo chmod -R g+w test.symfony-slack-webhook-email
* sudo chown 1001:1001 test.symfony-slack-webhook-email
* cd test.symfony-slack-webhook-email
* touch [README.md](README.md)
* touch [docker-compose.yml](docker-compose.yml)
* touch [docker-entrypoint.sh](docker-entrypoint.sh)
* touch [Dockerfile](Dockerfile)
* touch .env
* docker compose up -d --build
* docker compose exec php bash
* composer require symfony/http-client symfony/mailer
* mkdir -p src/Service/Mailer/Transport
* ... src/Service/Mailer/Transport/*
* ... config/services/mailer.yaml