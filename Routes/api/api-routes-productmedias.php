<?php

/*
 * API url will be: <base-url>/public/api/store/productmedias
 */
Route::group(
    [
        'prefix' => 'store/productmedias',
        'middleware' => ['auth:api'],
        'namespace' => 'Backend',
    ],
function () {

    /**
     * Get Assets
     */
    Route::get('/assets', 'ProductMediasController@getAssets')
        ->name('vh.backend.store.api.productmedias.assets');
    /**
     * Get List
     */
    Route::get('/', 'ProductMediasController@getList')
        ->name('vh.backend.store.api.productmedias.list');
    /**
     * Update List
     */
    Route::match(['put', 'patch'], '/', 'ProductMediasController@updateList')
        ->name('vh.backend.store.api.productmedias.list.update');
    /**
     * Delete List
     */
    Route::delete('/', 'ProductMediasController@deleteList')
        ->name('vh.backend.store.api.productmedias.list.delete');



    /**
     * Get Item
     */
    Route::get('/{id}', 'ProductMediasController@getItem')
        ->name('vh.backend.store.api.productmedias.read');

    /**
     * Delete Item
     */
    Route::delete('/{id}', 'ProductMediasController@deleteItem')
        ->name('vh.backend.store.api.productmedias.delete');

    /**
     * List Actions
     */
    Route::any('/action/{action}', 'ProductMediasController@listAction')
        ->name('vh.backend.store.api.productmedias.list.action');

    /**
     * Item actions
     */
    Route::any('/{id}/action/{action}', 'ProductMediasController@itemAction')
        ->name('vh.backend.store.api.productmedias.item.action');



});
