<?php

use Kan\NkOpendata\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client();

dd($client->bosses()->recent());
