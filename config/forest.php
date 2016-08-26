<?php

return [
    /*
     |--------------------------------------------------------------------------
     | ForestAdmin Package
     |--------------------------------------------------------------------------
     |
     |
     */

    'ApiMap' => 'https://forestadmin-server.herokuapp.com/forest/apimaps',
    'URI' => 'https://forestadmin-server.herokuapp.com',
    'SecretKey' => '4d737c706cbd622b45820ecfe6669cab36e26ea600df8e35a69b91502cb9749f',
    'AuthKey' => env('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Model locations to include
    |--------------------------------------------------------------------------
    |
    | Define in which directories the forest:postmap command should look
    | for models.
    |
    */

    'ModelLocations' => array(
        'app'
    ),
];

