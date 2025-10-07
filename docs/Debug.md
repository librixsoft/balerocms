# Installing environment for local debugging

# 1. Update Homebrew
brew update

# 2. Install PHP (latest stable version, e.g., 8.4)
brew install php

# 3. Verify PHP installation
php -v
# You should see something like "PHP 8.4.x (cli)"

# 4. Install Xdebug (if not included in PHP by default)
brew install php-xdebug

# 5. Enable Xdebug in PHP
# Check php.ini location
php --ini
# Open the loaded php.ini and add/edit:
zend_extension="xdebug.so"
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003

# 6. Verify Xdebug is active
php -i | grep xdebug
# You should see xdebug.mode => debug


# Debugging Balero CMS with PhpStorm + Xdebug

This document summarizes the steps to set up and debug your Balero CMS project locally using PHP built-in server and PhpStorm.

---

## 1. Prepare PHP Environment

Check that PHP and Xdebug are installed locally (Homebrew):

```bash
php -v
php -i | grep xdebug
```

You should see something like:

```
PHP 8.4.x ...
with Xdebug v3.x ...
```

> Note: Ensure that `xdebug.mode => debug` and `xdebug.start_with_request => yes`.

---

## 2. Start PHP Built-in Server

Your CMS `index.php` is in `public/`, so you must start the server pointing to that folder as the DocumentRoot.

### Option 1: From the project root

```bash
php -S localhost:8081 -t public
```

### Option 2: From inside `public/`

```bash
cd public
php -S localhost:8081
```

- Now `http://localhost:8081/` points to `public/index.php`
- All friendly URLs work without `/public` in the URL

---

## 3. PhpStorm Configuration

1. **PHP Servers**:
    - Host: `localhost`
    - Port: `8081`
    - Path mapping: project root → `/` (DocumentRoot)

2. Enable **Listen for PHP Debug Connections** (phone icon ☎️)

3. Set **breakpoints** in your code.

4. Open the URL in the browser:

```
http://localhost:8081/home
http://localhost:8081/test
```

- PhpStorm will stop execution at the breakpoints.

---

## 4. Friendly URLs and Router

- Avoid including `/public` in URLs when using PHP built-in server with `public` as the root.
- Your internal Router (`Framework\\Bootstrap\\Router`) will continue handling controller routes.

### Example:
```
Controller HomeController -> /home
Controller TestController -> /test
```
- Direct access:
```
http://localhost:8081/home
http://localhost:8081/test
```

---

## 5. Additional Notes

- To stop the built-in server: **Ctrl + C** in the terminal.
- When adding new routes, just open the corresponding URL; you don’t need to modify the router.
- Keep `xdebug.log` configured to check connection errors if anything fails.

---

Now you can debug your Balero CMS step by step with PhpStorm and Xdebug.

