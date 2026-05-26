# Camagru

## Docker

Start the full stack with one command:

```sh
cp .env.example .env
docker compose up --build
```

This starts Nginx, PHP-FPM, MySQL, and runs the database setup automatically.

Open the site at:

```text
http://localhost:8080
```

## Database setup

For local non-Docker development, edit the connection settings in
`config/database.php`, then run:

```sh
php config/setup.php
```

The setup script creates the `camagru` database and installs the tables from
`config/schema.sql`.
