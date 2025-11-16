### Project RAGu

<img src="ragu.jpg">

#### Installation

```bash
docker-compose up --build -d

containers/php-fpm-external/docker/composer install
containers/php-fpm-internal/docker/composer install

containers/php-fpm-external/docker/console app:setup-env
```

Set APP_ENV to "dev" in .env.local for development mode.

#### Setup User

```bash
containers/php-fpm-external/docker/console app:create-user
```