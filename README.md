### Project RAGu

<img src="ragu.jpg">

#### Installation

```bash
docker-compose up --build -d

containers/php-fpm-external/docker/composer install
containers/php-fpm-internal/docker/composer install

containers/php-fpm-external/docker/console app:setup-env
```