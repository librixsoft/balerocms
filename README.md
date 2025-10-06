# Balero CMS

Docker version of Balero CMS.

---

## Install

Set write permissions to config file:

```bash
chmod 755 ./resources/config/balero.config.json
```

---

### Starting the environment

* **First time or after changing Dockerfile/images:**

```bash
docker-compose up -d --build
```

* `-d` → run in background

* `--build` → rebuild images before starting containers

* **Just start existing containers:**

```bash
docker-compose up -d
```

---

## Accessing services

* **BaleroCMS:** [http://localhost:8080](http://localhost:8080)
* **MySQL:**

  * Host: `localhost`
  * Port: `3307`
  * User: `root`
  * Database: `balero_cms`
  * Password: `root`
* **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)

  * User: `root`
  * Password: `root`
* **SonarQube:** [http://localhost:9000](http://localhost:9000)

  * User: `admin`
  * Password: `admin`

---

## MySQL storage modes

You can configure MySQL in two ways:

1. **In-memory (RAM):**

  * Data is lost when the container stops.
  * Ideal for development and quick tests.

2. **Persistent with volume:**

  * Data is stored in a Docker volume.
  * Recommended for production use.

In `docker-compose.yml` you can switch between these two options by commenting/uncommenting the relevant section.

That's all, if you enter to [http://localhost:8080](http://localhost:8080) you will see the Balero CMS Setup Wizard!

Dashboard: [http://localhost:8080/login](http://localhost:8080/login)

Note: Using in production mode

public/index.php
```bash
define('APP_ENV', 'dev'); // change to "prod" if you are uploading to your server
```

---

# Documentation only for developers or/and customization

## Update/Install Front-End Libs

Update library versions in `package.json` and run:

```bash
npm install
```

---

## Run Unit Tests

Create tests in `tests/Framework` and run:

```bash
composer install
composer test
```

## Generate code coverage with Docker + PHPUnit

```bash
docker compose run --rm phpunit \
  php -d xdebug.mode=coverage ./vendor/bin/phpunit \
  -c phpunit.xml \
  --coverage-clover build/logs/coverage.xml \
  --coverage-filter Framework \
  --coverage-filter App
```

It will create: build/logs/coverage.xml

## Execute sonar to view coverage

```bash
docker run --rm \
  -e SONAR_HOST_URL="http://host.docker.internal:9000" \
  -e SONAR_TOKEN="GENERATED_TOKEN" \
  -v $(pwd):/usr/src \
  sonarsource/sonar-scanner-cli
```

---

## Using Third-Party Libraries in BaleroCMS

BaleroCMS is ready to use Composer for dependencies. Integrating  Third-Party Libraries is very easy.

### Example: Integrating an ORM (Doctrine)

Install Doctrine ORM:

```bash
composer require doctrine/orm
```

#### Example Model

```php
<?php
namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;

#[ORM\Entity]
#[ORM\Table(name: "data_items")]
class DataItemModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\Column(type: "string")]
    private string $value;

    private string $hello = "hi";

    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getValue(): string { return $this->value; }
    public function setValue(string $value): void { $this->value = $value; }

    public function getHello(): string { return $this->hello; }
    public function setHello(string $hello): void { $this->hello = $hello; }

    public function getAllItems(): array
    {
        return $this->em->getRepository(self::class)->findAll();
    }

    public function createItem(string $name, string $value): int
    {
        $item = new self($this->em);
        $item->setName($name);
        $item->setValue($value);

        $this->em->persist($item);
        $this->em->flush();

        return $item->getId();
    }
}
```

#### Example Controller

```php
<?php
namespace App\Controllers;

use App\Models\DataItemModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Controller;
use Framework\Core\View;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Doctrine\ORM\EntityManager;

#[Controller('/data')]
class DataItemController
{
    #[Inject]
    private View $view;

    #[Inject]
    private DataItemModel $model;

    #[Get('/list')]
    #[JsonResponse]
    public function listItems(): array
    {
        $items = $this->model->getAllItems();
        return array_map(fn($item) => [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'value' => $item->getValue()
        ], $items);
    }

    #[Get('/create')]
    #[JsonResponse]
    public function createItem(): array
    {
        $id = $this->model->createItem('Sample Item', '123');
        return [
            'status' => 'success',
            'id' => $id
        ];
    }

    #[Get('/view')]
    public function renderView()
    {
        $items = $this->model->getAllItems();
        return $this->view->render("data/list.html", ['items' => $items], useTheme: false);
    }
}
```

---

## SonarQube

* **Start SonarQube:**

```bash
docker-compose up -d
```

* **Run Sonar Scanner:**

```bash
docker run --rm \
  -e SONAR_HOST_URL="http://host.docker.internal:9000" \
  -e SONAR_TOKEN="your_generated_token_here" \
  -v $(pwd):/usr/src \
  sonarsource/sonar-scanner-cli
```

* **Generate a Sonar token:**

  1. Go to [http://localhost:9000](http://localhost:9000)
  2. Login as `admin/admin`
  3. Navigate to **My Account → Security**
  4. Generate and copy your token
  5. Replace it in the command above
  
---

## Stopping Docker Services

### 1️⃣ Stop only containers (keep volumes and networks)

```bash
docker-compose stop
```

### 2️⃣ Stop and remove containers and networks

```bash
docker-compose down
```

### 3️⃣ Stop and remove everything including volumes

```bash
docker-compose down -v
```

> 💡 For MySQL in RAM (tmpfs), simply use `docker-compose down` and restart; the database is deleted when the container stops.

---

### Debug

Open shell inside container:
```bash
docker exec -it lamp-app bash
```
Get log in container:
```bash
tail -F /var/log/apache2/error.log
```

### Notes

* **phpMyAdmin:** [http://localhost:8081](http://localhost:8081) (User: `root`, Password: `root`)
* **MySQL storage modes:** RAM for ephemeral testing, volume for persistent data.
* **LAMP:** Only Apache + PHP; MySQL is separate.

---

## Docker Hub Repository

[https://hub.docker.com/r/lastprophet/balerocms](https://hub.docker.com/r/lastprophet/balerocms)

## Repository

* GitHub: [https://github.com/librixsoft/balerocms-docker](https://github.com/librixsoft/balerocms-docker)
* Bitbucket mirror: [https://balerocms@bitbucket.org/librixsoft/balerocms.git](https://balerocms@bitbucket.org/librixsoft/balerocms.git)

