<?php

use Illuminate\Routing\Router;

Route::group(['middleware' => 'cors'], function(Router $router) {
    $spacename = "\ForestAdmin\ForestLaravel\Http\Controllers";
    
    Route::get('forest', $spacename.'\ForestController@index');
    Route::post('forest/sessions', $spacename.'\ForestController@sessions');

    Route::get('forest/post', $spacename.'ForestController@post');

    Route::get('forest/{modelName}/{recordId}', $spacename.'LianaController@getResource');
});

