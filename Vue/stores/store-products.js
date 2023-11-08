import {toRaw, watch} from 'vue'
import {acceptHMRUpdate, defineStore} from 'pinia'
import qs from 'qs'
import {vaah} from '../vaahvue/pinia/vaah'

let model_namespace = 'VaahCms\\Modules\\Store\\Models\\Product';


let base_url = document.getElementsByTagName('base')[0].getAttribute("href");
let ajax_url = base_url + "/store/products";

let empty_states = {
    query: {
        page: null,
        rows: null,
        filter: {
            q: null,
            is_active: null,
            trashed: null,
            sort: null,
        },
    },
    action: {
        type: null,
        items: [],
    }
};

export const useProductStore = defineStore({
    id: 'products',
    state: () => ({
        base_url: base_url,
        ajax_url: ajax_url,
        model: model_namespace,
        assets_is_fetching: true,
        app: null,
        assets: null,
        rows_per_page: [10,20,30,50,100,500],
        list: null,
        item: null,
        fillable:null,
        active_vendors:null,
        active_brands:null,
        active_stores:null,
        selected_vendor:null,
        select_all_vendor:false,
        user_error_message: [],
        empty_variation_item : null,
        variation_item: {
            attribute_option_type: 1,
            product_attributes: [],
            selected_attribute: null,
            attribute_options: null,
            show_create_form: false,
            select_all_variation: false,
            create_variation_data: null,
            new_variation: [],
        },
        status:null,
        empty_query:empty_states.query,
        empty_action:empty_states.action,
        query: vaah().clone(empty_states.query),
        action: vaah().clone(empty_states.action),
        search: {
            delay_time: 600, // time delay in milliseconds
            delay_timer: 0 // time delay in milliseconds
        },
        route: null,
        watch_stopper: null,
        route_prefix: 'products.',
        view: 'large',
        show_filters: false,
        list_view_width: 12,
        form: {
            type: 'Create',
            action: null,
            is_button_loading: null
        },
        is_list_loading: null,
        count_filters: 0,
        variation_selected_menu: [],
        vendor_selected_menu: [],
        suggestion:null,
        product_status:null,
        filtered_status:null,
        filtered_stores:null,
        filtered_brands:null,
        type_suggestion:null,
        name:null,
        list_selected_menu: [],
        list_bulk_menu: [],
        list_create_menu: [],
        item_menu_list: [],
        item_menu_state: null,
        form_menu_list: []
    }),
    getters: {

    },
    actions: {

        //---------------------------------------------------------------------
        searchTaxonomyProduct(event) {

            this.type_suggestion = this.types.filter((product) => {
                return product.name.toLowerCase().startsWith(event.query.toLowerCase());
            });
        },

        //---------------------------------------------------------------------

        async searchBrand(event) {
            const query = {
                filter: {
                    q: event,
                },
            };

            const options = {
                params: query,
                method: 'post',
            };
            await vaah().ajax(
                this.ajax_url + '/search/brand',
                this.searchBrandAfter,
                options
            );
        },

        //---------------------------------------------------------------------

        searchBrandAfter(data, res) {

            if (data) {
                this.filtered_brands = data;
            }
        },

        //--------------------------------------------------------------------

        searchStatus(event) {

            this.filtered_status = this.product_status.filter((status) => {
                return status.name.toLowerCase().startsWith(event.query.toLowerCase());
            });
        },

        //---------------------------------------------------------------------
        async searchStore(event) {
            const query = {
                filter: {
                    q: event,
                },
            };

            const options = {
                params: query,
                method: 'post',
            };
            await vaah().ajax(
                this.ajax_url + '/search/store',
                this.searchStoreAfter,
                options
            );
        },

        //---------------------------------------------------------------------

        searchStoreAfter(data, res) {

            if (data) {
                this.filtered_stores = data;
            }
        },

        //---------------------------------------------------------------------

        async onLoad(route) {
            /**
             * Set initial routes
             */
            this.route = route;

            /**
             * Update with view and list css column number
             */
            this.setViewAndWidth(route.name);

            /**
             * Update query state with the query parameters of url
             */
            this.updateQueryFromUrl(route);
        },
        //---------------------------------------------------------------------
        setViewAndWidth(route_name) {
            switch (route_name) {
                case 'products.index':
                    this.view = 'large';
                    this.list_view_width = 12;
                    break;
                case 'products.variation':
                case 'products.vendor':
                    this.view = 'small';
                    this.list_view_width = 4;
                    break;
                default:
                    this.view = 'small';
                    this.list_view_width = 6;
                    break
            }
        },
        //---------------------------------------------------------------------
        async updateQueryFromUrl(route) {
            if (route.query) {
                if (Object.keys(route.query).length > 0) {
                    for (let key in route.query) {
                        this.query[key] = route.query[key]
                    }
                    this.countFilters(route.query);
                }
            }
        },
        //---------------------------------------------------------------------
        watchRoutes(route) {
            //watch routes
            this.watch_stopper = watch(route, (newVal, oldVal) => {

                    if (this.watch_stopper && !newVal.name.includes(this.route_prefix)) {
                        this.watch_stopper();

                        return false;
                    }

                    this.route = newVal;

                    if (newVal.params.id) {
                        this.getItem(newVal.params.id);
                    }

                    this.setViewAndWidth(newVal.name);

                }, {deep: true}
            )
        },
        //---------------------------------------------------------------------
        watchStates() {
            watch(this.query.filter, (newVal, oldVal) => {
                    this.delayedSearch();
                }, {deep: true}
            )
        },
        //---------------------------------------------------------------------
        watchItem() {
            if (this.item) {
                watch(() => this.item.name, (newVal, oldVal) => {
                        if (newVal && newVal !== "") {
                            this.item.name = newVal;
                            this.item.slug = vaah().strToSlug(newVal);
                        }
                    }, {deep: true}
                )
                watch(() => this.variation_item.attribute_option_type, (newVal, oldVal) => {
                        if (newVal != oldVal) {
                            this.getAttributeList();
                        }
                    }, {deep: true}
                )
            }
            if (this.form_menu_list.length === 0) {
                this.getFormMenu();
            }
        },

        //---------------------------------------------------------------------

        setStore(event) {
            let store = toRaw(event.value);
            this.item.vh_st_store_id = store.id;
        },

        //---------------------------------------------------------------------

        checkQuantity(event) {
            this.item.quantity = event.value;
            if (this.item.quantity > 0) {
                this.item.in_stock = 1;
            } else {
                this.item.in_stock = 0;
            }
        },

        //---------------------------------------------------------------------

        checkInStock(event) {

            if (this.item.in_stock == 0) {
                this.item.quantity = 0;
            }
        },

        //---------------------------------------------------------------------

        setBrand(event) {
            let brand = toRaw(event.value);
            this.item.vh_st_brand_id = brand.id;
        },

        //---------------------------------------------------------------------

        setType(event) {
            let type = toRaw(event.value);
            this.item.taxonomy_id_product_type = type.id;
        },

        //---------------------------------------------------------------------

        setProductStatus(event) {
            let status = toRaw(event.value);
            this.item.taxonomy_id_product_status = status.id;
            if (status.name == 'Approved') {
                this.item.is_active = 1;
            } else {
                this.item.is_active = 0;
            }
        },
        //---------------------------------------------------------------------

        selectStatus() {
            if (this.item.is_active == '1') {
                let active_status = this.product_status.find((item) => item.name === "Approved");
                this.item.taxonomy_id_product_status = active_status.id;
                this.item.status = active_status;
            } else {
                let rejected_status = this.product_status.find((item) => item.name === "Rejected");
                this.item.taxonomy_id_product_status = rejected_status.id;
                this.item.status = rejected_status;
            }
        },

        //---------------------------------------------------------------------

        addVendor() {
            if (this.selected_vendor != null) {
                let exist = 0;
                this.item.vendors.forEach((item) => {
                    if (item['vendor']['id'] == this.selected_vendor['id']) {
                        exist = 1;
                    }
                })
                if (exist == 0) {
                    let new_vendor = {
                        vendor: this.selected_vendor,
                        is_selected: false,
                        can_update: false,
                        status:this.selected_vendor.status,
                        status_notes: null,
                    };
                    this.item.vendors.push(new_vendor);
                } else {
                    this.showUserErrorMessage(['This vendor is already present'], 4000);
                }

            }
        },
        //---------------------------------------------------------------------
        selectAllVendor() {
            this.item.vendors.forEach((i) => {
                i['is_selected'] = !this.select_all_vendor;
            })
        },
        //---------------------------------------------------------------------

        async removeVendor(attribute) {

            this.item.vendors = this.item.vendors.filter(function (item) {
                return item['vendor']['id'] != attribute['vendor']['id']
            })

            if (attribute.id) {
                const options = {
                    method: 'get',
                };
                let id = attribute.id;
                await vaah().ajax(
                    this.ajax_url + '/' + id + '/remove/vendor',
                    this.removeVendorAfter,
                    options
                );
            }

        },

        //---------------------------------------------------------------------

        async removeVendorAfter(data, res) {
            if (data) {
                this.item = data;
                await this.getList();
                await this.formActionAfter(data);
                this.getItemMenu();
                this.getFormMenu();
            }
        },

        //---------------------------------------------------------------------

        async bulkRemoveVendor(id, all = null) {

            if (all) {
                const options = {
                    method: 'get',
                };
                await vaah().ajax(
                    this.ajax_url + '/' + id + '/bulk-remove/vendor',
                    this.bulkRemoveVendorAfter,
                    options
                );
            } else {

                let temp = null;
                temp = this.item.vendors.filter((item) => {
                    return item['is_selected'] == true;
                });

                if (temp.length === this.item.vendors.length) {

                    let temp = null;
                    temp = this.item.vendors.filter((item) => {
                        return item['is_selected'] == true;
                    });
                    this.item.vendors = temp;
                    this.select_all_product = false;
                    let id = this.route.params.id;
                    const options = {
                        method: 'get',
                    };

                    await vaah().ajax(
                        this.ajax_url + '/' + id + '/bulk-remove/vendor',
                        this.bulkRemoveVendorAfter,
                        options
                    );
                } else {
                    let temp = null;
                    temp = this.item.vendors.filter((item) => {
                        return item['is_selected'] != true;
                    })
                    this.item.vendors = temp;
                    this.select_all_vendor = false;
                    await this.itemAction('save-vendor')
                }

            }
        },
        //---------------------------------------------------------------------

        async bulkRemoveVendorAfter() {
            await this.getList();
            this.item.vendors = [];
        },

        //---------------------------------------------------------------------

        async getAttributeList(callback= null, get_attribute_from_group = false) {

            let params = {
                attribute_type: this.variation_item.attribute_option_type == 0 ? 'attribute' : 'attribute_group',
                product_id: this.item.id,
                selected_attribute: this.variation_item.selected_attribute,
                get_attribute_from_group: get_attribute_from_group
            }

            let options = {
                params: params,
                method: "POST"
            };

            await vaah().ajax(
                this.ajax_url+'/getAttributeList',
                callback ?? this.afterGetAttributeList,
                options
            );

        },
        //---------------------------------------------------------------------
        afterGetAttributeList(data, res){

            this.variation_item.attribute_options = data;

        },
        //---------------------------------------------------------------------
        async getAttributeValues(callback, method = 'generate') {

            let params = {
                attribute: this.variation_item.product_attributes,
                product_id: this.item.id,
                method: method
            }

            let options = {
                params: params,
                method: "POST"
            };

            await vaah().ajax(
                this.ajax_url+'/getAttributeValue',
                callback,
                options
            );

        },
        //---------------------------------------------------------------------
        getUnique(data, key){
            return [...new Map(data.map(x => [key(x), x])).values()];
        },
        //---------------------------------------------------------------------
        addNewProductAttribute(data = null){

            if (this.variation_item.selected_attribute && this.variation_item.attribute_option_type == 0){
                this.variation_item.product_attributes.push(this.variation_item.selected_attribute);
                this.variation_item.product_attributes = this.getUnique(this.variation_item.product_attributes, it=> it.id);
            }
            else if(this.variation_item.selected_attribute && this.variation_item.attribute_option_type == 1){
                this.getAttributeList(this.addProductAttributeFromGroup, true)
            }
        },
        //---------------------------------------------------------------------
        addProductAttributeFromGroup(data){
            data.forEach((i)=>{
                this.variation_item.product_attributes.push(i);
            })
            this.variation_item.product_attributes = this.getUnique(this.variation_item.product_attributes, it=> it.id);
        },
        //---------------------------------------------------------------------
        removeProductAttribute(attribute){
            this.variation_item.product_attributes = this.variation_item.product_attributes.filter(function(item){ return item.name != attribute.name })
        },
        //---------------------------------------------------------------------
        generateProductVariation(){

            if (this.variation_item.product_attributes && this.variation_item.product_attributes.length > 0){
                this.getAttributeValues(this.afterGenerateVariations, 'generate');
            }
        },
        //---------------------------------------------------------------------
        afterGenerateVariations(data, res){
            if (data){
                this.item.all_variation = data;
                this.variation_item.show_create_form = false;
                this.variation_item.create_variation_data = [];
            }
        },
        //---------------------------------------------------------------------
        createProductVariation(){
            if (this.variation_item.product_attributes && this.variation_item.product_attributes.length > 0){
                this.getAttributeValues(this.afterCreateVariation, 'create');
            }
        },
        //---------------------------------------------------------------------
        afterCreateVariation(data, res){
            if (data){

                this.variation_item.create_variation_data = data;
                this.variation_item.show_create_form = true;
                this.variation_item.new_variation = [];
            }
        },
        //---------------------------------------------------------------------
        removeProductVariation(item){
            let item_key = this.getIndexOfArray(this.item.all_variation.structured_variation, item);
            if (item_key >= 0){
                this.item.all_variation.structured_variation.splice(item_key, 1);
            }
        },
        //---------------------------------------------------------------------
        bulkRemoveProductVariation(all = null){

            if (all){
                this.item.all_variation = {};
                this.variation_item.select_all_variation = false;
            }else{
                let temp = null;
                temp = this.item.all_variation.structured_variation.filter((item) => {
                    return item['is_selected'] != true;
                });
                this.item.all_variation.structured_variation = temp;

                this.variation_item.select_all_variation = false;
            }

        },
        //---------------------------------------------------------------------
        getIndexOfArray(array, findArray){
            let index = -1;
            array.some((item, i)=>{
                if(JSON.stringify(item) === JSON.stringify(findArray)) {
                    index = i;
                    return true;
                }
            });
            return index;
        },
        //---------------------------------------------------------------------
        selectAllVariation(){
            this.item.all_variation.structured_variation.forEach((i)=>{
                i['is_selected'] = !this.variation_item.select_all_variation;
            })
        },
        //---------------------------------------------------------------------
        addNewProductVariation(new_record){

            if (this.variation_item.new_variation
                && Object.keys(this.variation_item.new_variation).length
                > Object.keys(this.variation_item.create_variation_data.all_attribute_name).length){
                if (this.item.all_variation && Object.keys(this.item.all_variation).length > 0){

                    let error_message = [];
                    let variation_match_key = null;
                    this.item.all_variation.structured_variation.forEach((i,k)=>{
                        if (i.variation_name == this.variation_item.new_variation.variation_name.trim()){
                            error_message.push('variation name must be unique');
                        }
                        this.variation_item.create_variation_data.all_attribute_name.forEach((i_new, k_new)=>{
                            if (i[i_new]['value'] == this.variation_item.new_variation[i_new]['value']){
                                // console.log(k);;
                                if (variation_match_key == k){
                                    error_message.push('variation already exist');
                                }else{
                                    variation_match_key = k;
                                }
                            }
                        });

                        if (variation_match_key != null && Object.keys(this.variation_item.create_variation_data.all_attribute_name).length == 1){
                            error_message.push('variation already exist');
                        }
                    })

                    if(error_message && error_message.length == 0){
                        this.item.all_variation.structured_variation.push(Object.assign({},this.variation_item.new_variation));
                        this.variation_item.create_variation_data = null;
                        this.variation_item.show_create_form = false;
                        console.log(this.item.all_variation);
                    }else{
                        this.showUserErrorMessage(error_message, 4000);
                    }

                }else{
                    let temp = {
                        structured_variation: [Object.assign({},this.variation_item.new_variation)],
                        all_attribute_name: this.variation_item.create_variation_data.all_attribute_name
                    };
                    this.item.all_variation = temp;
                    this.variation_item.create_variation_data = null;
                    this.variation_item.show_create_form = false;
                }
            }
        },
        //---------------------------------------------------------------------
        showUserErrorMessage(message, time = 2500){
            this.user_error_message = message;
            setTimeout(()=>{
                this.user_error_message = [];
            },time);
        },

        //---------------------------------------------------------------------
        async getAssets() {

            if(this.assets_is_fetching === true){
                this.assets_is_fetching = false;

                await vaah().ajax(
                    this.ajax_url+'/assets',
                    this.afterGetAssets,
                );
            }
        },
        //---------------------------------------------------------------------
        afterGetAssets(data, res)
        {
            if(data)
            {
                this.assets = data;
                this.product_status = data.taxonomy.product_status;
                this.active_brands = data.active_brands;
                this.active_stores = data.active_stores;
                this.types = data.taxonomy.types;
                this.active_vendors = data.active_vendors;
                this.selected_vendor = data.default_vendor;
                this.product_vendor_status = data.taxonomy.product_vendor_status;
                if(data.rows)
                {
                    this.query.rows = data.rows;
                }

                if(this.route.params && !this.route.params.id){
                    this.item = vaah().clone(data.empty_item);
                }

            }
        },
        //---------------------------------------------------------------------
        async getList() {
            let options = {
                query: vaah().clone(this.query)
            };
            await vaah().ajax(
                this.ajax_url,
                this.afterGetList,
                options
            );
        },
        //---------------------------------------------------------------------
        afterGetList: function (data, res)
        {
            if(data)
            {
                this.list = data;
            }
        },
        //---------------------------------------------------------------------

        async getItem(id) {
            if(id){
                await vaah().ajax(
                    ajax_url+'/'+id,
                    this.getItemAfter
                );
            }
        },
        //---------------------------------------------------------------------
        async getItemAfter(data, res)
        {
            if(data)
            {
                this.item = data;
            }else{
                this.$router.push({name: 'products.index'});
            }
            await this.getItemMenu();
            await this.getFormMenu();
        },
        //---------------------------------------------------------------------
        isListActionValid()
        {

            if(!this.action.type)
            {
                vaah().toastErrors(['Select an action type']);
                return false;
            }

            if(this.action.items.length < 1)
            {
                vaah().toastErrors(['Select records']);
                return false;
            }

            return true;
        },
        //---------------------------------------------------------------------
        async updateList(type = null){

            if(!type && this.action.type)
            {
                type = this.action.type;
            } else{
                this.action.type = type;
            }

            if(!this.isListActionValid())
            {
                return false;
            }


            let method = 'PUT';

            switch (type)
            {
                case 'delete':
                    method = 'DELETE';
                    break;
            }

            let options = {
                params: this.action,
                method: method,
                show_success: false
            };
            await vaah().ajax(
                this.ajax_url,
                this.updateListAfter,
                options
            );
        },
        //---------------------------------------------------------------------
        async updateListAfter(data, res) {
            if(data)
            {
                this.action = vaah().clone(this.empty_action);
                await this.getList();
            }
        },
        //---------------------------------------------------------------------
        async listAction(type = null){

            if(!type && this.action.type)
            {
                type = this.action.type;
            } else{
                this.action.type = type;
            }

            let url = this.ajax_url+'/action/'+type
            let method = 'PUT';

            switch (type)
            {
                case 'delete':
                    url = this.ajax_url
                    method = 'DELETE';
                    break;
                case 'delete-all':
                    method = 'DELETE';
                    break;
            }

            this.action.filter = this.query.filter;

            let options = {
                params: this.action,
                method: method,
                show_success: false
            };
            await vaah().ajax(
                url,
                this.updateListAfter,
                options
            );
        },
        //---------------------------------------------------------------------
        async itemAction(type, item=null){
            if(!item)
            {
                item = this.item;
            }

            this.form.action = type;

            let ajax_url = this.ajax_url;

            let options = {
                method: 'post',
            };

            /**
             * Learn more about http request methods at
             * https://www.youtube.com/watch?v=tkfVQK6UxDI
             */
            switch (type)
            {
                /**
                 * Create a record, hence method is `POST`
                 * https://docs.vaah.dev/guide/laravel.html#create-one-or-many-records
                 */
                case 'create-and-new':
                case 'create-and-close':
                case 'create-and-clone':
                    options.method = 'POST';
                    options.params = item;
                    break;

                /**
                 * Update a record with many columns, hence method is `PUT`
                 * https://docs.vaah.dev/guide/laravel.html#update-a-record-update-soft-delete-status-change-etc
                 */
                case 'save':
                case 'save-and-close':
                case 'save-and-clone':
                    options.method = 'PUT';
                    options.params = item;
                    ajax_url += '/'+item.id
                    break;
                case 'save-variation':
                    options.method = 'POST';
                    options.params = item;
                    ajax_url += '/variation'
                    break;

                case 'save-vendor':
                    options.method = 'POST';
                    options.params = item;
                    ajax_url += '/vendor'
                    break;
                /**
                 * Delete a record, hence method is `DELETE`
                 * and no need to send entire `item` object
                 * https://docs.vaah.dev/guide/laravel.html#delete-a-record-hard-deleted
                 */
                case 'delete':
                    options.method = 'DELETE';
                    ajax_url += '/'+item.id
                    break;
                /**
                 * Update a record's one column or very few columns,
                 * hence the method is `PATCH`
                 * https://docs.vaah.dev/guide/laravel.html#update-a-record-update-soft-delete-status-change-etc
                 */
                default:
                    options.method = 'PATCH';
                    ajax_url += '/'+item.id+'/action/'+type;
                    break;
            }

            await vaah().ajax(
                ajax_url,
                this.itemActionAfter,
                options
            );
        },
        //---------------------------------------------------------------------
        async itemActionAfter(data, res)
        {
            if(data)
            {
                console.log(data);
                this.item = data;
                await this.getList();
                await this.formActionAfter(data);
                this.getItemMenu();

            }
        },
        //---------------------------------------------------------------------
        async formActionAfter (data)
        {
           
            switch (this.form.action)
            {
                case 'create-and-new':
                case 'save-and-new':
                    this.setActiveItemAsEmpty();
                    break;
                case 'create-and-close':
                case 'save-and-close':
                    this.setActiveItemAsEmpty();
                    this.$router.push({name: 'products.index'});
                    break;
                case 'save-and-clone':
                case 'create-and-clone':
                    this.item.id = null;
                    await this.getFormMenu();
                    break;
                case 'trash':
                case 'restore':
                case 'save':
                    this.item = data;
                    break;
                case 'delete':
                    this.item = null;
                    this.toList();
                    break;
            }
        },
        //---------------------------------------------------------------------
        async toggleIsActive(item)
        {
            if(item.is_active)
            {
                await this.itemAction('activate', item);
            } else{
                await this.itemAction('deactivate', item);
            }
        },
        //---------------------------------------------------------------------
        async paginate(event) {
            this.query.page = event.page+1;
            await this.getList();
            await this.updateUrlQueryString(this.query);
        },
        //---------------------------------------------------------------------
        async reload()
        {
            await this.getAssets();
            await this.getList();
        },
        //---------------------------------------------------------------------
        async getFormInputs () {
            let params = {
                model_namespace: this.model,
                except: this.assets.fillable.except,
            };

            let url = this.ajax_url+'/fill';

            await vaah().ajax(
                url,
                this.getFormInputsAfter,
            );
        },
        //---------------------------------------------------------------------
        getFormInputsAfter: function (data, res) {
            if(data)
            {
                let self = this;
                Object.keys(data.fill).forEach(function(key) {
                    self.item[key] = data.fill[key];
                });
            }
        },

        //---------------------------------------------------------------------

        //---------------------------------------------------------------------
        onItemSelection(items)
        {
            this.action.items = items;
        },
        //---------------------------------------------------------------------
        setActiveItemAsEmpty()
        {
            this.item = vaah().clone(this.assets.empty_item);
        },
        //---------------------------------------------------------------------
        confirmDelete()
        {
            if(this.action.items.length < 1)
            {
                vaah().toastErrors(['Select a record']);
                return false;
            }
            this.action.type = 'delete';
            vaah().confirmDialogDelete(this.listAction);
        },
        //---------------------------------------------------------------------
        confirmDeleteAll()
        {
            this.action.type = 'delete-all';
            vaah().confirmDialogDelete(this.listAction);
        },
        //---------------------------------------------------------------------
        async delayedSearch()
        {
            let self = this;
            this.query.page = 1;
            this.action.items = [];
            clearTimeout(this.search.delay_timer);
            this.search.delay_timer = setTimeout(async function() {
                await self.updateUrlQueryString(self.query);
                await self.getList();
            }, this.search.delay_time);
        },
        //---------------------------------------------------------------------
        async updateUrlQueryString(query)
        {
            //remove reactivity from source object
            query = vaah().clone(query)

            //create query string
            let query_string = qs.stringify(query, {
                skipNulls: true,
            });
            let query_object = qs.parse(query_string);

            if(query_object.filter){
                query_object.filter = vaah().cleanObject(query_object.filter);
            }

            //reset url query string
            await this.$router.replace({query: null});

            //replace url query string
            await this.$router.replace({query: query_object});

            //update applied filters
            this.countFilters(query_object);

        },
        //---------------------------------------------------------------------
        countFilters: function (query)
        {
            this.count_filters = 0;
            if(query && query.filter)
            {
                let filter = vaah().cleanObject(query.filter);
                this.count_filters = Object.keys(filter).length;
            }
        },
        //---------------------------------------------------------------------
        async clearSearch()
        {
            this.query.filter.q = null;
            await this.updateUrlQueryString(this.query);
            await this.getList();
        },
        //---------------------------------------------------------------------
        async resetQuery()
        {
            //reset query strings
            await this.resetQueryString();

            //reload page list
            await this.getList();
        },
        //---------------------------------------------------------------------
        async resetQueryString()
        {
            for(let key in this.query.filter)
            {
                this.query.filter[key] = null;
            }
            await this.updateUrlQueryString(this.query);
        },
        //---------------------------------------------------------------------
        closeForm()
        {
            this.$router.push({name: 'products.index'})
        },
        //---------------------------------------------------------------------
        toList()
        {
            this.item = vaah().clone(this.assets.empty_item);
            this.$router.push({name: 'products.index'})
        },
        //---------------------------------------------------------------------
        toForm()
        {
            this.item = vaah().clone(this.assets.empty_item);
            this.getFormMenu();
            this.$router.push({name: 'products.form'})
        },
        //---------------------------------------------------------------------
        toView(item)
        {
            this.item = vaah().clone(item);
            this.$router.push({name: 'products.view', params:{id:item.id}})
        },
        //---------------------------------------------------------------------
        toVariation(item)
        {
            this.item = vaah().clone(item);
            this.variation_item = vaah().clone(this.variation_item);
            this.$router.push({name: 'products.variation', params:{id:item.id}})
        },
        //---------------------------------------------------------------------
        toVendor(item)
        {
            this.item = vaah().clone(item);
            this.$router.push({name: 'products.vendor', params:{id:item.id}})
        },
        //---------------------------------------------------------------------

        toEdit(item)
        {
            this.item = item;
            this.$router.push({name: 'products.form', params:{id:item.id}})
        },
        //---------------------------------------------------------------------
        isViewLarge()
        {
            return this.view === 'large';
        },
        //---------------------------------------------------------------------
        getIdWidth()
        {
            let width = 50;

            if(this.list && this.list.total)
            {
                let chrs = this.list.total.toString();
                chrs = chrs.length;
                width = chrs*40;
            }

            return width+'px';
        },
        //---------------------------------------------------------------------
        getActionWidth()
        {
            let width = 100;
            if(!this.isViewLarge())
            {
                width = 80;
            }
            return width+'px';
        },
        //---------------------------------------------------------------------
        getActionLabel()
        {
            let text = null;
            if(this.isViewLarge())
            {
                text = 'Actions';
            }

            return text;
        },
        //---------------------------------------------------------------------
        async getListSelectedMenu()
        {
            this.list_selected_menu = [
                {
                    label: 'Activate',
                    command: async () => {
                        await this.updateList('activate')
                    }
                },
                {
                    label: 'Deactivate',
                    command: async () => {
                        await this.updateList('deactivate')
                    }
                },
                {
                    separator: true
                },
                {
                    label: 'Trash',
                    icon: 'pi pi-times',
                    command: async () => {
                        await this.updateList('trash')
                    }
                },
                {
                    label: 'Restore',
                    icon: 'pi pi-replay',
                    command: async () => {
                        await this.updateList('restore')
                    }
                },
                {
                    label: 'Delete',
                    icon: 'pi pi-trash',
                    command: () => {
                        this.confirmDelete()
                    }
                },
            ]
            this.variation_selected_menu = [
                {
                    label: 'Remove',
                    icon: 'pi pi-trash',
                    command: () => {
                        this.bulkRemoveProductVariation()
                    }
                },
            ]

            this.vendor_selected_menu = [
                {
                    label: 'Remove',
                    icon: 'pi pi-trash',
                    command: () => {
                        this.bulkRemoveVendor()
                    }
                },
            ]
        },

        //---------------------------------------------------------------------

        getListBulkMenu()
        {
            this.list_bulk_menu = [
                {
                    label: 'Mark all as active',
                    command: async () => {
                        await this.listAction('activate-all')
                    }
                },
                {
                    label: 'Mark all as inactive',
                    command: async () => {
                        await this.listAction('deactivate-all')
                    }
                },
                {
                    separator: true
                },
                {
                    label: 'Trash All',
                    icon: 'pi pi-times',
                    command: async () => {
                        await this.listAction('trash-all')
                    }
                },
                {
                    label: 'Restore All',
                    icon: 'pi pi-replay',
                    command: async () => {
                        await this.listAction('restore-all')
                    }
                },
                {
                    label: 'Delete All',
                    icon: 'pi pi-trash',
                    command: async () => {
                        this.confirmDeleteAll();
                    }
                },
            ];
        },
        //---------------------------------------------------------------------
        getItemMenu()
        {
            let item_menu = [];

            if(this.item && this.item.deleted_at)
            {

                item_menu.push({
                    label: 'Restore',
                    icon: 'pi pi-refresh',
                    command: () => {
                        this.itemAction('restore');
                    }
                });
            }

            if(this.item && this.item.id && !this.item.deleted_at)
            {
                item_menu.push({
                    label: 'Trash',
                    icon: 'pi pi-times',
                    command: () => {
                        this.itemAction('trash');
                    }
                });
            }

            item_menu.push({
                label: 'Delete',
                icon: 'pi pi-trash',
                command: () => {
                    this.confirmDeleteItem('delete');
                }
            });

            this.item_menu_list = item_menu;
        },
        //---------------------------------------------------------------------
        async getListCreateMenu()
        {
            let form_menu = [];

            form_menu.push(
                {
                    label: 'Create 100 Records',
                    icon: 'pi pi-pencil',
                    command: () => {
                        this.listAction('create-100-records');
                    }
                },
                {
                    label: 'Create 1000 Records',
                    icon: 'pi pi-pencil',
                    command: () => {
                        this.listAction('create-1000-records');
                    }
                },
                {
                    label: 'Create 5000 Records',
                    icon: 'pi pi-pencil',
                    command: () => {
                        this.listAction('create-5000-records');
                    }
                },
                {
                    label: 'Create 10,000 Records',
                    icon: 'pi pi-pencil',
                    command: () => {
                        this.listAction('create-10000-records');
                    }
                },

            )

            this.list_create_menu = form_menu;

        },

        //---------------------------------------------------------------------
        confirmDeleteItem()
        {
            this.form.type = 'delete';
            vaah().confirmDialogDelete(this.confirmDeleteItemAfter);
        },
        //---------------------------------------------------------------------
        confirmDeleteItemAfter()
        {
            this.itemAction('delete', this.item);
        },
        //---------------------------------------------------------------------
        async getFormMenu()
        {
            let form_menu = [];

            if(this.item && this.item.id)
            {
                let is_deleted = !!this.item.deleted_at;
                form_menu = [
                    {
                        label: 'Save & Close',
                        icon: 'pi pi-check',
                        command: () => {

                            this.itemAction('save-and-close');
                        }
                    },
                    {
                        label: 'Save & Clone',
                        icon: 'pi pi-copy',
                        command: () => {

                            this.itemAction('save-and-clone');

                        }
                    },
                    {
                        label: is_deleted ? 'Restore': 'Trash',
                        icon: is_deleted ? 'pi pi-refresh': 'pi pi-times',
                        command: () => {
                            this.itemAction(is_deleted ? 'restore': 'trash');
                        }
                    },
                    {
                        label: 'Delete',
                        icon: 'pi pi-trash',
                        command: () => {
                            this.confirmDeleteItem('delete');
                        }
                    },
                ];

            } else{
                form_menu = [
                    {
                        label: 'Create & Close',
                        icon: 'pi pi-check',
                        command: () => {
                            this.itemAction('create-and-close');
                        }
                    },
                    {
                        label: 'Create & Clone',
                        icon: 'pi pi-copy',
                        command: () => {

                            this.itemAction('create-and-clone');

                        }
                    },
                    {
                        label: 'Reset',
                        icon: 'pi pi-refresh',
                        command: () => {
                            this.setActiveItemAsEmpty();
                        }
                    }
                ];
            }

            form_menu.push({
                label: 'Fill',
                icon: 'pi pi-pencil',
                command: () => {
                    this.getFormInputs();
                }
            },)

            this.form_menu_list = form_menu;

        },
        //---------------------------------------------------------------------
    }
});



// Pinia hot reload
if (import.meta.hot) {
    import.meta.hot.accept(acceptHMRUpdate(useProductStore, import.meta.hot))
}
