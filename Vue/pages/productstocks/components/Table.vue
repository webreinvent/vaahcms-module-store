<script setup>
import { vaah } from '../../../vaahvue/pinia/vaah'
import { useProductStockStore } from '../../../stores/store-productstocks'

const store = useProductStockStore();
const useVaah = vaah();

</script>

<template>

    <div v-if="store.list" style=" display: flex;flex-direction: column;justify-content: center; height: 100%;">
        <!--table-->
         <DataTable :value="store.list.data"
                   dataKey="id"
                   :rowClass="(rowData) => rowData.id === store.item?.id ? 'bg-blue-100' : ''"
                   class="p-datatable-sm p-datatable-hoverable-rows"
                   v-model:selection="store.action.items"
                   stripedRows
                   responsiveLayout="scroll">

            <Column selectionMode="multiple"
                    v-if="store.isViewLarge()"
                    headerStyle="width: 3em">
            </Column>
             <Column field="id" header="ID" :style="{width: store.getIdWidth()}" :sortable="true">
            </Column>

             <Column field="vendor.name" header="Vendor"
                     :sortable="true">

                 <template #body="prop">
                     <Badge v-if="prop.data.vendor && prop.data.vendor.deleted_at"
                            value="Trashed"
                            severity="danger"></Badge>
                     <div style="word-break: break-word;" v-if="prop.data.vendor && prop.data.vendor.name">{{ prop.data.vendor.name }}</div>
                 </template>

             </Column>

             <Column field="product.name" header="Product"
                     :sortable="true">

                 <template #body="prop" >
                     <Badge v-if="prop.data.product && prop.data.product.deleted_at"
                            value="Trashed"
                            severity="danger"></Badge>
                        <div style="word-break: break-word;" v-if="prop.data.product && prop.data.product.name">
                            {{ prop.data.product.name }}
                        </div>
                 </template>

             </Column>

             <Column field="product_variation.name" header="Product Variation"
                     :sortable="true">

                 <template #body="prop" >
                     <Badge v-if="prop.data.product_variation && prop.data.product_variation.deleted_at"
                            value="Trashed"
                            severity="danger"></Badge>
                     <div style="word-break: break-word;" v-if="prop.data.product_variation && prop.data.product_variation.name">
                         {{ prop.data.product_variation.name }}
                     </div>
                 </template>

             </Column>

             <Column field="quantity" header="Quantity"
                     :sortable="true">

                 <template #body="prop">
                     <Badge v-if="prop.data.quantity == 0"
                            value="0"
                            severity="danger"></Badge>
                     <Badge v-else-if="prop.data.quantity > 0"
                            :value="prop.data.quantity"
                            severity="info"></Badge>
                 </template>

             </Column>



             <Column field="status" header="Status">
                 <template #body="prop">
                     <Badge v-if="prop.data.status && prop.data.status.slug == 'approved'"
                            severity="success"> {{prop.data.status.name}} </Badge>
                     <Badge v-else-if="!prop.data.status"
                            severity="primary"> null </Badge>
                     <Badge v-else-if="prop.data.status && prop.data.status.slug == 'rejected'"
                            severity="danger"> {{prop.data.status.name}} </Badge>
                     <Badge v-else
                            severity="warning"> {{prop.data.status.name}} </Badge>
                 </template>
             </Column>

            <Column field="is_active" v-if="store.isViewLarge()"
                    style="width:100px;"
                    header="Is Active">

                <template #body="prop">
                    <InputSwitch v-model.bool="prop.data.is_active"
                                 data-testid="productstocks-table-is-active"
                                 v-bind:false-value="0"  v-bind:true-value="1"
                                 class="p-inputswitch-sm"
                                 @input="store.toggleIsActive(prop.data)">
                    </InputSwitch>
                </template>

            </Column>

            <Column field="actions" style="width:150px;"
                    :style="{width: store.getActionWidth() }"
                    :header="store.getActionLabel()">

                <template #body="prop">
                    <div class="p-inputgroup ">

                        <Button class="p-button-tiny p-button-text"
                                data-testid="productstocks-table-to-view"
                                v-tooltip.top="'View'"
                                :disabled="$route.path.includes('view') && prop.data.id===store.item?.id"
                                @click="store.toView(prop.data)"
                                icon="pi pi-eye" />

                        <Button v-if="store.assets.permissions.includes('can-update-module')"
                                class="p-button-tiny p-button-text"
                                data-testid="productstocks-table-to-edit"
                                v-tooltip.top="'Update'"
                                :disabled="$route.path.includes('form') && prop.data.id===store.item?.id"
                                @click="store.toEdit(prop.data)"
                                icon="pi pi-pencil" />

                        <Button
                                class="p-button-tiny p-button-danger p-button-text"
                                data-testid="productstocks-table-action-trash"
                                v-if="store.isViewLarge() && !prop.data.deleted_at && store.assets.permissions.includes('can-update-module')"
                                @click="store.itemAction('trash', prop.data)"
                                v-tooltip.top="'Trash'"
                                icon="pi pi-trash" />


                        <Button
                                class="p-button-tiny p-button-success p-button-text"
                                data-testid="productstocks-table-action-restore"
                                v-if="store.isViewLarge() && prop.data.deleted_at && store.assets.permissions.includes('can-update-module')"
                                @click="store.itemAction('restore', prop.data)"
                                v-tooltip.top="'Restore'"
                                icon="pi pi-replay" />


                    </div>

                </template>


            </Column>

             <template #empty="prop">

                 <div class="no-record-message" style="text-align: center;font-size: 12px; color: #888;">No records found.</div>

             </template>


        </DataTable>
        <!--/table-->

        <!--paginator-->
        <Paginator v-model:rows="store.query.rows"
                   :totalRecords="store.list.total"
                   @page="store.paginate($event)"
                   :rowsPerPageOptions="store.rows_per_page"
                   class="bg-white-alpha-0 pt-2">
        </Paginator>
        <!--/paginator-->

    </div>

</template>
