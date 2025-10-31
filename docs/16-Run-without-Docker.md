## Running the Application without Docker

### Using PHP Built-in Server

The application can be run using PHP's built-in web server. Make sure to start the server from the `public/` directory:
```bash
cd public
php -S localhost:8080
```

Then open your browser and navigate to:
```
http://localhost:8080
```

**Note:** You can manually generate the route cache by executing `php bin/cache_routes.php` or execute directly using Docker for this!

