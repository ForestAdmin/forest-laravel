<?php

Route::group(['prefix' => 'forest', 'middleware' => \Barryvdh\Cors\HandleCors::class], function () {
    $namespace = '\ForestAdmin\ForestLaravel\Http\Controllers';
    Route::get('/', $namespace.'\ApimapController@index');
    Route::post('sessions', $namespace.'\SessionController@create');
    Route::post('sessions-google', $namespace.'\SessionController@createWithGoogle');

    Route::group(['middleware' => 'auth.forest'], function () {
        $namespace = '\ForestAdmin\ForestLaravel\Http\Controllers';

        // Records
        Route::get('{modelName}.csv', $namespace.'\ResourcesController@exportCSV');
        Route::get('{modelName}', $namespace.'\ResourcesController@index');
        Route::get('{modelName}/count', $namespace.'\ResourcesController@count');
        Route::post('{modelName}', $namespace.'\ResourcesController@create');
        Route::get('{modelName}/{recordId}', $namespace.'\ResourcesController@show');
        Route::put('{modelName}/{recordId}', $namespace.'\ResourcesController@update');
        Route::delete('{modelName}/{recordId}', $namespace.'\ResourcesController@destroy');

        // Associations
        Route::get('{modelName}/{recordId}/relationships/{associationName}.csv', $namespace.'\AssociationsController@exportCSV');
        Route::get('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@index');
        Route::get('{modelName}/{recordId}/relationships/{associationName}/count', $namespace.'\AssociationsController@count');
        Route::put('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@update');
        Route::post('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@associate');
        Route::delete('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@dissociate');

        // Stats
        Route::post('stats/{modelName}', $namespace.'\StatsController@show');
    });
});
