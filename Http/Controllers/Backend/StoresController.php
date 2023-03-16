<?php namespace VaahCms\Modules\Store\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VaahCms\Modules\Store\Models\Store;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahExtend\Facades\VaahCountry;


class StoresController extends Controller
{


    //----------------------------------------------------------
    public function __construct()
    {

    }

    //----------------------------------------------------------

    public function getAssets(Request $request)
    {

        $data = [];



        $data['permission'] = [];

        $data['fillable']['except'] = [
            'uuid',
            'created_by',
            'updated_by',
            'deleted_by',
            'is_multi_currency',
            'is_multi_lingual',
            'is_multi_vendor',
            'is_default',
            'is_active',
        ];

        $model = new Store();
        $fillable = $model->getFillable();
        $data['fillable']['columns'] = array_diff(
            $fillable, $data['fillable']['except']
        );

        foreach ($data['fillable']['columns'] as $column)
        {
            $data['empty_item'][$column] = null;
        }
        $data['empty_item']['is_multi_currency'] = 0;
        $data['empty_item']['is_multi_lingual'] = 0;
        $data['empty_item']['is_multi_vendor'] = 0;
        $data['empty_item']['is_default'] = false;
        $data['empty_item']['is_active'] = true;
        $data['empty_item']['currencies'] = null;
        $data['empty_item']['currency_default'] = null;
        $data['empty_item']['languages'] = null;
        $data['empty_item']['language_default'] = null;

        $data['status'] = Taxonomy::getTaxonomyByType('store-status');

        $data['currencies_list'] = VaahCountry::getListWithCurrency();
        $data['languages_list'] = VaahCountry::getListWithLanguage();

        $data['actions'] = [];

        $response['success'] = true;
        $response['data'] = $data;

        return $response;
    }

    //----------------------------------------------------------
    public function getList(Request $request)
    {
        return Store::getList($request);
    }
    //----------------------------------------------------------
    public function updateList(Request $request)
    {
        return Store::updateList($request);
    }
    //----------------------------------------------------------
    public function deleteList(Request $request)
    {
        return Store::deleteList($request);
    }
    //----------------------------------------------------------
    public function createItem(Request $request)
    {
        return Store::createItem($request);
    }
    //----------------------------------------------------------
    public function getItem(Request $request, $id): array
    {
        return Store::getItem($id);
    }

    //----------------------------------------------------------
    public function updateItem(Request $request,$id)
    {
        return Store::updateItem($request,$id);
    }
    //----------------------------------------------------------
    public function deleteItem(Request $request,$id)
    {
        return Store::deleteItem($request,$id);
    }
    //----------------------------------------------------------
    public function itemAction(Request $request,$id,$action)
    {
        return Store::itemAction($request,$id,$action);
    }
    //----------------------------------------------------------


}
