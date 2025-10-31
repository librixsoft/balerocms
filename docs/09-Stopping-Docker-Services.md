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

> For MySQL in RAM (tmpfs), simply use `docker-compose down` and restart; the database is deleted when the container stops.



