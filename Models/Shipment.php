<?php namespace VaahCms\Modules\Store\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahCms\Entities\TaxonomyType;
use WebReinvent\VaahCms\Models\VaahModel;
use WebReinvent\VaahCms\Traits\CrudWithUuidObservantTrait;
use WebReinvent\VaahCms\Models\User;
use WebReinvent\VaahCms\Libraries\VaahSeeder;

class Shipment extends VaahModel
{

    use SoftDeletes;
    use CrudWithUuidObservantTrait;

    //-------------------------------------------------
    protected $table = 'vh_st_shipments';
    //-------------------------------------------------
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    //-------------------------------------------------
    protected $fillable = [
        'uuid',
        'name',
        'tracking_url',
        'tracking_key',
        'tracking_value',
        'is_trackable',
        'taxonomy_id_shipment_status',
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

        $empty_item['is_active'] = 1;

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
    public  function orders()
    {
        return $this->belongsToMany(Order::class, 'vh_st_shipment_items', 'vh_st_shipment_id', 'vh_st_order_id')
            ->withPivot('quantity','pending');
    }
    public  function shipmentOrderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'vh_st_shipment_items', 'vh_st_shipment_id', 'vh_st_order_item_id')
            ->withPivot('id', 'quantity', 'pending');
    }
    //-------------------------------------------------
    public function status()
    {
        return $this->hasOne(Taxonomy::class,'id','taxonomy_id_shipment_status');
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
//    public static function createItem($request)
//    {
//        $inputs = $request->all();
////        dd($inputs['taxonomy_id_shipment_status']);
//        $validation = self::validation($inputs);
//        if (!$validation['success']) {
//            return $validation;
//        }
//        if (isset($inputs['orders'])){
//        foreach ($inputs['orders'] as $order) {
//            $order_items = $order['items'];
//            foreach ($order_items as $order_item) {
//                if (isset($order_item['to_be_shipped']) && $order_item['to_be_shipped']) {
//                    if ($order_item['to_be_shipped'] > $order_item['pending']) {
//                        return ['success' => false, 'errors' => ["to be shipped quantity should not exceeds pending quantity for item:{$order_item['product_variation']['name']}"]];
//                    }
//                }
//            }
//        }
//        }
//        $item = new self();
//        $item->fill($inputs);
//        $item->save();
//            foreach ($inputs['orders'] as $order) {
//                $order_items = $order['items'];
//                $order_id = $order['id'];
//                foreach ($order_items as $order_item) {
//                    $item_id = $order_item['id'];
//                    if (isset($order_item['to_be_shipped']) && $order_item['to_be_shipped']) {
//                        $item_shipped_quantity = $order_item['to_be_shipped'];
//                        $item_pending_quantity = $order_item['pending'];
////                        dd($item_pending_quantity);
//                        $item->orders()->attach($order['id'], [
//                            'vh_st_order_item_id' => $item_id,
//                            'quantity' => $item_shipped_quantity,
//                            'pending' => $item_pending_quantity-$item_shipped_quantity,
//                            'created_at'=>now(),
//                        ]);
//                    }
//                }
//                $shipped_order_quantity = ShipmentItem::where('vh_st_order_id', $order_id)->sum('quantity');
//                $self_status_name=Taxonomy::where('id',$inputs['taxonomy_id_shipment_status'])->first();
//                $order = Order::with('items')->findOrFail($order_id);
//                $total_order_quantity = $order->items()->sum('quantity');
//                $order_payment_status = $order->orderPaymentStatus()->first();
//                if ($order_payment_status->slug === 'pending') {
//                    switch ($self_status_name->slug) {
//                        case 'pending':
//                            $order->taxonomy_id_order_status = 'Placed';
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        case 'delivered':
//                            if ($shipped_order_quantity==$total_order_quantity){
//                            $order->taxonomy_id_order_status = 'Payment Pending';
//                            }
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        default:
//                            $order->taxonomy_id_order_status = $self_status_name->name;
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                    }
//                    $order->save();
//                }
//                if ($order_payment_status->slug === 'partially-paid') {
//                    switch ($self_status_name->slug) {
//                        case 'pending':
//                            $order->taxonomy_id_order_status = 'Placed';
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        case 'delivered':
//                            if ($shipped_order_quantity==$total_order_quantity) {
//                                $order->taxonomy_id_order_status = 'Partially-Paid';
//                            }
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        default:
//                            $order->taxonomy_id_order_status = $self_status_name->name;
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                    }
//                    $order->save();
//                }
//                if ($order_payment_status->slug === 'paid') {
//                    switch ($self_status_name->slug) {
//                        case 'pending':
//                            $order->taxonomy_id_order_status = 'Placed';
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        case 'delivered':
//                            if ($shipped_order_quantity==$total_order_quantity) {
//                                $order->taxonomy_id_order_status = 'Completed';
//                            }
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                        default:
//                            $order->taxonomy_id_order_status = $self_status_name->name;
//                            $order->shipment_status = $self_status_name->name;
//                            break;
//                    }
//                    $order->save();
//                }
//            }
//
//        $response = self::getItem($item->id);
//        $response['messages'][] = trans("vaahcms-general.saved_successfully");
//        return $response;
//
//    }


    public static function createItem($request)
    {
        $inputs = $request->all();
        $validation = self::validation($inputs);
        if (!$validation['success']) {
            return $validation;
        }

        if (isset($inputs['orders'])) {
            foreach ($inputs['orders'] as $order) {
                $order_items = $order['items'];
                foreach ($order_items as $order_item) {
                    if (isset($order_item['to_be_shipped']) && $order_item['to_be_shipped']) {
                        if ($order_item['to_be_shipped'] > $order_item['pending'] ) {
                            return [
                                'success' => false,
                                'errors' => [
                                    "To be shipped quantity should not exceed pending quantity for item: {$order_item['product_variation']['name']}"
                                ]
                            ];
                        }
                    }
                }
            }
        }
        $item = new self();
        $item->fill($inputs);
        $item->save();

        foreach ($inputs['orders'] as $order) {
            $order_id = $order['id'];
            $order_items = $order['items'];

            foreach ($order_items as $order_item) {
                if (isset($order_item['to_be_shipped']) && $order_item['to_be_shipped']) {
                    $item_id = $order_item['id'];
                    $item_shipped_quantity = $order_item['to_be_shipped'];
                    $item_pending_quantity = $order_item['pending'];

                    $item->orders()->attach($order_id, [
                        'vh_st_order_item_id' => $item_id,
                        'quantity' => $item_shipped_quantity,
                        'pending' => $item_pending_quantity - $item_shipped_quantity,
                        'created_at' => now(),
                    ]);
                }
            }

            // get total shipped quantity of items in order
            $shipped_order_quantity = ShipmentItem::where('vh_st_order_id', $order_id)->sum('quantity');
            $shipment_status_name = Taxonomy::where('id', $inputs['taxonomy_id_shipment_status'])->value('name');
            $order = Order::with('items', 'orderPaymentStatus')->findOrFail($order_id);
            // total order quantity to be shipped
            $total_order_quantity = $order->items()->sum('quantity');
            $order_payment_status_slug = $order->orderPaymentStatus->slug;


            self::updateOrderStatus($order, $order_payment_status_slug, $shipment_status_name, $shipped_order_quantity, $total_order_quantity);
        }
        $response = self::getItem($item->id);
        $response['messages'][] = trans("vaahcms-general.saved_successfully");
        return $response;
    }
    public static function updateOrderStatus($order, $payment_status_slug, $shipment_status_name, $shipped_order_quantity, $total_order_quantity)
    {
        switch ($payment_status_slug) {
            case 'pending':
                switch ($shipment_status_name) {
                    case 'Pending':
                        $order->order_status = 'Placed';
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                    case 'Delivered':
                        $order->order_status = 'Payment Pending';
                        if ($shipped_order_quantity != $total_order_quantity) {
                            $order->order_shipment_status = 'Partially Delivered';
                        } else {
                            $order->order_shipment_status = $shipment_status_name;
                        }
                        break;
                    default:
                        $order->order_status = $shipment_status_name;
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                }
                break;

            case 'partially-paid':
                switch ($shipment_status_name) {
                    case 'Pending':
                        $order->order_status = 'Placed';
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                    case 'Delivered':
                        $order->order_status = 'Partially-Paid';
                        if ($shipped_order_quantity != $total_order_quantity) {
                            $order->order_shipment_status = 'Partially Delivered';
                        } else {
                            $order->order_shipment_status = $shipment_status_name;
                        }
                        break;
                    default:
                        $order->order_status = $shipment_status_name;
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                }
                break;

            case 'paid':
                switch ($shipment_status_name) {
                    case 'Pending':
                        $order->order_status = 'Placed';
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                    case 'Delivered':
                        $order->order_status = 'Completed';
                        if ($shipped_order_quantity != $total_order_quantity) {
                            $order->order_status = 'Partially Delivered';
                            $order->order_shipment_status = 'Partially Delivered';
                        } else {
                            $order->order_shipment_status = $shipment_status_name;
                        }
                        break;
                    default:
                        $order->order_status = $shipment_status_name;
                        $order->order_shipment_status = $shipment_status_name;
                        break;
                }
                break;

            default:
                $order->order_status = $shipment_status_name;
                $order->order_shipment_status = $shipment_status_name;
                break;
        }
        $order->save();
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
        $search_array = explode(' ',$filter['q']);
        foreach ($search_array as $search_item){
            $query->where(function ($q1) use ($search_item) {
                $q1->where('name', 'LIKE', '%' . $search_item . '%')
                    ->orWhere('slug', 'LIKE', '%' . $search_item . '%')
                    ->orWhere('id', 'LIKE', $search_item . '%');
            });
        }

    }
    //-------------------------------------------------
    public static function getList($request)
    {
        $list = self::getSorted($request->filter)->with( 'status')->withCount(['orders' => function ($query) {
            $query->select(DB::raw('count(distinct vh_st_order_id)'));
        }]);
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
            'type.required' => trans("vaahcms-general.action_type_is_required"),
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

        $items = self::whereIn('id', $items_id);

        switch ($inputs['type']) {
            case 'deactivate':
                $items->withTrashed()->where(['is_active' => 1])
                    ->update(['is_active' => null]);
                break;
            case 'activate':
                $items->withTrashed()->where(function ($q){
                    $q->where('is_active', 0)->orWhereNull('is_active');
                })->update(['is_active' => 1]);
                break;
            case 'trash':
                self::whereIn('id', $items_id)
                    ->get()->each->delete();
                break;
            case 'restore':
                self::whereIn('id', $items_id)->onlyTrashed()
                    ->get()->each->restore();
                break;
        }

        $response['success'] = true;
        $response['data'] = true;
        $response['messages'][] = trans("vaahcms-general.action_successful");

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
            'type.required' => trans("vaahcms-general.action_type_is_required"),
            'items.required' => trans("vaahcms-general.select_items"),
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
        $response['messages'][] = trans("vaahcms-general.action_successful");

        return $response;
    }
    //-------------------------------------------------
     public static function listAction($request, $type): array
    {

        $list = self::query();

        if($request->has('filter')){
            $list->getSorted($request->filter);
            $list->isActiveFilter($request->filter);
            $list->trashedFilter($request->filter);
            $list->searchFilter($request->filter);
        }

        switch ($type) {
            case 'activate-all':
                $list->withTrashed()->where(function ($q){
                    $q->where('is_active', 0)->orWhereNull('is_active');
                })->update(['is_active' => 1]);
                break;
            case 'deactivate-all':
                $list->withTrashed()->where(['is_active' => 1])
                    ->update(['is_active' => null]);
                break;
            case 'trash-all':
                $list->get()->each->delete();
                break;
            case 'restore-all':
                $list->onlyTrashed()->get()
                    ->each->restore();
                break;
            case 'delete-all':
                $items = self::withTrashed()->get();
                foreach ($items as $item) {
                    $item->orders()->detach();
                }
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
        $response['messages'][] = trans("vaahcms-general.action_successful");

        return $response;
    }
//    -------------------------------------------------
    public static function getItem($id)
    {

        $item = self::where('id', $id)
            ->with([
                'createdByUser',
                'updatedByUser',
                'deletedByUser',
                'status',

                'shipmentOrderItems.order.user',
                'shipmentOrderItems.productVariation' => function ($query) {
                    $query->select('id', 'name', 'slug');
                },
                'shipmentOrderItems.vendor' => function ($query) {
                    $query->select('id', 'name', 'slug');
                }
            ])
            ->withTrashed()
            ->first();



        $item->is_items_exist_already = false;
        foreach ($item->shipmentOrderItems as $shipped_item) {
            $shipped_item->overall_shipped_quantity = static::getShippedQuantity($shipped_item->id);
            $is_exist_with_other_shipment = ShipmentItem::where('vh_st_order_item_id', $shipped_item->id)
                ->where('vh_st_shipment_id', '!=', $item->id)
                ->exists();
            if ($is_exist_with_other_shipment) {
                $shipped_item->is_items_exist_already = true;
            }

        }

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
        // check if name exist
        $item = self::where('id', '!=', $id)
            ->withTrashed()
            ->where('name', $inputs['name'])->first();

         if ($item) {
             $error_message = "This name is already exist".($item->deleted_at?' in trash.':'.');
             $response['success'] = false;
             $response['errors'][] = $error_message;
             return $response;
         }

        $item_ids = [];
        foreach ($inputs['orders'] as $shipment_order) {
            foreach ($shipment_order['items'] as $item_single) {
                if ((isset($item_single['to_be_shipped'])) && ($item_single['to_be_shipped'] > $item_single['pending'])) {
                    return ['success' => false, 'errors' => ["to be shipped quantity should not exceeds pending quantity for item:{$item_single['product_variation']['name']}"]];
                }
                $quantity = $item_single['to_be_shipped'] ?? $item_single['shipped'] ?? 0;
                $order_item_id = $item_single['id'] ?? null;
                $order_id = $item_single['vh_st_order_id'] ?? null;
                $item_ids[$order_item_id] = [
                    'quantity' => $quantity,
                    'vh_st_order_item_id' => $order_item_id,
                    'vh_st_order_id' => $order_id,
                    'pending' => abs($item_single['quantity'] - $quantity),
                ];
            }
            $order_id = $shipment_order['id'];
            $order_items = $shipment_order['items'];
            $shipped_order_quantity = ShipmentItem::where('vh_st_order_id', $order_id)->sum('quantity');
            $shipment_status_name = Taxonomy::where('id', $inputs['taxonomy_id_shipment_status'])->value('name');
            $order = Order::with('items', 'orderPaymentStatus')->findOrFail($order_id);
            // total order quantity to be shipped
            $total_order_quantity = $order->items()->sum('quantity');
//            dd($shipped_order_quantity,$total_order_quantity);
            $order_payment_status_slug = $order->orderPaymentStatus->slug;


            self::updateOrderStatus($order, $order_payment_status_slug, $shipment_status_name, $shipped_order_quantity, $total_order_quantity);
        }

       /* $item_ids = [];

        foreach ($inputs['orders'] as $shipment_order_items) {
            foreach ($shipment_order_items['items'] as $item_single) {
                // Check if 'to_be_shipped' is present and greater than 0
                if (isset($item_single['to_be_shipped']) && $item_single['to_be_shipped'] > 0) {
                    $quantity = $item_single['to_be_shipped'];
                    $order_item_id = $item_single['id'] ?? null;
                    $order_id = $item_single['vh_st_order_id'] ?? null;

                    $item_ids[$order_item_id] = [
                        'quantity' => $quantity,
                        'vh_st_order_item_id' => $order_item_id,
                        'vh_st_order_id' => $order_id,
                    ];
                }
            }
        }*/
        $item = self::where('id', $id)->withTrashed()->first();
        $item->fill($inputs);
        $item->save();
        foreach ($item_ids as $order_item_id => $data) {
            $is_exist_order_item = ShipmentItem::where('vh_st_order_item_id', $order_item_id)
                ->where('vh_st_shipment_id', '!=', $id)
                ->exists();
            if (!$is_exist_order_item) {
                $item->shipmentOrderItems()->sync($item_ids);
            }
        }
        $response = self::getItem($item->id);
        $response['messages'][] = trans("vaahcms-general.saved_successfully");
        return $response;

    }
    //-------------------------------------------------
    public static function deleteItem($request, $id): array
    {
        $item = self::where('id', $id)->withTrashed()->first();
        if (!$item) {
            $response['success'] = false;
            $response['errors'][] = trans("vaahcms-general.record_does_not_exist");
            return $response;
        }
        $item->forceDelete();

        $response['success'] = true;
        $response['data'] = [];
        $response['messages'][] = trans("vaahcms-general.record_has_been_deleted");

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
                self::find($id)
                    ->delete();
                break;
            case 'restore':
                self::where('id', $id)
                    ->onlyTrashed()
                    ->first()->restore();
                break;
        }

        return self::getItem($id);
    }
    //-------------------------------------------------

    public static function validation($inputs)
    {
        $rules = [
            'name' => 'required',
            'orders' => 'required',
            'status' => 'required',
        ];

        if (!empty($inputs['tracking_url'])) {
            $rules['tracking_key'] = 'required';
            $rules['tracking_value'] = 'required';

        }
        $validated_data = validator($inputs, $rules);
        if($validated_data->fails()){
            $errors = $validated_data->errors()->all();
            if (isset($inputs['value'])) {
                foreach ($inputs['value'] as $key => $value) {

                    if (in_array("value.{$key}.value", $errors)) {
                        unset($inputs['value'][$key]);
                    }
                }
            }
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
    public static function searchOrders($request){
        $query = Order::with(['user' => function ($query) {
            $query->select('id', 'display_name as user_name');
        }])
            ->with(['items' => function ($query) {
                $query->select('id', 'uuid', 'vh_st_order_id', 'vh_user_id', 'vh_st_product_variation_id','quantity')
                    ->with(['ProductVariation' => function ($query) {
                        $query->select('id', 'name');
                    }]);
            }])
            ->select('id', 'amount', 'paid', 'created_at', 'updated_at', 'vh_user_id')
            ->where('is_active', 1);

        if ($request->has('query') && $request->input('query')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('display_name', 'LIKE', '%' . $request->input('query') . '%')
                    ->orWhere('first_name', 'LIKE', '%' . $request->input('query') . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->input('query') . '%');
            });
        }

        $orders = $query->limit(10)->get();

        foreach ($orders as &$order) {
            foreach ($order->items as &$item) {
                if ($item->productVariation) {
                    $item->name = $item->productVariation->name;

                    $shippedQuantity = static::getShippedQuantity($item->id);
                    $pending_quantity = static::getPendingQuantity($item->id);
                    $item->shipped = $shippedQuantity;
                    if ($pending_quantity != 0) {

                        $item->pending = $pending_quantity;
                    } else {
                        $item->pending = $item->quantity - $shippedQuantity;
                    }
                    $item->overall_shipped_quantity = static::getShippedQuantity($item->id);
                    unset($item->productVariation);

                }
            }
            if ($order->user) {
                $order->user_name = $order->user->user_name;
                unset($order->user);
            }
        }

        $response['success'] = true;
        $response['data'] = $orders;
        return $response;
    }

    //-------------------------------------------------
    private static function getShippedQuantity($itemId) {
        return DB::table('vh_st_shipment_items')
            ->where('vh_st_order_item_id', $itemId)
            ->sum('quantity');
    }

    //-------------------------------------------------
    private static function getPendingQuantity($itemId) {
        return DB::table('vh_st_shipment_items')
            ->where('vh_st_order_item_id', $itemId)
            ->orderByDesc('created_at')
            ->value('pending');
    }

    //-------------------------------------------------
    private static function getPendingQuantity($itemId) {
        return DB::table('vh_st_shipment_items')
            ->where('vh_st_order_item_id', $itemId)
            ->orderByDesc('created_at')
            ->value('pending');
    }
    //-------------------------------------------------
    public static function searchStatus($request){
        $query = $request->input('query');
        if(empty($query)) {
            $item = Taxonomy::getTaxonomyByType('shipment-status');
        } else {
            $status = TaxonomyType::getFirstOrCreate('shipment-status');
            $item =array();

            if(!$status){
                return $item;
            }
            $item = Taxonomy::whereNotNull('is_active')
                ->where('vh_taxonomy_type_id',$status->id)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->get();
        }

        $response['success'] = true;
        $response['data'] = $item;
        return $response;

    }

    public static function getShippedItemList($id){
        dd($id);
    }
}
