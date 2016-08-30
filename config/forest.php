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
    'SecretKey' => 'SecretKeyGeneratedFromForestAdminWebsite',
    'AuthKey' => 'YourSecretKey',

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

