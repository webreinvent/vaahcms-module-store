<?php namespace VaahCms\Modules\Store\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use WebReinvent\VaahCms\Entities\Taxonomy;
use Faker\Factory;
use WebReinvent\VaahCms\Models\VaahModel;
use WebReinvent\VaahCms\Traits\CrudWithUuidObservantTrait;
use WebReinvent\VaahCms\Models\User;
use WebReinvent\VaahCms\Libraries\VaahSeeder;
use WebReinvent\VaahCms\Models\TaxonomyType;

class StorePaymentMethod extends VaahModel
{

    use SoftDeletes;
    use CrudWithUuidObservantTrait;

    //-------------------------------------------------
    protected $table = 'vh_st_store_payment_methods';
    //-------------------------------------------------
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    //-------------------------------------------------
    protected $fillable = [
        'uuid',
        'taxonomy_id_payment_status',
        'vh_st_store_id',
        'vh_st_payment_method_id',
        'is_active',
        'last_payment_at',
        'status_notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    //-------------------------------------------------
    protected $fill_except = [

    ];

    //-------------------------------------------------
    protected $appends = [
    ];

    //-------------------------------------------------
    protected function serializeDate(DateTimeInterface $date)
    {
        $date_time_format = config('settings.global.datetime_format');
        return $date->format($date_time_format);
    }
    //-------------------------------------------------
    public function store()
    {
        return $this->hasOne(Store::class,'id','vh_st_store_id')
            ->select('id','name','slug');
    }
    //-------------------------------------------------
    public function status()
    {
        return $this->hasOne(Taxonomy::class,'id','taxonomy_id_payment_status')
            ->select('id','name','slug');
    }
    //-------------------------------------------------
    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class,'id','vh_st_payment_method_id')
            ->select('id','name','slug');
    }

    //-------------------------------------------------
    public static function getUnFillableColumns()
    {
        return [
            'uuid',
            'created_by',
            'updated_by',
            'deleted_by',
        ];
    }
    //-------------------------------------------------
    public static function getFillableColumns()
    {
        $model = new self();
        $except = $model->fill_except;
        $fillable_columns = $model->getFillable();
        $fillable_columns = array_diff(
            $fillable_columns, $except
        );
        return $fillable_columns;
    }
    //-------------------------------------------------
    public static function getEmptyItem()
    {
        $model = new self();
        $fillable = $model->getFillable();
        $empty_item = [];
        foreach ($fillable as $column)
        {
            $empty_item[$column] = null;
        }

        return $empty_item;
    }

    //-------------------------------------------------

    public function createdByUser()
    {
        return $this->belongsTo(User::class,
            'created_by', 'id'
        )->select('id', 'uuid', 'first_name', 'last_name', 'email');
    }

    //-------------------------------------------------
    public function updatedByUser()
    {
        return $this->belongsTo(User::class,
            'updated_by', 'id'
        )->select('id', 'uuid', 'first_name', 'last_name', 'email');
    }

    //-------------------------------------------------
    public function deletedByUser()
    {
        return $this->belongsTo(User::class,
            'deleted_by', 'id'
        )->select('id', 'uuid', 'first_name', 'last_name', 'email');
    }

    //-------------------------------------------------
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()
            ->getColumnListing($this->getTable());
    }

    //-------------------------------------------------
    public function scopeExclude($query, $columns)
    {
        return $query->select(array_diff($this->getTableColumns(), $columns));
    }

    //-------------------------------------------------
    public function scopeBetweenDates($query, $from, $to)
    {

        if ($from) {
            $from = \Carbon::parse($from)
                ->startOfDay()
                ->toDateTimeString();
        }

        if ($to) {
            $to = \Carbon::parse($to)
                ->endOfDay()
                ->toDateTimeString();
        }

        $query->whereBetween('updated_at', [$from, $to]);
    }

    //-------------------------------------------------
    public static function createItem($request)
    {

        $inputs = $request->all();

        $validation = self::validation($inputs);
        if (!$validation['success']) {
            return $validation;
        }


        // check if exist
        $item = self::where('vh_st_store_id', $inputs['vh_st_store_id'])
            ->where('vh_st_payment_method_id', $inputs['vh_st_payment_method_id'])
            ->withTrashed()
            ->first();

        if ($item) {
            $response['success'] = false;
            $response['errors'][] = "This payment method is already exist for this store.";
            return $response;
        }

        $item = new self();
        $item->fill($inputs);
        $item->save();

        $response = self::getItem($item->id);
        $response['messages'][] = 'Saved successfully.';
        return $response;

    }

    //-------------------------------------------------
    public function scopeGetSorted($query, $filter)
    {

        if(!isset($filter['sort']))
        {
            return $query->orderBy('id', 'desc');
        }

        $sort = $filter['sort'];


        $direction = Str::contains($sort, ':');

        if(!$direction)
        {
            return $query->orderBy($sort, 'asc');
        }

        $sort = explode(':', $sort);

        return $query->orderBy($sort[0], $sort[1]);
    }
    //-------------------------------------------------
    public function scopeIsActiveFilter($query, $filter)
    {

        if(!isset($filter['is_active'])
            || is_null($filter['is_active'])
            || $filter['is_active'] === 'null'
        )
        {
            return $query;
        }
        $is_active = $filter['is_active'];

        if($is_active === 'true' || $is_active === true)
        {
            return $query->where('is_active', 1);
        } else{
            return $query->where(function ($q){
                $q->whereNull('is_active')
                    ->orWhere('is_active', 0);
            });
        }

    }
    //-------------------------------------------------
    public function scopeTrashedFilter($query, $filter)
    {

        if(!isset($filter['trashed']))
        {
            return $query;
        }
        $trashed = $filter['trashed'];

        if($trashed === 'include')
        {
            return $query->withTrashed();
        } else if($trashed === 'only'){
            return $query->onlyTrashed();
        }

    }
    //-------------------------------------------------
    public function scopeSearchFilter($query, $filter)
    {
        if(!isset($filter['q']))
        {
            return $query;
        }
        $search = $filter['q'];
        $query->where(function ($q) use ($search) {
            $q->where('id', 'LIKE', '%' . $search . '%')
                ->orWhereHas('store', function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $search . '%');
                });
        });

    }
    //-------------------------------------------------
    public function scopeStatusFilter($query, $filter)
    {

        if(!isset($filter['payment_status']))
        {
            return $query;
        }
        $search = $filter['payment_status'];
        $query->whereHas('status' , function ($q) use ($search){
            $q->whereIn('name' ,$search);
        });
    }
    //-------------------------------------------------
    public function scopePayTypeFilter($query, $filter)
    {

        if(!isset($filter['payment_type']))
        {
            return $query;
        }
        $search = $filter['payment_type'];
        $query->whereHas('paymentMethod',function ($q) use ($search) {
            $q->whereIn('name',$search);
        });

    }
    //-------------------------------------------------
    public static function getList($request)
    {
        $list = self::getSorted($request->filter)->with('store','paymentMethod','status');
        $list->isActiveFilter($request->filter);
        $list->trashedFilter($request->filter);
        $list->searchFilter($request->filter);
        $list->statusFilter($request->filter);
        $list->payTypeFilter($request->filter);

        $rows = config('vaahcms.per_page');

        if($request->has('rows'))
        {
            $rows = $request->rows;
        }

        $list = $list->paginate($rows);

        $response['success'] = true;
        $response['data'] = $list;

        return $response;


    }

    //-------------------------------------------------
    public static function updateList($request)
    {

        $inputs = $request->all();

        $rules = array(
            'type' => 'required',
        );

        $messages = array(
            'type.required' => 'Action type is required',
        );


        $validator = \Validator::make($inputs, $rules, $messages);
        if ($validator->fails()) {

            $errors = errorsToArray($validator->errors());
            $response['success'] = false;
            $response['errors'] = $errors;
            return $response;
        }

        if(isset($inputs['items']))
        {
            $items_id = collect($inputs['items'])
                ->pluck('id')
                ->toArray();
        }


        $items = self::whereIn('id', $items_id)
            ->withTrashed();

        switch ($inputs['type']) {
            case 'deactivate':
                $items->update(['is_active' => null]);
                break;
            case 'activate':
                $items->update(['is_active' => 1]);
                break;
            case 'trash':
                self::whereIn('id', $items_id)->delete();
                $items->update(['deleted_by' => auth()->user()->id]);
                break;
            case 'restore':
                self::whereIn('id', $items_id)->restore();
                $items->update(['deleted_by' => null]);
                break;
        }

        $response['success'] = true;
        $response['data'] = true;
        $response['messages'][] = 'Action was successful.';

        return $response;
    }

    //-------------------------------------------------
    public static function deleteList($request): array
    {
        $inputs = $request->all();

        $rules = array(
            'type' => 'required',
            'items' => 'required',
        );

        $messages = array(
            'type.required' => 'Action type is required',
            'items.required' => 'Select items',
        );

        $validator = \Validator::make($inputs, $rules, $messages);
        if ($validator->fails()) {

            $errors = errorsToArray($validator->errors());
            $response['success'] = false;
            $response['errors'] = $errors;
            return $response;
        }

        $items_id = collect($inputs['items'])->pluck('id')->toArray();
        self::whereIn('id', $items_id)->forceDelete();

        $response['success'] = true;
        $response['data'] = true;
        $response['messages'][] = 'Action was successful.';

        return $response;
    }
    //-------------------------------------------------
    public static function listAction($request, $type): array
    {
        $inputs = $request->all();

        if(isset($inputs['items']))
        {
            $items_id = collect($inputs['items'])
                ->pluck('id')
                ->toArray();

            $items = self::whereIn('id', $items_id)
                ->withTrashed();
        }

        $list = self::query();

        if($request->has('filter')){
            $list->getSorted($request->filter);
            $list->isActiveFilter($request->filter);
            $list->trashedFilter($request->filter);
            $list->searchFilter($request->filter);
        }

        switch ($type) {
            case 'deactivate':
                if($items->count() > 0) {
                    $items->update(['is_active' => null]);
                }
                break;
            case 'activate':
                if($items->count() > 0) {
                    $items->update(['is_active' => 1]);
                }
                break;
            case 'trash':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->delete();
                    $items->update(['deleted_by' => auth()->user()->id]);
                }
                break;
            case 'restore':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->restore();
                    $items->update(['deleted_by' => null]);
                }
                break;
            case 'delete':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->forceDelete();
                }
                break;
            case 'activate-all':
                $list->update(['is_active' => 1]);
                break;
            case 'deactivate-all':
                $list->update(['is_active' => null]);
                break;
            case 'trash-all':
                $list->update(['deleted_by'  => auth()->user()->id]);
                $list->delete();
                break;
            case 'restore-all':
                $list->restore();
                $list->update(['deleted_by'  => null]);
                break;
            case 'delete-all':
                $list->forceDelete();
                break;
            case 'create-100-records':
            case 'create-1000-records':
            case 'create-5000-records':
            case 'create-10000-records':

            if(!config('store.is_dev')){
                $response['success'] = false;
                $response['errors'][] = 'User is not in the development environment.';

                return $response;
            }

            preg_match('/-(.*?)-/', $type, $matches);

            if(count($matches) !== 2){
                break;
            }

            self::seedSampleItems($matches[1]);
            break;
        }

        $response['success'] = true;
        $response['data'] = true;
        $response['messages'][] = 'Action was successful.';

        return $response;
    }
    //-------------------------------------------------
    public static function getItem($id)
    {

        $item = self::where('id', $id)
            ->with(['createdByUser', 'updatedByUser', 'deletedByUser','store','paymentMethod','status'])
            ->withTrashed()
            ->first();

        if(!$item)
        {
            $response['success'] = false;
            $response['errors'][] = 'Record not found with ID: '.$id;
            return $response;
        }
        $response['success'] = true;
        $response['data'] = $item;

        return $response;

    }
    //-------------------------------------------------
    public static function updateItem($request, $id)
    {
        $inputs = $request->all();

        $validation = self::validation($inputs);
        if (!$validation['success']) {
            return $validation;
        }

        // check if exist
        $item = self::where('id', '!=', $inputs['id'])
            ->withTrashed()
            ->where('vh_st_store_id', $inputs['vh_st_store_id'])
            ->where('vh_st_payment_method_id', $inputs['vh_st_payment_method_id'])
            ->first();

        if ($item) {
            $response['success'] = false;
            $response['errors'][] = "This payment method is already exist for this store.";
            return $response;
        }

        $item = self::where('id', $id)->withTrashed()->first();
        $item->fill($inputs);
        $item->save();

        $response = self::getItem($item->id);
        $response['messages'][] = 'Saved successfully.';
        return $response;

    }
    //-------------------------------------------------
    public static function deleteItem($request, $id): array
    {
        $item = self::where('id', $id)->withTrashed()->first();
        if (!$item) {
            $response['success'] = false;
            $response['errors'][] = 'Record does not exist.';
            return $response;
        }
        $item->forceDelete();

        $response['success'] = true;
        $response['data'] = [];
        $response['messages'][] = 'Record has been deleted';

        return $response;
    }
    //-------------------------------------------------
    public static function itemAction($request, $id, $type): array
    {
        switch($type)
        {
            case 'activate':
                self::where('id', $id)
                    ->withTrashed()
                    ->update(['is_active' => 1]);
                break;
            case 'deactivate':
                self::where('id', $id)
                    ->withTrashed()
                    ->update(['is_active' => null]);
                break;
            case 'trash':
                self::where('id', $id)
                ->withTrashed()
                ->delete();
                $item = self::where('id',$id)->withTrashed()->first();
                if($item->delete()) {
                    $item->deleted_by = auth()->user()->id;
                    $item->save();
                }
                break;
            case 'restore':
                self::where('id', $id)
                    ->withTrashed()
                    ->restore();
                    $item = self::where('id',$id)->withTrashed()->first();
                    $item->deleted_by = null;
                    $item->save();
                break;
        }

        return self::getItem($id);
    }
    //-------------------------------------------------

    public static function validation($inputs)
    {

        $rules = array(
            'vh_st_store_id'=> 'required',
            'vh_st_payment_method_id'=> 'required',
            'last_payment_at'=> 'required',
            'taxonomy_id_payment_status'=> 'required',
            'status_notes' => [
                'required_if:status.slug,==,rejected',
                'max:250'
            ],
        );

        $customMessages = array(
            'vh_st_store_id.required' => 'The Store field is required',
            'vh_st_payment_method_id.required' => 'The Payment Method field is required',
            'last_payment_at.required' => 'The Last Payment at field is required',
            'taxonomy_id_payment_status.required' => 'The Status field is required',
            'status_notes.required_if' => 'The Status notes field is required for "Rejected" Status',
            'status_notes.max' => 'The Status notes field may not be greater than :max characters.',
        );

        $validator = \Validator::make($inputs, $rules,$customMessages);
        if ($validator->fails()) {
            $messages = $validator->errors();
            $response['success'] = false;
            $response['errors'] = $messages->all();
            return $response;
        }

        $response['success'] = true;
        return $response;

    }

    //-------------------------------------------------
    public static function getActiveItems()
    {
        $item = self::where('is_active', 1)
            ->withTrashed()
            ->first();
        return $item;
    }

    //-------------------------------------------------
    //-------------------------------------------------
    public static function seedSampleItems($records=100)
    {

        $i = 0;

        while($i < $records)
        {
            $inputs = self::fillItem(false);

            $item =  new self();
            $item->fill($inputs);
            $item->save();

            $i++;

        }

    }


    //-------------------------------------------------
    public static function fillItem($is_response_return = true)
    {
        $request = new Request([
            'model_namespace' => self::class,
            'except' => self::getUnFillableColumns()
        ]);
        $fillable = VaahSeeder::fill($request);
        if(!$fillable['success']){
            return $fillable;
        }
        $inputs = $fillable['data']['fill'];


        $store_id = Store::where('is_active', 1)->inRandomOrder()->value('id');
        $store_id_data = Store::where('is_active',1)->where('id',$store_id)->first();
        $inputs['vh_st_store_id'] =$store_id;
        $inputs['store'] = $store_id_data;

        $start_date = Carbon::create(2022, 1, 1);
        $random_date = $start_date->copy()->addSeconds(mt_rand(0, Carbon::now()->diffInSeconds($start_date)));
        $inputs['last_payment_at'] = $random_date->toDateTimeString();


        $pay_id = PaymentMethod::where('is_active', 1)->inRandomOrder()->value('id');
        $pay_id_data = PaymentMethod::where('is_active',1)->where('id',$pay_id)->first();
        $inputs['vh_st_payment_method_id'] =$pay_id;
        $inputs['payment_method'] = $pay_id_data;

        $taxonomy_status = Taxonomy::getTaxonomyByType('payment-methods-status');
        $status_id = $taxonomy_status->pluck('id')->random();
        $status = $taxonomy_status->where('id',$status_id)->first();
        $inputs['taxonomy_id_payment_status'] = $status_id;
        $inputs['status']=$status;



        $inputs['is_active'] = rand(0,1);

        $faker = Factory::create();

        /*
         * You can override the filled variables below this line.
         * You should also return relationship from here
         */

        if(!$is_response_return){
            return $inputs;
        }

        $response['success'] = true;
        $response['data']['fill'] = $inputs;
        return $response;
    }

    //-------------------------------------------------
    public static function deleteStores($items_id){

        if($items_id){
            self::where('vh_st_store_id',$items_id)->forcedelete();
            $response['success'] = true;
            $response['data'] = true;
        }else{
            $response['error'] = true;
            $response['data'] = false;
        }

    }
    //-------------------------------------------------
    public static function searchStore($request){

        $store = Store::select('id', 'name')->where('is_active',1);
        if ($request->has('query') && $request->input('query')) {
            $store->where('name', 'LIKE', '%' . $request->input('query') . '%');
        }
        $store = $store->limit(10)->get();

        $response['success'] = true;
        $response['data'] = $store;
        return $response;

    }
    //-------------------------------------------------
    public static function searchPaymentMethod($request){

        $store = PaymentMethod::select('id', 'name')->where('is_active',1);
        if ($request->has('query') && $request->input('query')) {
            $store->where('name', 'LIKE', '%' . $request->input('query') . '%');
        }
        $store = $store->limit(10)->get();

        $response['success'] = true;
        $response['data'] = $store;
        return $response;

    }
    //-------------------------------------------------
    public static function searchStatus($request){

        $query = $request->input('query');
        if(empty($query)) {
            $item = Taxonomy::getTaxonomyByType('payment-methods-status');
        } else {
            $tax_type = TaxonomyType::getFirstOrCreate('payment-methods-status');

            $item =array();

            if(!$tax_type){
                return $item;
            }
            $item = Taxonomy::whereNotNull('is_active')
                ->where('vh_taxonomy_type_id',$tax_type->id)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->get();
        }

        $response['success'] = true;
        $response['data'] = $item;
        return $response;

    }
    //-------------------------------------------------


}
