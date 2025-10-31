## Quick start

- BaleroCMS: http://localhost:8080
- Dashboard: http://localhost:8080/login

That's all — if you open http://localhost:8080 you will see the Balero CMS Setup Wizard!

---

## Install

Set write permissions to config file:

```bash
chmod 755 ./resources/config/balero.config.json
```

---

## Starting the environment

- First time or after changing Dockerfile/images:

```bash
docker-compose up -d --build
```

- `-d` → run in background
- `--build` → rebuild images before starting containers

- Just start existing containers:

```bash
docker-compose up -d
```



