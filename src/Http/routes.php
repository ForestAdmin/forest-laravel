<?php

Route::group(['prefix' => 'forest', 'middleware' => 'cors'], function () {
    $namespace = '\ForestAdmin\ForestLaravel\Http\Controllers';
    Route::get('/', $namespace.'\ApimapController@index');
    Route::post('sessions', $namespace.'\SessionController@create');

    Route::group(['middleware' => 'auth.forest'], function () {
        $namespace = '\ForestAdmin\ForestLaravel\Http\Controllers';

        // Records
        Route::get('{modelName}.csv', $namespace.'\ResourcesController@csvExport');
        Route::get('{modelName}', $namespace.'\ResourcesController@index');
        Route::post('{modelName}', $namespace.'\ResourcesController@create');
        Route::get('{modelName}/{recordId}', $namespace.'\ResourcesController@show');
        Route::put('{modelName}/{recordId}', $namespace.'\ResourcesController@update');
        Route::delete('{modelName}/{recordId}', $namespace.'\ResourcesController@destroy');

        // Associations
        Route::get('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@index');
        Route::put('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@update');
        Route::post('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@associate');
        Route::delete('{modelName}/{recordId}/relationships/{associationName}', $namespace.'\AssociationsController@dissociate');

        // Stats
        Route::post('stats/{modelName}', $namespace.'\StatsController@show');
    });
});
