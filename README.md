# Balero CMS

Docker version of Balero CMS.

# Install


Set write permissions to config file.

```bash
$ chmod 755 ./resources/config/balero.config.json
```

## BaleroCMS Docker Setup Guide

### Starting the environment

1. **First time or after changing Dockerfile/images**:

```
docker-compose up -d --build
```

- `-d` → run in background
- `--build` → rebuild images before starting containers

2. **Just start existing containers**:

```
docker-compose up -d
```

---

### Accessing services

- **BaleroCMS:** http://localhost:8080
- **MySQL:**
    - Host: `localhost`
    - Port: `3307`
    - User: `root`
    - Database: `balero_cms`
    - Password: `""` (empty)

- **SonarQube:** http://localhost:9000
    - User: `admin`
    - Password: `admin`

---

## Update/Install Front-End Libs

Adjust your library version in `package.json` and execute:

```bash
$ npm install
```

It will update your local front-end libraries.

---

## Run Unit Tests

Create your tests in `tests/Framework` and execute:

```bash
$ composer install
$ composer test
```

---

## Using Third-Party Libraries in Balerocms

Balerocms is ready to use Composer for managing third-party libraries. Follow these steps to include and use external
libraries in your CMS.

### 1. Install libraries

Navigate to the root of your project (`balerocms-src/`) and run:

```bash
$ composer install
```

### 1. Add a library with Composer

```bash
$ composer require vendor/package-name
```

#### Example: Installing Guzzle for HTTP requests (third-party library)

```bash
$ composer require guzzlehttp/guzzle
```

This will:

- Download the library into `vendor/`.

---

### 2. Include the library in the CMS

Now you can use the library inside a controller. For example:

```php
<?php
namespace Modules\Example\Controllers;

use Framework\Core\Controller;
use GuzzleHttp\Client; // Third-party library

class ExampleController extends Controller
{
    public function fetchData()
    {
        // Create Guzzle HTTP client
        $client = new Client();

        // Send GET request
        $response = $client->get('https://api.example.com/data');

        // Get response body
        $body = $response->getBody()->getContents();

        return $body;
    }
}
```

# Sonar

### Start SonarQube with Docker

```
$ docker-compose up -d
```

### Run Sonar Scanner

```
docker run --rm \
  -e SONAR_HOST_URL="http://host.docker.internal:9000" \
  -e SONAR_TOKEN="sqa_3883c0bd01ccc056b9e62e5b1108674ceb1afde3" \
  -v $(pwd):/usr/src \
  sonarsource/sonar-scanner-cli
```

### How to get your Sonar token from the dashboard

1. Open your browser and go to your SonarQube dashboard:

```
http://localhost:9000
```

2. Log in with your account (default admin/admin).

3. Click on your avatar in the top right corner → **My Account**.

4. Go to the **Security** tab.

5. Under **Tokens**, click **Generate Token**.

6. Give your token a name (e.g., `balero_project`) and click **Generate**.

7. Copy the token immediately — this is your `SONAR_TOKEN` to use in the scanner command.

8. Replace the token in your Docker command:

```
-e SONAR_TOKEN="your_generated_token_here"
```

Now you can run the scanner and it will authenticate with SonarQube using your token.

# Reposiroty

GitHub: [https://github.com/librixsoft/balerocms-docker](https://github.com/librixsoft/balerocms-docker)

