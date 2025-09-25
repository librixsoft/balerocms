# Balero CMS

Docker version of Balero CMS.

---

## Install

Set write permissions to config file:

```bash
chmod 755 ./resources/config/balero.config.json
```

---

## BaleroCMS Docker Setup Guide

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
  * Password: `""` (empty)
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

---

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

---

## Using Third-Party Libraries in BaleroCMS

BaleroCMS is ready to use Composer for dependencies.

* **Install dependencies:**

```bash
composer install
```

* **Add a new library:**

```bash
composer require vendor/package-name
```

* **Example (Guzzle):**

```bash
composer require guzzlehttp/guzzle
```

After that, you can use the library in controllers:

```php
<?php
namespace Modules\Example\Controllers;

use Framework\Core\Controller;
use GuzzleHttp\Client;

class ExampleController extends Controller
{
    public function fetchData()
    {
        $client = new Client();
        $response = $client->get('https://api.example.com/data');
        return $response->getBody()->getContents();
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

To stop the services defined in `docker-compose.yml`:

### 1️⃣ Stop only the containers (keep volumes and networks)

```bash
docker-compose stop
```

* Stops the containers but keeps volumes and networks.
* Useful if you want to restart later without losing data.

### 2️⃣ Stop and remove containers and networks

```bash
docker-compose down
```

* Stops the containers and removes associated networks.
* Persistent volumes **are not deleted**.

### 3️⃣ Stop and remove everything, including volumes

```bash
docker-compose down -v
```

* Additionally removes the volumes defined in `docker-compose.yml`.
* Useful if you want to reset MySQL or clear temporary data.

> 💡 For MySQL in memory (tmpfs), simply use `docker-compose down` and start the service again, as the database is deleted when the container stops.

---

### Note about MySQL and phpMyAdmin

* **MySQL in RAM (tmpfs):** data is lost when the container stops, ideal for quick tests.
* **Persistent MySQL (volume):** data is stored on disk, recommended for production.
* **phpMyAdmin:** use [http://localhost:8081](http://localhost:8081) with MySQL credentials.

---

## Repository

* GitHub: [https://github.com/librixsoft/balerocms-docker](https://github.com/librixsoft/balerocms-docker)
* Bitbucket mirror: [https://balerocms@bitbucket.org/librixsoft/balerocms.git](https://balerocms@bitbucket.org/librixsoft/balerocms.git)
