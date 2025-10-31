## Debug

Open shell inside container:

```bash
docker exec -it lamp-app bash
```

Get log in container:

```bash
tail -F /var/log/apache2/error.log
```



