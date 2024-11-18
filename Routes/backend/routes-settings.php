<?php

Route::group(
    [
        'prefix' => 'backend/store/settings',

        'middleware' => ['web', 'has.backend.access'],

        'namespace' => 'Backend',
],
function () {
    /**
     * Get Assets
     */
    Route::get('/assets', 'SettingsController@getAssets')
        ->name('vh.backend.store.settings.assets');
    /**
     * Get List
     */
    Route::get('/', 'SettingsController@getList')
        ->name('vh.backend.store.settings.list');
    /**
     * Update List
     */
    Route::match(['put', 'patch'], '/', 'SettingsController@updateList')
        ->name('vh.backend.store.settings.list.update');


    Route::any('/fill/bulk/method', 'SettingsController@createBulkRecords')
        ->name('vh.backend.store.settings.create.bulk.records');

    Route::get('/get/all-item/count', 'SettingsController@getItemsCount')
        ->name('vh.backend.store.settings.get.items.count');

    Route::post('/delete/confirm', 'SettingsController@deleteConfirm')
        ->name('vh.backend.store.settings.delete.confirm');

    Route::post('/charts/date-filters', 'SettingsController@storeChartFilterSettings')
        ->name('vh.backend.store.settings.delete.confirm');
});
