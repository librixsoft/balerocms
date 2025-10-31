## SonarQube

- Start SonarQube:

```bash
docker-compose up -d
```

- Run Sonar Scanner:

```bash
docker run --rm \
  -e SONAR_HOST_URL="http://host.docker.internal:9000" \
  -e SONAR_TOKEN="your_generated_token_here" \
  -v $(pwd):/usr/src \
  sonarsource/sonar-scanner-cli
```

- Generate a Sonar token:
  1. Go to http://localhost:9000
  2. Login as `admin/admin`
  3. Navigate to My Account → Security
  4. Generate and copy your token
  5. Replace it in the command above
