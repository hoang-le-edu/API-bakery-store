<template>
    <div v-if="isVisible" class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center"
         @click="close">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-[90%] h-auto lg:w-full relative" @click.stop>
            <div class="flex flex-row items-center justify-between mb-4">
                <p class="text-black text-2xl font-semibold font-['Inter'] leading-[28.80px]">Select Cart</p>
                <button @click="close" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="details_topping flex flex-col max-h-[400px] overflow-y-auto scrollbar-thin p-2">
                <div v-for="cart in listCart" :key="cart.order_id"
                     @click="toggleSelectedCart(cart)"
                     :class="{'bg-gray-200': isSelectedCart(cart.order_id), 'bg-white': !isSelectedCart(cart.order_id)}"
                     class="flex flex-col justify-between border-b border-gray-300 py-3 m-1 shadow-lg  rounded-lg p-2 relative cursor-pointer transition-all duration-200 ease-in-out">
                    <div class="flex justify-between">
                        <div class="flex flex-col gap-2 w-2/3 ml-2">
                            <p class="text-black font-bold topping_content">{{ cart.name }}</p>
                            <p class="text-[14px] leading-none text-gray-600">{{ cart.type }}</p>
                        </div>
                        <button class="rounded-full bg-gray-100 hover:bg-gray-300 p-1 h-fit"
                                @click.stop="toggleToppings(cart.order_id)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Show order items only when toggled -->
                    <div v-if="isToppingsVisible(cart.order_id)"
                         class="p-2 w-[300px]">
                        <span v-if="cart.order_detail.length === 0" class="text-gray-500 text-sm">Empty cart</span>
                        <div v-for="(item, index) in cart.order_detail" :key="item.id" class="order-item mb-4">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="font-semibold">{{ item.product_name }} ({{ item.size }})</div>
                                    <span class="ml-2 text-sm text-gray-500">x{{ item.quantity }}</span>
                                </div>
                            </div>
                            <div class="toppings mt-2 text-sm">
                                <div v-for="(topping, toppingIndex) in item.toppings" :key="toppingIndex"
                                     class="flex justify-between w-full">
                                    <div>{{ topping.name }} (+{{ formatVietnameseCurrency(topping.price) }})</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter_btn flex items-center justify-end mt-5">
                <button @click="close"
                        class="w-full justify-center sm:w-auto text-md text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900"
                >
                    Cancel
                </button>
                <button :disabled="selectedCarts.length === 0"
                        @click="addProductToCart(selectedCarts)"
                        class="active_btn ml-2 bg-[#6B4226] text-white px-4 py-2 rounded-full font-semibold"
                        :class="{'opacity-50 cursor-not-allowed': selectedCarts.length === 0}">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {defineProps, defineEmits, ref} from 'vue';
import { useStore } from 'vuex';
import {notify} from "notiwind";
import {formatVietnameseCurrency} from "../../helpers/currencyFormat.js";

const store = useStore();
const emit = defineEmits();
const props = defineProps({
    isVisible: {type: Boolean, required: true},
    listCart: {type: Array, required: true},
    product: {type: Object, required: true}
});

const selectedCarts = ref([]); // Initialize as an empty array
const visibleToppings = ref({}); // Tracks which cart's toppings are visible

const isSelectedCart = (order_id) => {
    if (selectedCarts.value) {
        return selectedCarts.value.map(item => item.order_id).includes(order_id)
    }
}
const toggleSelectedCart = (cart) => {

    if (isSelectedCart(cart.order_id)) selectedCarts.value.pop(cart)
    else selectedCarts.value.push(cart)
}
const close = () => {
    emit('closePopup');
};

// Toggle the visibility of toppings for a specific cart
const toggleToppings = (cartId) => {
    visibleToppings.value[cartId] = !visibleToppings.value[cartId];
};

// Check if toppings for a specific cart are visible
const isToppingsVisible = (cartId) => {
    return visibleToppings.value[cartId] || false;
};

// Call the confirm function
const addProductToCart = (cartIds) => {
    let count = selectedCarts.value.length;
    let cartNames = selectedCarts.value.map(item => item.name).join(', ')
    if (confirm('Are you sure to add this product into ' + count + ' carts: ' + cartNames )) {
        store.dispatch('cart/addProductToCart', {
            product: props.product,
            order_ids: selectedCarts.value.map(item => item.order_id), // Array of cart IDs
        });
        notify({
            group: "foo",
            title: "Success",
            text: "Add to cart successfully!",
        }, 4000);
        close();
    }
};
</script>

<style scoped>
.overlay {
    background-color: rgba(0, 0, 0, 0.5); /* Background overlay */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 50; /* Ensure it appears on top */
}

.active_btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
}

.active_btn:disabled {
    background: #aaa; /* Disabled button background */
    cursor: not-allowed;
}

.topping_content p {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cursor-pointer {
    cursor: pointer;
}

.transition-all {
    transition: all 0.3s;
}

@media (max-width: 700px) {
    .active_btn {
        height: 28px;
    }
}
</style>
