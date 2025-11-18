<?php

require_once(__DIR__ . '/vendor/autoload.php');

$config = Finnhub\Configuration::getDefaultConfiguration()->setApiKey('token', 'd44uqihr01qr9l82q140d44uqihr01qr9l82q14g');
$client = new Finnhub\Api\DefaultApi(
    new GuzzleHttp\Client(),
    $config
);

print_r($client->recommendationTrends("AAPL"));

?>
