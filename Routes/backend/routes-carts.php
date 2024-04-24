<?php

use VaahCms\Modules\Store\Http\Controllers\Backend\CartsController;

Route::group(
    [
        'prefix' => 'backend/store/carts',
        
        'middleware' => ['web', 'has.backend.access'],
        
],
function () {
    /**
     * Get Assets
     */
    Route::get('/assets', [CartsController::class, 'getAssets'])
        ->name('vh.backend.store.carts.assets');
    /**
     * Get List
     */
    Route::get('/', [CartsController::class, 'getList'])
        ->name('vh.backend.store.carts.list');
    /**
     * Update List
     */
    Route::match(['put', 'patch'], '/', [CartsController::class, 'updateList'])
        ->name('vh.backend.store.carts.list.update');
    /**
     * Delete List
     */
    Route::delete('/', [CartsController::class, 'deleteList'])
        ->name('vh.backend.store.carts.list.delete');


    /**
     * Fill Form Inputs
     */
    Route::any('/fill', [CartsController::class, 'fillItem'])
        ->name('vh.backend.store.carts.fill');

    /**
     * Create Item
     */
    Route::post('/', [CartsController::class, 'createItem'])
        ->name('vh.backend.store.carts.create');
    /**
     * Get Item
     */
    Route::get('/{id}', [CartsController::class, 'getItem'])
        ->name('vh.backend.store.carts.read');
    /**
     * Update Item
     */
    Route::match(['put', 'patch'], '/{id}', [CartsController::class, 'updateItem'])
        ->name('vh.backend.store.carts.update');
    /**
     * Delete Item
     */
    Route::delete('/{id}', [CartsController::class, 'deleteItem'])
        ->name('vh.backend.store.carts.delete');

    /**
     * List Actions
     */
    Route::any('/action/{action}', [CartsController::class, 'listAction'])
        ->name('vh.backend.store.carts.list.actions');

    /**
     * Item actions
     */
    Route::any('/{id}/action/{action}', [CartsController::class, 'itemAction'])
        ->name('vh.backend.store.carts.item.action');

    //---------------------------------------------------------

});
