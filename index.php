<?php

use Kan\NkOpendata\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client();

print_r($client->ct()->recent());