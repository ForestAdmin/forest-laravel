<?php

use Illuminate\Routing\Router;

Route::group(['middleware' => 'cors'], function(Router $router) {
    $spacename = "\ForestAdmin\ForestLaravel\Http\Controllers";

    Route::get('forest', $spacename.'\ForestController@index');
    Route::post('forest/sessions', $spacename.'\ForestController@sessions');

    Route::get('forest/{modelName}/{recordId}', $spacename.'\LianaController@getResource');
    Route::get('forest/{modelName}', $spacename.'\LianaController@listResources');
    Route::get('forest/{modelName}/{recordId}/{associationName}', $spacename.'\LianaController@getHasMany');

    Route::post('forest/{modelName}', $spacename.'\LianaController@createResource');
    Route::put('forest/{modelName}/{recordId}', $spacename.'\LianaController@updateResource');

    // Delete ?
//    Route::delete('forest/{modelName}/{recordId}', $spacename.'\LianaController@deleteResource');
});

