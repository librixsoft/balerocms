## Run Unit Tests

Create tests in `Tests/Framework` and run:

```bash

docker compose exec phpunit composer install

docker compose exec phpunit composer test

```

## Generate code coverage with Docker + PHPUnit

```bash
docker compose run --rm phpunit \
  php -d xdebug.mode=coverage ./vendor/bin/phpunit \
  --configuration phpunit.xml
```

It will create: build/clover.xml

## Execute sonar to view coverage

```bash
docker run --rm \
  -e SONAR_HOST_URL="http://host.docker.internal:9000" \
  -e SONAR_TOKEN="GENERATED_TOKEN" \
  -v $(pwd):/usr/src \
  sonarsource/sonar-scanner-cli
```

---

