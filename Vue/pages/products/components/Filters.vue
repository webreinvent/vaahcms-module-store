<script  setup>

import { useProductStore } from '../../../stores/store-products'
import VhFieldVertical from './../../../vaahvue/vue-three/primeflex/VhFieldVertical.vue'
import {computed, onMounted} from "vue";


const store = useProductStore();

onMounted(async () => {

    await store.getCategories();

});


</script>

<template>
    <div>

        <Sidebar v-model:visible="store.show_filters"
                 position="right">

            <VhFieldVertical>
                <template #label>
                    <b>Price Range:</b>
                </template>
                <div>
                    <InputNumber v-model="store.query.filter.min_price" placeholder="Minimum Price" class="w-full" @input="store.minPrice($event)" inputId="integeronly" />
                    <InputNumber v-model="store.query.filter.max_price" placeholder="Maximum Price" class="w-full" @input="store.maxPrice($event)" inputId="integeronly" />
                </div>
            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Categories By:</b>
                </template>
                <TreeSelect
                    v-model="store.product_category_filter"
                    :options="store.categories_dropdown_data"
                    selectionMode="multiple"
                    display="chip"
                    @node-select="store.selectCategoryForFilter($event)"
                    @node-unselect="store.removeCategoryForFilter($event)"
                    placeholder="Select Category"
                    :show-count="true"
                    data-testid="products-category"

                    class=" w-full" />
            </VhFieldVertical>
            <VhFieldVertical >
                <template #label>
                    <b>Status:</b>
                </template>
                <VhField label="Product Status">
                    <MultiSelect
                        v-model="store.query.filter.status"
                        :options="store.assets.taxonomy.product_status"
                        filter
                        optionValue="slug"
                        optionLabel="name"
                        placeholder="Select Status"
                        display="chip"
                        append-to="self"
                        class="w-full relative" />
                </VhField>


            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Product Variation:</b>
                </template>

                <AutoComplete name="products-variation-filter"
                              data-testid="products-variation-filter"
                              v-model="store.selected_product_variations"
                              @change = "store.addProductVariation()"
                              option-label = "name"
                              multiple
                              :complete-on-focus = "true"
                              :suggestions="store.filtered_product_variations"
                              @complete="store.searchProductVariation"
                              placeholder="Select Product Variation"
                              class="w-full "
                              append-to="self"
                              :pt="{
                                              token: {
                        class: 'max-w-full'
                    },
                    removeTokenIcon: {
                    class: 'min-w-max'
                    },
                    item: { style: {
                    textWrap: 'wrap'
                    }  },
                    panel: { class: 'w-16rem ' }
                                                }"
                />

            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Vendor:</b>
                </template>

                <AutoComplete name="products-vendor-filter"
                              data-testid="products-vendor-filter"
                              v-model="store.selected_vendors"
                              @change = "store.addProductVendor()"
                              option-label = "name"
                              multiple
                              :complete-on-focus = "true"
                              :suggestions="store.filtered_vendors"
                              @complete="store.searchVendor($event)"
                              placeholder="Select Vendor"
                              class="w-full "
                              append-to="self"
                              :pt="{ token:
                               {class: 'max-w-full'},
                               removeTokenIcon: {class: 'min-w-max'},
                               item: { style: {
                                      textWrap: 'wrap'
                                      }  },
                               panel: { class: 'w-16rem ' }
                                                }"/>

            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Brand:</b>
                </template>

                <AutoComplete name="products-brand-filter"
                              data-testid="products-brand-filter"
                              v-model="store.filter_selected_brands"
                              @change = "store.addFilterBrand()"
                              option-label = "name"
                              multiple
                              :complete-on-focus = "true"
                              :suggestions="store.filtered_brands"
                              @complete="store.searchBrand"
                              placeholder="Select Brand"
                              class="w-full "
                              append-to="self"
                              :pt="{
                                              token: {
                        class: 'max-w-full'
                    },
                    removeTokenIcon: {
                    class: 'min-w-max'
                    },
                    item: { style: {
                    textWrap: 'wrap'
                    }  },
                    panel: { class: 'w-16rem ' }
                                                }"/>

            </VhFieldVertical>


            <VhFieldVertical >
                <template #label>
                    <b>Product Type:</b>
                </template>

                <AutoComplete name="products-type-filter"
                              data-testid="products-type-filter"
                              v-model="store.filter_selected_product_type"
                              @change = "store.addFilterProductType()"
                              option-label = "name"
                              multiple
                              :complete-on-focus = "true"
                              :suggestions="store.type_suggestion"
                              @complete="store.searchTaxonomyProduct($event)"
                              placeholder="Select Product Type"
                              append-to="self"
                              class="w-full " />

            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Store:</b>
                </template>

                <VhField label="Store*">

                    <AutoComplete
                        name="products-filter-store"
                        data-testid="products-filter-store"
                        v-model="store.filter_selected_store"
                        @change="store.setFilterStore($event)"
                        option-label = "name"
                        multiple
                        :complete-on-focus = "true"
                        class="w-full"
                        :suggestions="store.filtered_stores"
                        @complete="store.searchStore"
                        placeholder="Select Store"
                        append-to="self"
                        :pt="{
                                              token: {
                        class: 'max-w-full'
                    },
                    removeTokenIcon: {
                    class: 'min-w-max'
                    },
                    item: { style: {
                    textWrap: 'wrap'
                    }  },
                    panel: { class: 'w-16rem ' }
                                                }"
                    />


                </VhField>

            </VhFieldVertical>
            <br/>
            <VhFieldVertical >
                <template #label>
                    <b>Quantity Count Range:</b>
                </template>

                <div class="card flex justify-content-center">
                    <div class="w-14rem">

                        <InputNumber
                            v-model="store.query.filter.min_quantity"
                            data-testid="product-filter-min_quantity"
                            placeholder="Enter minimum quantity"
                            @input="store.minQuantity($event)"
                            class="w-14rem mt-2"

                        />
                        <InputNumber
                            v-model="store.query.filter.max_quantity"
                            data-testid="product-filter-max_quantity"
                            placeholder="Enter maximum quantity"
                            @input="store.maxQuantity($event)"
                            class="w-14rem mt-2"

                        />
                    </div>
                </div>

            </VhFieldVertical>


            <br/>



            <VhFieldVertical >
                <template #label>
                    <b>Sort By:</b>
                </template>

                <div class="field-radiobutton">
                    <RadioButton name="sort-none"
                                 inputId="sort-none"
                                 data-testid="products-filters-sort-none"
                                 value=""
                                 v-model="store.query.filter.sort" />
                    <label for="sort-none" class="cursor-pointer">None</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="sort-ascending"
                                 inputId="sort-ascending"
                                 data-testid="products-filters-sort-ascending"
                                 value="updated_at"
                                 v-model="store.query.filter.sort" />
                    <label for="sort-ascending" class="cursor-pointer">Updated (Ascending)</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="sort-descending"
                                 inputId="sort-descending"
                                 data-testid="products-filters-sort-descending"
                                 value="updated_at:desc"
                                 v-model="store.query.filter.sort" />
                    <label for="sort-descending" class="cursor-pointer">Updated (Descending)</label>
                </div>

            </VhFieldVertical>

            <Divider/>

            <VhFieldVertical >
                <template #label>
                    <b>Select Created Date:</b>
                </template>

                <Calendar v-model="store.selected_dates"
                          selectionMode="range"
                          @date-select="store.setDateRange"
                          :manualInput="false"
                          placeholder="Choose Date Range"
                          class="w-full "

                />

            </VhFieldVertical >

            <VhFieldVertical >
                <template #label>
                    <b>Is Active:</b>
                </template>

                <div class="field-radiobutton">
                    <RadioButton name="active-all"
                                 inputId="active-all"
                                 value="null"
                                 data-testid="products-filters-active-all"
                                 v-model="store.query.filter.is_active" />
                    <label for="active-all" class="cursor-pointer">All</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="active-true"
                                 inputId="active-true"
                                 data-testid="products-filters-active-true"
                                 value="true"
                                 v-model="store.query.filter.is_active" />
                    <label for="active-true" class="cursor-pointer">Only Active</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="active-false"
                                 inputId="active-false"
                                 data-testid="products-filters-active-false"
                                 value="false"
                                 v-model="store.query.filter.is_active" />
                    <label for="active-false" class="cursor-pointer">Only Inactive</label>
                </div>

            </VhFieldVertical>

            <VhFieldVertical >
                <template #label>
                    <b>Trashed:</b>
                </template>

                <div class="field-radiobutton">
                    <RadioButton name="trashed-exclude"
                                 inputId="trashed-exclude"
                                 data-testid="products-filters-trashed-exclude"
                                 value=""
                                 v-model="store.query.filter.trashed" />
                    <label for="trashed-exclude" class="cursor-pointer">Exclude Trashed</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="trashed-include"
                                 inputId="trashed-include"
                                 data-testid="products-filters-trashed-include"
                                 value="include"
                                 v-model="store.query.filter.trashed" />
                    <label for="trashed-include" class="cursor-pointer">Include Trashed</label>
                </div>
                <div class="field-radiobutton">
                    <RadioButton name="trashed-only"
                                 inputId="trashed-only"
                                 data-testid="products-filters-trashed-only"
                                 value="only"
                                 v-model="store.query.filter.trashed" />
                    <label for="trashed-only" class="cursor-pointer">Only Trashed</label>
                </div>

            </VhFieldVertical>


        </Sidebar>

    </div>
</template>
