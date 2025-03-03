<?php namespace VaahCms\Modules\Store\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Faker\Factory;
use VaahCms\Modules\Store\Models\StoreUser;
use WebReinvent\VaahCms\Models\VaahModel;
use WebReinvent\VaahCms\Traits\CrudWithUuidObservantTrait;
use WebReinvent\VaahCms\Models\User;
use WebReinvent\VaahCms\Libraries\VaahSeeder;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahCms\Models\TaxonomyType;

class Address extends VaahModel
{

    use SoftDeletes;
    use CrudWithUuidObservantTrait;

    //-------------------------------------------------
    protected $table = 'vh_st_addresses';
    //-------------------------------------------------
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    //-------------------------------------------------
    protected $fillable = [
        'uuid',
        'vh_user_id',
        'taxonomy_id_address_types',
        'taxonomy_id_address_status',
        'address_line_1','phone', 'city','country', 'state','pin_code',
        'address_line_2',
        'is_default',
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
        'address'
    ];

    //-------------------------------------------------
    protected function serializeDate(DateTimeInterface $date)
    {
        $date_time_format = config('settings.global.datetime_format');
        return $date->format($date_time_format);
    }

    //-------------------------------------------------

    public function getAddressAttribute()
    {
        return $this->address_line_1 . ' ' . $this->address_line_2;
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
        $empty_item['user'] = null;
        $empty_item['address_type'] = null;
        $empty_item['status'] = null;
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
    public function user()
    {
        return $this->belongsTo(User::class,'vh_user_id','id')->withTrashed()
            ->select('id','first_name', 'email','deleted_at');
    }
    //-------------------------------------------------
    public function status()
    {
        return $this->hasOne(Taxonomy::class,'id','taxonomy_id_address_status')
            ->select('id','name','slug');
    }
    //-------------------------------------------------
    public function addressType()
    {
        return $this->hasOne(Taxonomy::class,'id','taxonomy_id_address_types')
            ->select('id','name','slug');
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
        $user_id = $inputs['vh_user_id'];
        $user = StoreUser::find($user_id);

        //check if this address already exist for the user when address type is same
        $address = $user->addresses()
            ->where('taxonomy_id_address_types',$inputs['taxonomy_id_address_types'])
            ->where('address_line_1', $inputs['address_line_1'])
            ->where('address_line_2', $inputs['address_line_2'])
            ->withTrashed()
            ->first();

        if ($address) {
            $error_message = "This Address already exist for the user".($address->deleted_at?' in trash.':'.');
            $response['errors'][] = $error_message;
            return $response;
        }
        $item = new self();
        $item->fill($inputs);
        $addresses = $user->addresses()->get();

        if ($addresses->isEmpty()) {
            $item->is_default = 1;
        }

        //remove previous default address
        if(($inputs['is_default']) == 1)
        {
            $user = StoreUser::find($user_id);
            $previous_default_address = $user->addresses()->where('is_default',1)->first();
            if($previous_default_address)
            {
                $previous_default_address->is_default = 0;
                $previous_default_address->save();
            }
            $item->is_default=1;
        }
        $item->save();

        $response = self::getItem($item->id);
        $response['messages'][] = 'Saved successfully.';
        return $response;

    }


    //-------------------------------------------------

    public static function setDefaultAddress($user_id, $address_id)
    {
        $user = StoreUser::find($user_id);

        if (!$user) {
            throw new \Exception("User not found");
        }

        $user->addresses()->update(['is_default' => false]);

        $address = $user->addresses()->find($address_id);

        if (!$address) {
            throw new \Exception("Address not found");
        }

        $address->is_default = true;
        $address->save();
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
        $keywords = explode(' ',$filter['q']);
        foreach($keywords as $search) {
            $query->where(function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('address_line_1', 'LIKE', '%' . $search . '%')
                        ->orWhere('address_line_2', 'LIKE', '%' . $search . '%');
                })
                    ->orWhere('id', 'LIKE', '%' . $search . '%');
            });
        }
    }

    //-------------------------------------------------

    public function scopeAddressTypeFilter($query, $filter)
    {
        if(!isset($filter['address_type'])
            || is_null($filter['address_type'])
            || $filter['address_type'] === 'null'
        )
        {
            return $query;
        }

        $address_type = $filter['address_type'];

        return $query->whereHas('addressType', function ($query) use ($address_type) {
            $query->where('slug', $address_type);
        });
    }

    //-------------------------------------------------

    public function scopeDefaultFilter($query, $filter)
    {
        if(!isset($filter['is_default'])
            || is_null($filter['is_default'])
            || $filter['is_default'] === 'null'
        )
        {
            return $query;
        }

        $is_default = $filter['is_default'];

        if($is_default == 'true')
        {
            return $query->where(function ($q){
                $q->Where('is_default', 1);
            });
        }
        else{

            return $query->where(function ($q){
                $q->whereNull('is_default')
                    ->orWhere('is_default', 0);
            });
        }
    }

    //-------------------------------------------------

    public function scopeStatusFilter($query, $filter)
    {



        if (!isset($filter['status'])) {
            return $query;
        }
        $status = $filter['status'];
        $query->whereHas('status', function ($q) use ($status) {
            $q->whereIn('slug', $status);
        });

    }

    //-------------------------------------------------

    public static function getList($request)
    {
        $default_address = self::where('is_default', 1)->first();
        $list = self::getSorted($request->filter)->with('user','status','addressType');
        $list->trashedFilter($request->filter);
        $list->searchFilter($request->filter);
        $list->dateFilter($request->filter);
        $list->statusFilter($request->filter);
        $list->addressTypeFilter($request->filter);
        $list->defaultFilter($request->filter);
        $list->userFilter($request->filter);
        $default_address_exists = $default_address;
        $rows = config('vaahcms.per_page');

        if($request->has('rows'))
        {
            $rows = $request->rows;
        }

        $list = $list->paginate($rows);

        $response['success'] = true;
        $response['data'] = $list;
        if (!$default_address_exists) {
            $response['message'] = true;
        }
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

        $status = Taxonomy::getTaxonomyByType('address-status');
        $approve_id = $status->where('name','Approved')->pluck('id')->first();
        $reject_id = $status->where('name','Rejected')->pluck('id')->first();
        $pending_id =$status->where('name','Pending')->pluck('id')->first();

        $items = self::whereIn('id', $items_id)
            ->withTrashed();

        switch ($inputs['type']) {
            case 'pending':
                $items->update(['taxonomy_id_address_status' => $pending_id]);
                break;
            case 'reject':
                $items->update(['taxonomy_id_address_status' => $reject_id]);
                break;
            case 'approve':
                $items->update(['taxonomy_id_address_status' => $approve_id]);
                break;
            case 'trash':
                self::whereIn('id', $items_id)->delete();
                $items->update(['deleted_by' => auth()->user()->id]);
                break;
            case 'restore':
                self::whereIn('id', $items_id)->restore();
                $items->update(['deleted_by' => null,'is_default' => 0]);
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
            $list->trashedFilter($request->filter);
            $list->searchFilter($request->filter);
        }

        $status = Taxonomy::getTaxonomyByType('address-status');
        $approve_id = $status->where('name','Approved')->pluck('id')->first();
        $reject_id = $status->where('name','Rejected')->pluck('id')->first();
        $pending_id =$status->where('name','Pending')->pluck('id')->first();

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
                if (isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->delete();
                    $items->update(['deleted_by' => auth()->user()->id]);
                }
                break;
            case 'restore':
                if (isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->restore();
                    $items->update(['deleted_by' => null,'is_default' => 0]);
                }
                break;
            case 'delete':
                if(isset($items_id) && count($items_id) > 0) {
                    self::whereIn('id', $items_id)->forceDelete();
                }
                break;
            case 'pending-all':
                $list->update(['taxonomy_id_address_status' => $pending_id]);
                break;
            case 'reject-all':
                $list->update(['taxonomy_id_address_status' => $reject_id]);
                break;
            case 'approve-all':
                $list->update(['taxonomy_id_address_status' => $approve_id]);
                break;
            case 'trash-all':
                $user_id = auth()->user()->id;
                $list->update(['deleted_by' => $user_id]);
                $list->delete();
                break;
            case 'restore-all':
                $list->onlyTrashed()->update(['deleted_by' => null,'is_default' => 0]);
                $list->restore();
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
            ->with(['createdByUser', 'updatedByUser', 'deletedByUser','user','status','addressType'])
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
        $item = self::where('id', $id)->withTrashed()->first();
        $item->fill($inputs);
        $user_id = $inputs['vh_user_id'];
        $user = StoreUser::find($user_id);
        $address = $user->addresses()
            ->where('taxonomy_id_address_types',$inputs['taxonomy_id_address_types'])
            ->where('address_line_1', $inputs['address_line_1'])
            ->where('address_line_2', $inputs['address_line_2'])
            ->whereNot('id',$inputs['id'])
            ->withTrashed()
            ->first();
        if ($address) {
            $error_message = "This Address already exist for the user".($address->deleted_at?' in trash.':'.');
            $response['errors'][] = $error_message;
            return $response;
        }

        if(($inputs['is_default']) == 1)
        {
            $user = StoreUser::find($user_id);
            $previous_default_address = $user->addresses()->where('is_default',1)->first();
            if($previous_default_address)
            {
                $previous_default_address->is_default = 0;
                $previous_default_address->save();
            }
            $item->is_default=1;
        }
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
            case 'make-default':
                $address = Address::find($id);
                $user_id = $address->user()->pluck('id')->first();
                $user = StoreUser::find($user_id);
                $addresses = $user->addresses()->get();
                foreach ($addresses as $address) {

                    $address->is_default = 0;
                    $address->save();
                }

                self::where('id', $id)
                    ->withTrashed()
                    ->update(['is_default' => 1]);
                break;
            case 'remove-from-default':
                self::where('id', $id)
                    ->withTrashed()
                    ->update(['is_default' => 0]);
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
                $item = self::where('id',$id)->first();
                $item->deleted_by = null;
                $item->is_default = 0;
                $item->save();
                break;
        }

        return self::getItem($id);
    }
    //-------------------------------------------------

    public static function validation($inputs)
    {

        $rules = validator($inputs,
            [
                'vh_user_id' => 'required',
                'taxonomy_id_address_types' => 'required',
                'address_line_1'=>'required|max:150',
                'address_line_2'=>'nullable|max:150',
                'taxonomy_id_address_status' => 'required',
                'status_notes' => [
                    'required_if:status.slug,==,rejected',
                    'max:250'
                ],
            ],
            [
                'vh_user_id.required' => 'The User field is required',
                'address_line_1.required' => 'The Address Line 1 field is required',
                'address_line_1.max' => 'The Address Line 1 field cannot be more than :max characters.',
                'address_line_2.max' => 'The Address Line 2 field cannot be more than :max characters.',
                'taxonomy_id_address_types.required' => 'The Type field is required',
                'taxonomy_id_address_status.required' => 'The Status field is required',

                'status_notes.required_if' => 'The Status notes is required for "Rejected" Status',
                'status_notes.max' => 'The Status notes field may not be greater than :max characters.',
            ]

        );


        if($rules->fails()){
            return [
                'success' => false,
                'errors' => $rules->errors()->all()
            ];
        }
        $rules = $rules->validated();

        return [
            'success' => true,
            'data' => $rules
        ];

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
        $faker = Factory::create();
        $inputs = $fillable['data']['fill'];
        $inputs['address_line_1'] = $faker->address;
        $inputs['address_line_2'] = $faker->streetAddress;
        $inputs['city'] = $faker->city;
        $inputs['state'] = $faker->streetSuffix;
        $inputs['country'] = $faker->country;
        $inputs['phone'] = $faker->phoneNumber;
        $taxonomy_status = Taxonomy::getTaxonomyByType('address-status');
        $status_ids = $taxonomy_status->pluck('id')->toArray();
        $status_id = $status_ids[array_rand($status_ids)];
        $status = $taxonomy_status->where('id',$status_id)->first();
        $inputs['taxonomy_id_address_status'] = $status_id;
        $inputs['status']=$status;

        $user_ids= User::where('is_active',1)->pluck('id')->toArray();
        $user_id = $user_ids[array_rand($user_ids)];
        $user = User::where('id',$user_id)->first();
        $inputs['user']=$user;
        $inputs['vh_user_id'] = $user_id;

        $address_types = Taxonomy::getTaxonomyByType('address-types');
        $address_ids = $address_types->pluck('id')->toArray();
        $address_id = $address_ids[array_rand($address_ids)];
        $address_type = $address_types->where('id',$address_id)->first();
        $inputs['taxonomy_id_address_types'] = $address_id;
        $inputs['address_type']=$address_type;
        $inputs['is_default']= 0;

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
    public function scopeDateFilter($query, $filter)
    {


        if(!isset($filter['date'])
            || is_null($filter['date'])
        )
        {
            return $query;
        }

        $dates = $filter['date'];
        $from = \Carbon::parse($dates[0])
            ->startOfDay()
            ->toDateTimeString();

        $to = \Carbon::parse($dates[1])
            ->endOfDay()
            ->toDateTimeString();

        return $query->whereBetween('created_at', [$from, $to]);

    }

    //-------------------------------------------------

    public static function searchUser($request)
    {

        $query = $request['filter']['q']['query'];
        if($query === null)
        {
            $users = User::where('is_active',1)
                ->take(10)
                ->get();
        }

        else{

            $users = User::where('first_name', 'like', "%$query%")
                ->where('is_active',1)
                ->get();
        }
        $response['success'] = true;
        $response['data'] = $users;
        return $response;

    }

    //-------------------------------------------------
    public function scopeUserFilter($query, $filter)
    {
        if(!isset($filter['users'])
            || is_null($filter['users'])
            || $filter['users'] === 'null'
        )
        {
            return $query;
        }

        $users = $filter['users'];

        return $query->whereHas('user', function ($query) use ($users) {
            $query->whereIn('first_name', $users);
        });
    }

    //-------------------------------------------------

    public static function getUserBySlug($request)
    {

        $query = $request['filter']['users'];
        $users = User::whereIn('first_name',$query)->get();
        $response['success'] = true;
        $response['data'] = $users;
        return $response;
    }

    //-------------------------------------------------

    public static function getAddressTypeBySlug($request)
    {
        $query = $request['filter']['address_type'];
        $address_type = TaxonomyType::getFirstOrCreate('address-types');
        $item = Taxonomy::whereNotNull('is_active')
                ->where('vh_taxonomy_type_id',$address_type->id)
                ->where('slug',$query)
                ->select('id','name','slug')
                ->first();
        $response['success'] = true;
        $response['data'] = $item;
        return $response;
    }

}
