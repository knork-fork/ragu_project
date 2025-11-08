<?php

echo 'hello from php-fpm-external';

echo '<br>';

$response = file_get_contents('http://webserver:8081');

echo 'response from internal service: ' . $response;