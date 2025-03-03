<script setup>
import {onMounted, ref, watch} from "vue";
import { useProductStockStore } from '../../stores/store-productstocks'

import VhField from './../../vaahvue/vue-three/primeflex/VhField.vue'
import {useRoute} from 'vue-router';


const store = useProductStockStore();
const route = useRoute();

onMounted(async () => {
    if(route.params && route.params.id)
    {
        await store.getItem(route.params.id);
    }

    await store.getFormMenu();
});

//--------form_menu
const form_menu = ref();
const toggleFormMenu = (event) => {
    form_menu.value.toggle(event);
};
//--------/form_menu

</script>
<template>

    <div class="col-6" >

        <Panel class="is-small">

            <template class="p-1" #header>


                <div class="flex flex-row">
                    <div class="p-panel-title">
                        <span v-if="store.item && store.item.id">
                            Update
                        </span>
                        <span v-else>
                            Create
                        </span>
                    </div>

                </div>


            </template>

            <template #icons>


                <div class="p-inputgroup">
                    <Button class="p-button-sm"
                            v-if="store.item && store.item.id"
                            data-testid="productstocks-view_item"
                            @click="store.toView(store.item)"
                            icon="pi pi-eye"/>
                    <Button :disabled="!store.assets.permissions.includes('can-update-module')"
                            label="Save"
                            class="p-button-sm"
                            v-if="store.item && store.item.id"
                            data-testid="productstocks-save"
                            @click="store.itemAction('save')"
                            icon="pi pi-save"/>

                    <Button :disabled="!store.assets.permissions.includes('can-update-module')"
                            label="Create & New"
                            v-else
                            @click="store.itemAction('create-and-new')"
                            class="p-button-sm"
                            data-testid="productstocks-create-and-new"
                            icon="pi pi-save"/>

                    <Button data-testid="productstocks-document" icon="pi pi-info-circle"
                            href="https://vaah.dev/store"
                            v-tooltip.top="'Documentation'"
                            class="p-button-sm"
                            onclick=" window.open('https://vaah.dev/store','_blank')"/>


                    <!--form_menu-->
                    <Button
                        :disabled="!store.assets.permissions.includes('can-update-module')"
                        type="button"
                        @click="toggleFormMenu"
                        class="p-button-sm"
                        data-testid="productstocks-form-menu"
                        icon="pi pi-angle-down"
                        aria-haspopup="true"/>

                    <Menu ref="form_menu"
                          :model="store.form_menu_list"
                          :popup="true" />
                    <!--/form_menu-->


                    <Button class="p-button-primary p-button-sm"
                            icon="pi pi-times"
                            data-testid="productstocks-to-list"
                            @click="store.toList()">
                    </Button>
                </div>



            </template>


            <div v-if="store.item" class="pt-2">

                <VhField label="Vendor*">
                    <AutoComplete v-model="store.item.vendor"
                                  @change="store.setVendor($event)"
                                  value="id"
                                  class="w-full"
                                  data-testid="productstocks-vh_st_vendor_id"
                                  :suggestions="store.vendors_suggestion"
                                  @complete="store.searchVendor($event)"
                                  :dropdown="true"
                                  optionLabel="name"
                                  placeholder="Select Vendor"
                                  forceSelection
                                  :pt="{
                          token: {
                                    class: 'max-w-full'
                                  },
                          removeTokenIcon: {
                                    class: 'min-w-max'
                          },
                          item: { style:
                                {
                                textWrap: 'wrap'
                                }  },
                          panel: { class: 'w-16rem ' }
                            }">
                        <template #option="slotProps">
                            <div class="flex align-options-center">
                                <div>{{ slotProps.option.name }}<span v-if="slotProps.option.is_default == 1"> (Default) </span></div>
                            </div>
                        </template>
                    </AutoComplete>
                </VhField>

                <VhField label="Product*">
                    <AutoComplete v-model="store.item.product"
                                  @change="store.setProduct($event)"
                                  value="id"
                                  class="w-full"
                                  data-testid="productstocks-vh_st_product_id"
                                  :suggestions="store.products_suggestion"
                                  @complete="store.searchProduct($event)"
                                  :dropdown="true"
                                  optionLabel="name"
                                  placeholder="Select Product"
                                  forceSelection
                                  :pt="{
                          token: {
                                    class: 'max-w-full'
                                  },
                          removeTokenIcon: {
                                    class: 'min-w-max'
                          },
                          item: { style:
                                {
                                textWrap: 'wrap'
                                }  },
                          panel: { class: 'w-16rem ' }
                            }">
                        <template #option="slotProps">
                            <div class="flex align-options-center">
                                <div>{{ slotProps.option.name }}<span v-if="slotProps.option.is_default == 1"> (Default) </span></div>
                            </div>
                        </template>
                    </AutoComplete>
                </VhField>

                <VhField label="Product Variation*">
                    <AutoComplete v-model="store.item.product_variation"
                                  @change="store.setProductVariation($event)"
                                  value="id"
                                  class="w-full"
                                  data-testid="productstocks-vh_st_product_variation_id"
                                  :suggestions="store.product_variations_suggestion"
                                  @complete="store.searchProductVariation($event)"
                                  :dropdown="true"
                                  optionLabel="name"
                                  placeholder="Select Product Variation"
                                  forceSelection
                                  :pt="{
                          token: {
                                    class: 'max-w-full'
                                  },
                          removeTokenIcon: {
                                    class: 'min-w-max'
                          },
                          item: { style:
                                {
                                textWrap: 'wrap'
                                }  },
                          panel: { class: 'w-16rem ' }
                            }">
                        <template #option="slotProps">
                            <div class="flex align-options-center">
                                <div>{{ slotProps.option.name }}<span v-if="slotProps.option.is_default == 1"> (Default) </span></div>
                            </div>
                        </template>
                    </AutoComplete>
                </VhField>

                <VhField label="Warehouse*">
                    <AutoComplete v-model="store.item.warehouse"
                                  @change="store.setWarehouse($event)"
                                  value="id"
                                  class="w-full"
                                  data-testid="productstocks-vh_st_warehouse_id"
                                  :suggestions="store.warehouses_suggestion"
                                  @complete="store.searchWarehouse($event)"
                                  :dropdown="true"
                                  optionLabel="name"
                                  placeholder="Select Warehouse"
                                  forceSelection
                                  :pt="{
                          token: {
                                    class: 'max-w-full'
                                  },
                          removeTokenIcon: {
                                    class: 'min-w-max'
                          },
                          item: { style:
                                {
                                textWrap: 'wrap'
                                }  },
                          panel: { class: 'w-16rem ' }
                            }">
                        <template #option="slotProps">
                            <div class="flex align-options-center">
                                <div>{{ slotProps.option.name }}<span v-if="slotProps.option.is_default == 1"> (Default) </span></div>
                            </div>
                        </template>
                    </AutoComplete>
                </VhField>

                <VhField label="Quantity*">
                    <InputNumber
                        name="productstocks-quantity"
                        v-model="store.item.quantity"
                        mode="decimal" showButtons
                        placeholder="Enter Quantity"
                        class="w-full"
                        data-testid="productstocks-quantity"
                        :min="0"/>
                </VhField>

                <VhField label="Status*">
                    <AutoComplete v-model="store.item.status"
                                  @change="store.setStatus($event)"
                                  value="id"
                                  class="w-full"
                                  data-testid="productstocks-taxonomy_id_product_stock_status"
                                  :suggestions="store.status_suggestion"
                                  @complete="store.searchStatus($event)"
                                  :dropdown="true"
                                  optionLabel="name"
                                  placeholder="Select Status"
                                  forceSelection />
                </VhField>

                <VhField label="Status Notes">
                    <Textarea placeholder="Enter Status Note" v-model="store.item.status_notes"
                              data-testid="productstocks-taxonomy_status_notes" :autoResize="true"
                              rows="3" class="w-full" />
                </VhField>


                <VhField label="Is Active">
                    <InputSwitch v-bind:false-value="0"
                                 v-bind:true-value="1"
                                 class="p-inputswitch"
                                 name="productstocks-active"
                                 data-testid="productstocks-active"
                                 v-model="store.item.is_active"/>
                </VhField>

            </div>
        </Panel>

    </div>

</template>
