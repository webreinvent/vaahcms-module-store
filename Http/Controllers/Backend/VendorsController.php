<?php namespace VaahCms\Modules\Store\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VaahCms\Modules\Store\Models\Product;
use VaahCms\Modules\Store\Models\Vendor;
use VaahCms\Modules\Store\Models\Store;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahCms\Entities\User;


class VendorsController extends Controller
{


    //----------------------------------------------------------
    public function __construct()
    {

    }

    //----------------------------------------------------------

    public function getAssets(Request $request)
    {

        try{

            $data = [];

            $data['permission'] = [];
            $data['rows'] = config('vaahcms.per_page');

            $data['fillable']['except'] = [
                'uuid',
                'created_by',
                'updated_by',
                'deleted_by',
            ];

            $model = new Vendor();
            $fillable = $model->getFillable();
            $data['fillable']['columns'] = array_diff(
                $fillable, $data['fillable']['except']
            );

            foreach ($fillable as $column)
            {
                $data['empty_item'][$column] = null;
            }

            $data['taxonomy']['status'] = Taxonomy::getTaxonomyByType('vendor-status');

            $data['empty_item']['is_default'] = 0;
            $data['empty_item']['is_active'] = 1;
            $data['empty_item']['auto_approve_products'] = 0;
            $data['empty_item']['products'] = [];
            $data['actions'] = [];

            $active_stores = $this->getActiveStores();
            $active_users = $this->getActiveUsers();
            $active_products = $this->getActiveProducts();

            $data = array_merge($data, $active_stores, $active_users, $active_products);

            // set default value's
            $data['default_product'] = $this->getDefaultProduct();
            $data['empty_item']['vh_st_store_id'] = $this->getDefaultStore();
            $data['empty_item']['approved_by'] = $this->getActiveUser();

            // get taxonomy data's
            $data['product_vendor_status'] = Taxonomy::getTaxonomyByType('product-vendor-status');
            $data['status'] = Taxonomy::getTaxonomyByType('vendor-status');

            $response['success'] = true;
            $response['data'] = $data;

        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
            }
        }

        return $response;
    }

    //----------------------------------------------------------
    public function getActiveStores(){
        $stores = Store::where('is_active',1)->get(['id','name', 'slug', 'is_default']);
        if ($stores){
            return [
                'active_stores' =>$stores
            ];
        }else{
            return [
                'active_stores' => null
            ];
        }
    }

    //----------------------------------------------------------
    public function getActiveUsers(){
        $users = User::where('is_active',1)->get(['id','first_name','email']);
        if ($users){
            return [
                'active_users' =>$users
            ];
        }else{
            return [
                'active_users' => null
            ];
        }
    }

    //----------------------------------------------------------
    public function getActiveProducts(){
        $active_products = Product::where('is_active', 1)->get(['id','name','slug','is_default']);
        if ($active_products){
            return [
                'active_products' =>$active_products
            ];
        }else{
            return [
                'active_products' => null
            ];
        }
    }

    //----------------------------------------------------------
    public function getActiveUser(){
        $active_user = auth()->user();

        return [
            'id' => $active_user->id,
            'first_name' => $active_user->first_name,
            'email' => $active_user->email,
        ];
    }

    //----------------------------------------------------------
    public function getDefaultStore(){
        return Store::where(['is_active' => 1, 'is_default' => 1])->get(['id','name', 'slug', 'is_default'])->first();
    }

    //----------------------------------------------------------
    public function getDefaultProduct(){
        return Product::where(['is_active' => 1, 'is_default' => 1])->get(['id','name', 'slug', 'is_default'])->first();
    }

    //----------------------------------------------------------
    public function createProduct(Request $request)
    {
        try{
            return Vendor::createProduct($request);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }

    //----------------------------------------------------------
    public function getList(Request $request)
    {
        try{
            return Vendor::getList($request);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function updateList(Request $request)
    {
        try{
            return Vendor::updateList($request);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function listAction(Request $request, $type)
    {


        try{
            return Vendor::listAction($request, $type);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function deleteList(Request $request)
    {
        try{
            return Vendor::deleteList($request);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function createItem(Request $request)
    {
        try{
            return Vendor::createItem($request);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function getItem(Request $request, $id)
    {
        try{
            return Vendor::getItem($id);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function updateItem(Request $request,$id)
    {
        try{
            return Vendor::updateItem($request,$id);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function deleteItem(Request $request,$id)
    {
        try{
            return Vendor::deleteItem($request,$id);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------
    public function itemAction(Request $request,$id,$action)
    {
        try{
            return Vendor::itemAction($request,$id,$action);
        }catch (\Exception $e){
            $response = [];
            $response['status'] = 'failed';
            if(env('APP_DEBUG')){
                $response['errors'][] = $e->getMessage();
                $response['hint'] = $e->getTrace();
            } else{
                $response['errors'][] = 'Something went wrong.';
                return $response;
            }
        }
    }
    //----------------------------------------------------------


}
