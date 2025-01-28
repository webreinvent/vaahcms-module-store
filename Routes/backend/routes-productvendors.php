<?php

Route::group(
    [
        'prefix' => 'backend/store/productvendors',

        'middleware' => ['web', 'has.backend.access'],

        'namespace' => 'Backend',
],
function () {
    /**
     * Get Assets
     */
    Route::get('/assets', 'ProductVendorsController@getAssets')
        ->name('vh.backend.store.productvendors.assets');
    /**
     * Get List
     */
    Route::get('/', 'ProductVendorsController@getList')
        ->name('vh.backend.store.productvendors.list');
    /**
     * Update List
     */
    Route::match(['put', 'patch'], '/', 'ProductVendorsController@updateList')
        ->name('vh.backend.store.productvendors.list.update');
    /**
     * Delete List
     */
    Route::delete('/', 'ProductVendorsController@deleteList')
        ->name('vh.backend.store.productvendors.list.delete');

    /**
     * POST create productPrice
     */
    Route::post('/{id}/prices', 'ProductVendorsController@addProductPrices')
        ->name('vh.backend.store.products.createProductPrice');
    /**
     * POST Product list for store
     */
    Route::post('/products', 'ProductVendorsController@productForStore')
        ->name('vh.backend.store.productvendors.list.productForStore');

    /**
     * Fill Form Inputs
     */
    Route::any('/fill', 'ProductVendorsController@fillItem')
        ->name('vh.backend.store.productvendors.fill');

    /**
     * Create Item
     */
    Route::post('/', 'ProductVendorsController@createItem')
        ->name('vh.backend.store.productvendors.create');
    /**
     * Get Item
     */
    Route::get('/{id}', 'ProductVendorsController@getItem')
        ->name('vh.backend.store.productvendors.read');
    /**
     * Update Item
     */
    Route::match(['put', 'patch'], '/{id}', 'ProductVendorsController@updateItem')
        ->name('vh.backend.store.productvendors.update');
    /**
     * Delete Item
     */
    Route::delete('/{id}', 'ProductVendorsController@deleteItem')
        ->name('vh.backend.store.productvendors.delete');

    /**
     * List Actions
     */
    Route::any('/action/{action}', 'ProductVendorsController@listAction')
        ->name('vh.backend.store.productvendors.list.actions');

    /**
     * Item actions
     */
    Route::any('/{id}/action/{action}', 'ProductVendorsController@itemAction')
        ->name('vh.backend.store.productvendors.item.action');

    /**
     * Search product
     */
    Route::any('/search/product', 'ProductVendorsController@searchProduct')
        ->name('vh.backend.store.productvendors.search.product');

    /**
     * Search vendor
     */
    Route::any('/search/vendor', 'ProductVendorsController@searchVendor')
        ->name('vh.backend.store.productvendors.search.vendor');

    /**
     * Search added by
     */
    Route::any('/search/added/by', 'ProductVendorsController@searchAddedBy')
        ->name('vh.backend.store.productvendors.search.Added');

    /**
     * Search status
     */
    Route::any('/search/status', 'ProductVendorsController@searchStatus')
        ->name('vh.backend.store.productvendors.search.status');

    /**
     * Search Active Stores
     */
    Route::any('/search/active-store', 'ProductVendorsController@searchActiveStores')
        ->name('vh.backend.store.productvendors.search.active-store');

    /**
     * Search Products for filter
     */
    Route::post('/filter/search/product', 'ProductVendorsController@getProduct')
        ->name('vh.backend.store.productvendors.search.filter.product');

    /**
     * Search Products after refresh
     */
    Route::post('/search/products-by-slug', 'ProductVendorsController@getProductsBySlug')
        ->name('vh.backend.store.productvendors.search.filter-product-by-slug');


    /**
     * search variations of product
     */

    Route::post('/search/product-variation', 'ProductVendorsController@searchVariationOfProduct')
        ->name('vh.backend.store.productvendors.search.product-variation');

    Route::any('/get/default/values', 'ProductVendorsController@getDefaultValues')
        ->name('vh.backend.store.productvendors.search.default.values');
});
