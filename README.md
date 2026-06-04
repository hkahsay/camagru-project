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

Open the local email inbox at:

```text
http://localhost:8025
```

In local Docker, confirmation emails are captured by Mailpit instead of being
sent to a real external inbox. To send real email, configure SMTP values in
`.env` and keep `MAIL_DRIVER=smtp`.

## Database setup

For local non-Docker development, edit the connection settings in
`config/database.php`, then run:

```sh
php config/setup.php
```

The setup script creates the `camagru` database and installs the tables from
`config/schema.sql`.

## Tests

Run the unit-style test suite:

```sh
make test
```
