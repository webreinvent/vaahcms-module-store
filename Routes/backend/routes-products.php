<?php

Route::group(
    [
        'prefix' => 'backend/store/products',

        'middleware' => ['web', 'has.backend.access'],

        'namespace' => 'Backend',
],
function () {
    /**
     * Get Assets
     */
    Route::get('/assets', 'ProductsController@getAssets')
        ->name('vh.backend.store.products.assets');
    /**
     * Get List
     */
    Route::get('/', 'ProductsController@getList')
        ->name('vh.backend.store.products.list');
    /**
     * Update List
     */
    Route::match(['put', 'patch'], '/', 'ProductsController@updateList')
        ->name('vh.backend.store.products.list.update');
    /**
     * Delete List
     */
    Route::delete('/', 'ProductsController@deleteList')
        ->name('vh.backend.store.products.list.delete');

    /**
     * get vendors list
     */
    Route::get('/Vendors_list', 'ProductsController@getVendorsList')
        ->name('vh.backend.store.products.getVendorsList');

    /**
     * create vendor
     */
    Route::post('/vendor', 'ProductsController@createVendor')
        ->name('vh.backend.store.products.vendor');
    /**
     * POST get attribute list
     */
    Route::post('/getAttributeList', 'ProductsController@getAttributeList')
        ->name('vh.backend.store.products.getAttributeList');

    /**
     * POST get attribute values
     */
    Route::post('/getAttributeValue', 'ProductsController@getAttributeValue')
        ->name('vh.backend.store.products.getAttributeValue');

    /**
     * POST create variation
     */
    Route::post('/variation', 'ProductsController@createVariation')
        ->name('vh.backend.store.products.createVariation');


    /**
     * Fill Form Inputs
     */
    Route::any('/fill', 'ProductsController@fillItem')
        ->name('vh.backend.store.products.fill');

    /**
     * Create Item
     */
    Route::post('/', 'ProductsController@createItem')
        ->name('vh.backend.store.products.create');
    /**
     * Get Item
     */
    Route::get('/{id}', 'ProductsController@getItem')
        ->name('vh.backend.store.products.read');
    /**
     * Update Item
     */
    Route::match(['put', 'patch'], '/{id}', 'ProductsController@updateItem')
        ->name('vh.backend.store.products.update');
    /**
     * Delete Item
     */
    Route::delete('/{id}', 'ProductsController@deleteItem')
        ->name('vh.backend.store.products.delete');

    /**
     * List Actions
     */
    Route::any('/action/{action}', 'ProductsController@listAction')
        ->name('vh.backend.store.products.list.actions');

    /**
     * Item actions
     */
    Route::any('/{id}/action/{action}', 'ProductsController@itemAction')
        ->name('vh.backend.store.products.item.action');

    /**
     * Search Store
     */
    Route::post('/search/store', 'ProductsController@searchStore')
        ->name('vh.backend.store.products.search.store');

    /**
     * Search Brand
     */
    Route::post('/search/brand', 'ProductsController@searchBrand')
        ->name('vh.backend.store.products.search.brand');

    /** Remove vendor
    */
        Route::get('/{id}/remove/vendor', 'ProductsController@removeVendor')
            ->name('vh.backend.store.products.remove.vendor');

    /**
     * Remove All Vendor
     */
    Route::get('/{id}/bulk-remove/vendor', 'ProductsController@bulkRemoveVendor')
        ->name('vh.backend.store.products.remove.bulk.vendor');

    /**
     * Search Product variation
     */
    Route::post('/search/product-variation', 'ProductsController@searchProductVariation')
        ->name('vh.backend.store.products.search.productvariation');

    /**
     * Search Product vendor
     */
    Route::post('/search/product-vendor', 'ProductsController@searchProductVendor')
        ->name('vh.backend.store.products.search.productvendor');

    /**
     * Search Vendors using Slug
     */
    Route::post('/search/vendors-using-slug', 'ProductsController@searchVendorUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-vendors');

    //---------------------------------------------------------

    /**
     * Search Brands using Slug
     */
    Route::post('/search/brands-using-slug', 'ProductsController@searchBrandUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-brands');

    //---------------------------------------------------------
    /**
     * Search Variations using Slug
     */
    Route::post('/search/variations-using-slug', 'ProductsController@searchVariationUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-variations');

    //---------------------------------------------------------
    /**
     * Search Stores using Slug
     */
    Route::post('/search/stores-using-slug', 'ProductsController@searchStoreUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-stores');


    //---------------------------------------------------------

    /**
     * Search Product Type using Slug
     */
    Route::post('/search/product-types-using-slug', 'ProductsController@searchProductTypeUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-product-types');

    //---------------------------------------------------------

    /**
     * Search Product Type using Slug
     */
    Route::post('/search/product-types-using-slug', 'ProductsController@searchProductTypeUsingUrlSlug')
        ->name('vh.backend.store.products.search.filtered-product-types');

    /**
     * Search vendor
     */
    Route::post('/search/vendor', 'ProductsController@searchVendor')
        ->name('vh.backend.store.products.search.vendor');

    Route::any('/get/default/store', 'ProductsController@defaultStore')
        ->name('vh.backend.store.products.get.default.store');

    //---------------------------------------------------------
    Route::any('/category/{action}', 'ProductsController@deleteCategory')
        ->name('vh.backend.store.products.get.default.store');

    //---------------------------------------------------------
    Route::post('/search/category-using-slug', 'ProductsController@searchCategoryUsingSlug')
        ->name('vh.backend.store.products.search.filtered-category');


    //---------------------------------------------------------
    Route::get('/get-vendors-list/{id}', 'ProductsController@getVendorsListForPrduct')
        ->name('vh.backend.store.products.get.vendors-list');

    Route::patch('/{id}/action-for-vendor/{action}', 'ProductsController@vendorPreferredAction')
        ->name('vh.backend.store.products.preferred-vendor');

    //---------------------------------------------------------
    /**
     * Search customer-users for add to cart
     */
    Route::post('/search/user', 'ProductsController@searchUsers')
        ->name('vh.backend.store.products.search.user-for-cart');
    //---------------------------------------------------------
    /**
     * Add product to cart
     */
    Route::post('/add/product-to-cart', 'ProductsController@addProductToCart')
        ->name('vh.backend.store.products.save.user-info');

    //---------------------------------------------------------
    /**
     * Disable active cart session
     */
    Route::post('/disable/active-cart', 'ProductsController@disableActiveCart')
        ->name('vh.backend.store.products.disable.active-cart');

    //---------------------------------------------------------
    /**
     * get categories
     */
    //---------------------------------------------------------
    Route::get('/get/categories', 'ProductsController@getCategories')
        ->name('vh.backend.store.products.get.categories');

    /**
     * Top Selling products
     */
    Route::get('/charts/top-selling-products', 'ProductsController@topSellingProducts')
        ->name('vh.backend.store.users.count-chart-data');

    /**
     * Top Brands By product
     */
    Route::get('/charts/top-selling-brands', 'ProductsController@topSellingBrands')
        ->name('vh.backend.store.users.count-chart-data');

});
