let routes= [];
let routes_list= [];

import List from '../pages/orders/List.vue'
import Form from '../pages/orders/Form.vue'
import Item from '../pages/orders/Item.vue'
import OrderItems from '../pages/orders/OrderItem.vue'


routes_list = {

    path: '/orders',
    name: 'orders.index',
    component: List,
    props: true,
    children:[
        {
            path: 'form/:id?',
            name: 'orders.form',
            component: Form,
            props: true,
        },
        {
            path: 'view/:id?',
            name: 'orders.view',
            component: Item,
            props: true,
        },
        {
            path: ':order_id?/products',
            name: 'orders.orderitems',
            component: OrderItems,
            props: true,
        }
    ]
};

routes.push(routes_list);

export default routes;

