<?php namespace VaahCms\Modules\Store\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahCms\Traits\CrudWithUuidObservantTrait;
use WebReinvent\VaahCms\Entities\User;

class Vendor extends Model
{

    use SoftDeletes;
    use CrudWithUuidObservantTrait;

    //-------------------------------------------------
    protected $table = 'vh_st_vendors';
    //-------------------------------------------------
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    //-------------------------------------------------
    protected $fillable = [
        'uuid',
        'vh_st_store_id', 'name', 'slug',
        'owned_by', 'registered_at',
        'auto_approve_products', 'approved_by',
        'approved_at', 'is_default', 'is_active',
        'taxonomy_id_vendor_status', 'status_notes', 'meta',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
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
    public function store(){
        return $this->hasOne(Store::class, 'id', 'vh_st_store_id')->select(['id','name', 'is_default','slug']);
    }

    //-------------------------------------------------
    public function approvedBy(){
        return $this->hasOne(User::class, 'id', 'approved_by')->select(['id','first_name','email']);
    }

    //-------------------------------------------------
    public function ownedBy(){
        return $this->hasOne(User::class, 'id', 'owned_by')->select(['id','first_name', 'email']);
    }

    //-------------------------------------------------
    public function status(){
        return $this->hasOne(Taxonomy::class, 'id', 'taxonomy_id_vendor_status')->select(['id','name','slug']);
    }
    //-------------------------------------------------
    public static function createItem($request)
    {

        $validation_result = self::vendorInputValidator($request->all());

        if ($validation_result['success'] != true){
            return $validation_result;
        }

        $inputs = $validation_result['data'];
        $item = new self();
        $item->fill($inputs);
        $item->name = $inputs['name'];
        $item->slug = Str::slug($inputs['slug']);
        $item->auto_approve_products  = $inputs['auto_approve_products'];
        $item->vh_st_store_id  = $inputs['vh_st_store_id']['id'];
        $item->owned_by  = $inputs['owned_by']['id'];
        $item->approved_by = $inputs['approved_by']['id'];
        $item->is_default = $inputs['is_default'];
        $item->taxonomy_id_vendor_status = $inputs['taxonomy_id_vendor_status']['id'];
        $item->status_notes = $inputs['status_notes'];
        $item->is_active = $inputs['is_active'];
        $item->registered_at = \Carbon\Carbon::now()->toDateTimeString();
        $item->approved_at = \Carbon\Carbon::now()->toDateTimeString();
//        dd($item);
        $item->save();

        $response = self::getItem($item->id);
        $response['messages'][] = 'Saved successfully.';
        return $response;

    }

    //-------------------------------------------------
    public static function vendorInputValidator($requestData){

        $validated_data = validator($requestData, [
            'name' => 'required',
            'slug' => 'required',
            'vh_st_store_id' => 'required',
            'owned_by' => 'required',
            'auto_approve_products' => 'required',
            'approved_by' => 'required',
            'is_default' => 'required',
            'taxonomy_id_vendor_status' => 'required',
            'status_notes' => 'required_if:taxonomy_id_vendor_status.slug,==,rejected',
            'is_active' => 'required'
        ],
            [
                'vh_st_store_id.required' => 'The Store field is required',
                'taxonomy_id_vendor_status.required' => 'The Status field is required',
                'status_notes.*' => 'The Status notes field is required for "Rejected" Status',
            ]
        );

        if($validated_data->fails()){
            return [
                'success' => false,
                'errors' => $validated_data->errors()->all()
            ];
        }

        $validated_data = $validated_data->validated();

        return [
            'success' => true,
            'data' => $validated_data
        ];

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
            return $query->whereNotNull('is_active');
        } else{
            return $query->whereNull('is_active');
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
            $q->where('name', 'LIKE', '%' . $search . '%')
                ->orWhere('slug', 'LIKE', '%' . $search . '%');
        });

    }
    //-------------------------------------------------
    public static function getList($request)
    {
        $list = self::getSorted($request->filter)->with(['store', 'approvedBy', 'ownedBy', 'status']);
        $list->isActiveFilter($request->filter);
        $list->trashedFilter($request->filter);
        $list->searchFilter($request->filter);

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
                break;
            case 'restore':
                self::whereIn('id', $items_id)->restore();
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
            $response['failed'] = true;
            $response['messages'] = $errors;
            return $response;
        }

        $items_id = collect($inputs['items'])->pluck('id')->toArray();
        self::whereIn('id', $items_id)->forceDelete();
        ProductVendor::deleteVendors($items_id);

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
                }
                break;
            case 'restore':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->restore();
                }
                break;
            case 'delete':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->forceDelete();
                    ProductVendor::deleteVendors($items_id);
                }
                break;
            case 'activate-all':
                self::query()->update(['is_active' => 1]);
                break;
            case 'deactivate-all':
                self::query()->update(['is_active' => null]);
                break;
            case 'trash-all':
                self::query()->delete();
                break;
            case 'restore-all':
                self::withTrashed()->restore();
                break;
            case 'delete-all':
                $items_id = self::all()->pluck('id')->toArray();
                self::withTrashed()->forceDelete();
                ProductVendor::deleteVendors($items_id);
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
            ->with(['createdByUser', 'updatedByUser', 'deletedByUser', 'store', 'approvedBy','ownedBy', 'status'])
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
        $validation_result = self::vendorInputValidator($request->all());

        if ($validation_result['success'] != true){
            return $validation_result;
        }

        $inputs = $validation_result['data'];

        $item = self::where('id', $id)->withTrashed()->first();
        $item->name = $inputs['name'];
        $item->slug = Str::slug($inputs['slug']);
        $item->auto_approve_products  = $inputs['auto_approve_products'];
        $item->vh_st_store_id  = $inputs['vh_st_store_id']['id'];
        $item->owned_by  = $inputs['owned_by']['id'];
        $item->approved_by = $inputs['approved_by']['id'];
        $item->is_default = $inputs['is_default'];
        $item->taxonomy_id_vendor_status = $inputs['taxonomy_id_vendor_status']['id'];
        $item->status_notes = $inputs['status_notes'];
        $item->is_active = $inputs['is_active'];
        $item->registered_at = \Carbon\Carbon::now()->toDateTimeString();
        $item->approved_at = \Carbon\Carbon::now()->toDateTimeString();
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
            $response['messages'][] = 'Record does not exist.';
            return $response;
        }
        $item->forceDelete();
        ProductVendor::deleteVendors($item->id);

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
                self::find($id)->delete();
                break;
            case 'restore':
                self::where('id', $id)
                    ->withTrashed()
                    ->restore();
                break;
        }

        return self::getItem($id);
    }
    //-------------------------------------------------

    public static function validation($inputs)
    {

        $rules = array(
            'name' => 'required|max:150',
            'slug' => 'required|max:150',
        );

        $validator = \Validator::make($inputs, $rules);
        if ($validator->fails()) {
            $messages = $validator->errors();
            $response['success'] = false;
            $response['messages'] = $messages->all();
            return $response;
        }

        $response['success'] = true;
        return $response;

    }

    //-------------------------------------------------
    public static function getActiveItems()
    {
        $item = self::where('is_active', 1)
            ->first();
        return $item;
    }
    //-------------------------------------------------
    public static function deleteStores($items_id){
        if($items_id){
            self::whereIn('vh_st_product_id',$items_id)->forcedelete();
            $response['success'] = true;
            $response['data'] = true;
        }else{
            $response['error'] = true;
            $response['data'] = false;
        }

    }

    //-------------------------------------------------
    //-------------------------------------------------
    //-------------------------------------------------


}
