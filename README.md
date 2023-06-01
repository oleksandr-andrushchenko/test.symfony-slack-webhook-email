## History

* cd ~/Projects
* docker run --rm -v $(pwd):/app composer/composer create-project symfony/skeleton:"6.3.*@dev" test.traject
* sudo chmod -R g+w test.traject
* sudo chown 1001:1001 test.traject
* cd test.traject
* touch [README.md](README.md)
* touch [docker-compose.yml](docker-compose.yml)
* touch [docker-entrypoint.sh](docker-entrypoint.sh)
* touch [Dockerfile](Dockerfile)