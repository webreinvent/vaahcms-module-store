<?php namespace VaahCms\Modules\Store\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Faker\Factory;
use Ramsey\Uuid\Uuid;
use WebReinvent\VaahCms\Entities\Taxonomy;
use WebReinvent\VaahCms\Models\VaahModel;
use WebReinvent\VaahCms\Traits\CrudWithUuidObservantTrait;
use WebReinvent\VaahCms\Models\User;
use WebReinvent\VaahCms\Libraries\VaahSeeder;

class Payment extends VaahModel
{

    use SoftDeletes;
    use CrudWithUuidObservantTrait;

    //-------------------------------------------------
    protected $table = 'vh_st_payments';
    //-------------------------------------------------
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    //-------------------------------------------------
    protected $fillable = [
        'uuid',
        'notes',
        'amount',
        'taxonomy_id_payment_status',
        'vh_st_payment_method_id',
        'transaction_id',
        'meta',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    //-------------------------------------------------
    protected $fill_except = [

    ];

    //-------------------------------------------------

    //-------------------------------------------------
    protected $appends = [
    ];

    //-------------------------------------------------



    //-------------------------------------------------
    public function setMetaAttribute($value)
    {
        $this->attributes['meta'] = json_encode($value);
    }
    //-------------------------------------------------
    public function getMetaAttribute($value)
    {
        if($value && $value!='null'){
            return json_decode($value);
        }else{
            return json_decode('{}');
        }

    }



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
    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class,'id','vh_st_payment_method_id')->select('id','name','slug');
    }
    //-------------------------------------------------
    public  function orders()
    {
        return $this->belongsToMany(Order::class, 'vh_st_order_payments', 'vh_st_payment_id', 'vh_st_order_id');
    }
    //-------------------------------------------------
    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class, 'vh_st_payment_id')->with('taxonomyOrderPaymentStatus');
    }
    //-------------------------------------------------

    public function status()
    {
        return $this->hasOne(Taxonomy::class,'id','taxonomy_id_payment_status')->select('id','name','slug');
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
        $payment_method_slug = $inputs['payment_method']['slug'] ?? null;
        if ($payment_method_slug !== 'cod') {
            return ['success' => false, 'errors' => ["Currently dealing with COD payment method"]];
        }
        $validation_result = self::validateOrderAndPayment($inputs['orders'],$inputs['amount']);
        if (!$validation_result['success']) {
            return $validation_result;
        }
        $successfully_paid_orders = $validation_result['successfully_paid_orders'] ?? [];

        $transaction_id = Uuid::uuid7(now())->toString();
        $item = new self();
        $item->fill($inputs);
        $item->taxonomy_id_payment_status = Taxonomy::getTaxonomyByType('payment-status')
            ->where('slug', 'failure')->value('id');
        $item->transaction_id = $transaction_id;
        $is_payment_for_all_orders = false;
        $order_ids = [];
        $item->save();
        if (isset($inputs['orders']) && is_array($inputs['orders'])) {
            $is_payment_for_all_orders = false;
            $collect_orders = collect($inputs['orders']);
            foreach ($collect_orders as $key => $order_data){
                $order = Order::find($order_data['id']);
                if ($order) {
                    $order->paid += $order_data['pay_amount'];
                    $payable_amount = round($order_data['payable_amount'], 2);
                    $pay_amount = $order_data['pay_amount'];
                    $taxonomy_payment_status_slug = ($payable_amount == $pay_amount) ? 'paid' : 'partially-paid';
                    $taxonomy_payment_status_id = Taxonomy::getTaxonomyByType('order-payment-status')
                        ->where('slug', $taxonomy_payment_status_slug)->value('id');
                    $item->orders()->attach($order->id, [
                        'payable_amount' => $order_data['payable_amount'],
                        'payment_amount' => $order_data['pay_amount'],
                        'remaining_payable_amount' => $order_data['payable_amount'] - $order_data['pay_amount'],
                        'taxonomy_id_order_payment_status' => $taxonomy_payment_status_id,
                        'created_at' => now(),
                    ]);
                    $order->taxonomy_id_payment_status = $taxonomy_payment_status_id;
                    $order->is_paid = 1;
                    $order->save();

                    $is_payment_for_all_orders = true;
                    $order_ids[] = $order->id;
                } else {
                    $is_payment_for_all_orders = false;
                    break;
                }
            }
        }

        $overall_payment_status = $is_payment_for_all_orders ? 'success' : 'pending';
        $item->taxonomy_id_payment_status = Taxonomy::getTaxonomyByType('payment-status')
            ->where('slug', $overall_payment_status)
            ->value('id');
        $successfully_paid_order_names = implode(', ', $successfully_paid_orders);
        $item->status_notes = "Payment successfully done for orders: <strong>{$successfully_paid_order_names}</strong>";
        $item->date = now();
        $item->save();
        $response = self::getItem($item->id);
        $response['messages'][] = trans("vaahcms-general.saved_successfully");
        return $response;

    }

    //-------------------------------------------------


    public static function validateOrderAndPayment($orders,$total_payment)
    {
        $errors = [];
        $successfully_paid_orders = [];
        $total_paid_amount = 0;
        $collected_orders = collect($orders);
        foreach ($collected_orders as $key => $order_data){
            $order = Order::find($order_data['id']);
            $payable_amount = round($order_data['payable_amount'], 2);
            $pay_amount = $order_data['pay_amount'];
            $order_payable_amount = round($order->payable - $order->paid, 2);

            if (!$order) {
                $errors[] = "Order not found for ID: {$order_data['id']}";
                continue;
            }
            if ($order_payable_amount == 0) {
                $errors[] = "Order '{$order->user->name}' has already been fully paid.";
                continue;
            }
            if ($payable_amount != $order_payable_amount) {
                $errors[] = "Order '{$order->user->name}' has incorrect payable amount.";
                continue;
            }
            if ($pay_amount > $order_payable_amount) {
                $errors[] = "Payment amount exceeds payable amount for order '{$order->user->name}'";
                continue;
            }
            if ($pay_amount <= 0) {
                $errors[] = "Payment amount for order '{$order->user->name}' must be greater than 0.";
                continue;
            }

            $successfully_paid_orders[] = $order->user->name;
            $total_paid_amount += $pay_amount;
        }
        if ($total_paid_amount !== $total_payment) {
            $errors[] = "Total payment amount ($total_paid_amount) does not match the specified total payment ($total_payment).";
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true, 'successfully_paid_orders' => $successfully_paid_orders];
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
        foreach ($search_array as  $key =>$search_item){
            $query->where(function ($q1) use ($search_item) {
                $q1->Where('id', 'LIKE', $search_item . '%')
                    ->orWhere('transaction_id', 'LIKE', $search_item . '%');
            });
        }

    }
    //-------------------------------------------------
    public function scopeOrderFilter($query, $filter)
    {
        if(!isset($filter['order']))
        {
            return $query;
        }
        $orders = array_map('urldecode', $filter['order']);
        $query->whereHas('orders', function ($q) use ($orders) {
            $q->where(function ($ou) use ($orders) {
                foreach ($orders as $order) {
                    $ou->orWhereHas('user', function ($d) use ($order) {
                        $d->where('display_name', 'LIKE', '%' . $order . '%');
                    });
                }
            });
        });

        return $query;

    }
    //-------------------------------------------------
    public static function getList($request)
    {
        $list = self::getSorted($request->filter)->with('status','paymentMethod')->withCount('orders',);
        $list->isActiveFilter($request->filter);
        $list->trashedFilter($request->filter);
        $list->orderFilter($request->filter);
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
        self::with('orders')->whereIn('id', $items_id)->each(function ($item) {
            $item->orders()->detach();
        });
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
                foreach ($items as  $key =>$item) {
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
    //-------------------------------------------------
    public static function getItem($id)
    {

        $item = self::where('id', $id)
            ->with([
                'createdByUser',
                'updatedByUser',
                'deletedByUser',
                'status','orderPayments','orderPayments.order.user',
                'paymentMethod',
            ])
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
        $validatedData = $request->validate([
            'notes' => 'required|max:100',
        ], [
            'notes.required' => 'The payment notes field is required.',
            'notes.max' => 'The payment notes field must not exceed :max characters.',
        ]);
        $inputs = $request->all();
        $item = self::where('id', $id)->withTrashed()->first();
        $item->notes=$inputs['notes'];
        $item->is_active=$inputs['is_active'];
        $item->save();

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
        $item->orders()->detach();
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
        $validated_data = validator($inputs, [

           'orders' =>'required',
            'orders.*.pay_amount' => 'required|numeric|min:0',
            'orders.*.amount' => 'nullable|numeric',
            'orders.*.user_name' => 'required|string',
            'vh_st_payment_method_id' => 'required',
            'notes' => 'nullable|string|max:100',
        ],
        [
            'vh_st_payment_method_id.required' => 'The payment method is required.',

            'notes.max' => 'The payment notes field may not be greater than :max characters',
        ]
        );
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
            $item->date=now();
            $item->save();
            if (isset($inputs['orders']) && is_array($inputs['orders']) && count($inputs['orders']) > 0) {
                foreach ($inputs['orders'] as  $key =>$order) {
                    $payable_amount = $order['payable_amount'];

                    $pay_amount = 0;
                    $remaining_payable_amount = $payable_amount - $pay_amount;

                    $orderData = [
                        'payable_amount' => $payable_amount,
                        'pay_amount' => $pay_amount,
                    ];
                    $attachmentData = [
                        'payable_amount' => $orderData['payable_amount'],
                        'payment_amount' => $orderData['pay_amount'],
                        'remaining_payable_amount' => $remaining_payable_amount,
                        'created_at' => now(),
                    ];
                    $item->orders()->attach($order['id'], $attachmentData);
                }
            }
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
        $orders = Order::with(['user' => function ($query) {
            $query->select('id', 'display_name as user_name');
        }])
            ->select('id', 'amount', 'paid', 'created_at', 'updated_at', 'vh_user_id')
            ->where('is_active', 1)
            ->whereRaw('amount > paid');

        $orders = $orders->limit(10)->get();

        if ($orders->isNotEmpty()) {
            $random_order = $orders->random();
            $inputs['orders'] = [$random_order];
            foreach ($orders as &$order) {
                if ($order->user) {
                    $order->user_name = $order->user->user_name;
                    $order->payable_amount= $order->amount - $order->paid;
                    unset($order->user);
                }
            }
        }
        $payment_method = PaymentMethod::where(['is_active'=>1,'deleted_at'=>null])->get();
        $payment_method_ids = $payment_method->pluck('id')->toArray();
        $payment_id = $payment_method_ids[array_rand($payment_method_ids)];
        $inputs['vh_st_payment_method_id'] = $payment_id;
        $payment_method_input = $payment_method->where('id',$payment_id)->first();
        $inputs['payment_method']=$payment_method_input;

        $taxonomy_status = Taxonomy::getTaxonomyByType('payment-status')->where('slug', 'failure');
        $status_ids = $taxonomy_status->pluck('id')->toArray();
        $status_id = $status_ids[array_rand($status_ids)];
        $inputs['taxonomy_id_payment_status'] = $status_id;
        $inputs['amount'] = 0;
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
            ->select('id', 'amount','paid', 'created_at', 'updated_at', 'vh_user_id')
            ->where('is_active', 1)->whereRaw('amount > paid');;

        if ($request->has('query') && $request->input('query')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('display_name', 'LIKE', '%' . $request->input('query') . '%')
                    ->orWhere('first_name', 'LIKE', '%' . $request->input('query') . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->input('query') . '%');
            });
        }

        $orders = $query->limit(10)->get();

        foreach ($orders as &$order) {
            if ($order->user) {
                $order->user_name = $order->user->user_name;
                $order->payable_amount = round($order->amount - $order->paid, 2);
                unset($order->user);
            }
        }

        $response['success'] = true;
        $response['data'] = $orders;
        return $response;
    }
    //-------------------------------------------------
    public static function getOrdersForFilter($request){
        $query = Order::with(['user' => function ($query) {
            $query->select('id', 'display_name as user_name','email');
        }])
            ->select('id', 'amount','paid', 'created_at', 'updated_at', 'vh_user_id')
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
            if ($order->user) {
                $order->user_name = $order->user->user_name;
                $order->slug = Str::slug($order->user->user_name);
                $order->email = $order->user->email;

                unset($order->user);
            }
        }

        $response['success'] = true;
        $response['data'] = $orders;
        return $response;
    }
    //-------------------------------------------------
    public static function getOrdersByName($request)
    {
        $query = $request['filter']['order'];
        $orders = Order::with(['user' => function ($query) {
            $query->select('id', 'display_name as user_name', 'email');
        }])
            ->select('id', 'amount', 'paid', 'created_at', 'updated_at', 'vh_user_id')
            ->whereHas('user', function ($q) use ($query) {
                $q->whereIn('display_name', $query);
            })
            ->get();
        foreach ($orders as &$order) {
            if ($order->user) {
                $order->user_name = $order->user->user_name;
                $order->slug = Str::slug($order->user->user_name);
                $order->email = $order->user->email;

                unset($order->user);
            }
        }
        $response['success'] = true;
        $response['data'] = $orders;
        return $response;
    }
    //-------------------------------------------------


}
