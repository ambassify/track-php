<?php

require_once __DIR__ . '/vendor/autoload.php';

use \Ambassify\Track\Client as Track;

$client = Track::factory([
    'strict' => true,
    'apikey' => 'A5BMdQk&Ny[cqR)jh>Z7GEly121JVk}eOI9JdV1q03,b}>3ysD]R;a&%ic489P',
    // 'apikey' => 'a1',
    'endpoint' => 'http://forwrd.it',
    'baseurl' => 'http://forwrd.it'
]);

echo($client->override('1z', [ 'foo' => 'bar' ]));
echo '<br>';
echo(
    $client->override(
        $client->shorten([Track::PARAM_URL => 'http://google.be']),
        [ 'u' => 'http://bubobox.com' ]
    )
);
