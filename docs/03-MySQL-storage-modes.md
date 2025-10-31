## MySQL storage modes

You can configure MySQL in two ways:

1. In-memory (RAM):
   - Data is lost when the container stops.
   - Ideal for development and quick tests.

2. Persistent with volume:
   - Data is stored in a Docker volume.
   - Recommended for production use.

In `docker-compose.yml` you can switch between these two options by commenting/uncommenting the relevant section.
