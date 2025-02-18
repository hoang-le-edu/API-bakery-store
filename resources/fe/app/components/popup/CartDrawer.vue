<script setup>
import {initFlowbite, Drawer} from 'flowbite';
import {defineExpose, onBeforeUnmount} from 'vue';
import {onMounted, ref, computed} from "vue";
import {useStore} from 'vuex';
import {formatVietnameseCurrency} from "../../helpers/currencyFormat.js";
import {formatDate} from "@/js/helpers/dateFormat.js";
import {notify} from "notiwind";
import PaymentDetailPopup from "@/js/components/popup/PaymentDetail.vue";
import DetailProductPopup from "@/js/components/page/DetailProduct.vue";

let updateDrawerInstance = null; // Drawer instance
const isVisible = ref(false);
const store = useStore();
const expandedItems = ref({});
const activeTab = ref(0); // Default to the first tab
const isDropdownOpen = ref(false); // Controls the dropdown visibility
const cartData = ref([])
const showPaymentDetail = ref([])
const showProductDetail = ref(false)
const selectedProduct = ref({})
const openDrawer = () => {
    isVisible.value = true;
    updateDrawerInstance.show();
    fetchData();
};

defineExpose({
    openDrawer
});

const form = {
    cart_name: '',
    type: ''
}
const closeDrawer = () => {
    isVisible.value = false;
    updateDrawerInstance.hide();
};
const handleCreateCart = async () => {
    await store.dispatch('cart/createCart', {
        custom_name: form.cart_name,
        type: form.type
    });
    if (!store.getters['cart/error']) {
        notify({
            group: "foo",
            title: "Success",
            text: "Cart created successfully!",
        }, 4000);
        fetchData();
        store.commit('setCartCount', store.getters['cart/allCart'].length + 1);
    }
}
const fetchData = async () => {
    await store.dispatch('cart/fetchCarts');
    cartData.value = store.getters['cart/allCart']
};

showPaymentDetail.value = Array(cartData.value.length).fill(false);

onMounted(() => {
    initFlowbite();
    const $updateDrawer = document.getElementById('drawer-update-product');
    const drawerOptions = {
        placement: 'right',
        backdrop: true,
        bodyScrolling: false,
        onHide: () => {
        },
        onShow: () => {
            console.log('Drawer is shown');
        },
    };
    updateDrawerInstance = new Drawer($updateDrawer, drawerOptions);
    fetchData();
    document.addEventListener('click', clickOutsideHandler)
});
const clickOutsideHandler = (event) => {
    if (!event.target.closest('.dropdown') && !event.target.closest('.trigger-dropdown')) {
        isDropdownOpen.value = false;
    }
};
onBeforeUnmount(() => document.removeEventListener('click', clickOutsideHandler));
const toggleToppings = (itemId) => {
    expandedItems.value[itemId] = !expandedItems.value[itemId];
};

const deleteProduct = (cartId, orderDetailId) => {
    if (confirm('Are you sure you want to delete this product?')) {
        store.dispatch('cart/deleteProduct', {
            cartId,
            orderDetailId
        });
        fetchData();

        notify({
            group: "foo",
            title: "Success",
            text: "Delete product successfully!",
        }, 4000);
    }
};
const childRef = ref(null); // Create a reference for the child

const editProduct = (cartId, orderDetailId) => {
    let productDetail = cartData.value.find(cart => cart.order_id === cartId).order_detail.find(item => item.id === orderDetailId);
    store.dispatch('products/fetchCustomerProduct', productDetail.product_id).then(() => {
        selectedProduct.value = {
            ...store.getters["products/singleProduct"],
            quantity: productDetail.quantity,
            note: productDetail.note,
            size: {
                name: productDetail.size,
            },
            order_id: cartId,
        };
        let selectedToppingIds = productDetail.toppings.map(topping => topping.topping_id);
        selectedProduct.value.topping_list = selectedProduct.value.topping_list.map(topping => {
            topping.is_selected = selectedToppingIds.includes(topping.id);
            return topping;
        });
        selectedProduct.value.orderId = cartId;
        selectedProduct.value.orderDetailId = orderDetailId;
        childRef.value.loadExistedData(selectedProduct.value);
        showProductDetail.value = true;
    });
};
const deleteTopping = (cartId, orderDetailId, toppingId) => {
    if (confirm('Are you sure you want to delete this topping?')) {
        store.dispatch('cart/deleteTopping', {cartId, orderDetailId, toppingId});
        fetchData();

        notify({
            group: "foo",
            title: "Success",
            text: "Delete topping successfully!",
        }, 4000);
    }
};

const deleteCart = (cartId) => {
    if (confirm('Are you sure you want to delete this cart?')) {
        store.dispatch('cart/deleteCart', cartId);
        fetchData();

        notify({
            group: "foo",
            title: "Success",
            text: "Delete cart successfully!",
        }, 4000);
    }
};

const togglePaymentDetail = (index) => {
    showPaymentDetail.value[index] = !showPaymentDetail.value[index];
}

// Computed properties for visible and hidden tabs
const visibleTabs = computed(() => {
    return cartData.value.slice(0, 4); // Show only the first 5 tabs
});

const hiddenTabs = computed(() => {
    return cartData.value.slice(4); // Show the remaining tabs in the dropdown
});

// Toggle the dropdown
const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

// Select a hidden tab and bring it to the front
const selectHiddenTab = (index) => {
    // Move the selected tab to the front of the visible tabs
    const selectedTab = cartData.value[index];
    cartData.value.splice(index, 1); // Remove the selected tab from its current position
    cartData.value.unshift(selectedTab); // Add the selected tab to the front of the array

    activeTab.value = 0; // Set the active tab to the first tab (now the selected tab)
    isDropdownOpen.value = false; // Close the dropdown
};
const closePopup = () => {
    showProductDetail.value = false;
};
const isLoading = computed(() => store.getters['cart/isLoading']);
const error = computed(() => store.getters['cart/error']);
</script>


<template>
    <DetailProduct @refetch-cart="fetchData()" ref="childRef" :is-edit="true" :isVisible="showProductDetail" :selectedProduct="selectedProduct" @closePopup="closePopup" id="detail-product"/>
    <div
        id="drawer-update-product"
        v-show="isVisible"
        class="fixed top-0 z-99 right-0 w-full h-screen max-w-3xl overflow-y-auto transition-transform -translate-x-full bg-gray-100 dark:bg-gray-800"
        tabindex="-1"
        aria-labelledby="drawer-update-product-label"
        aria-hidden="true"
    >
        <div class="drawer-header flex justify-between items-center pl-4 pr-4 pt-4 pb-2">
            <h2 class="text-3xl font-semibold text-[#6B4226]">Carts</h2>
            <button @click="isVisible = false" class="text-2xl">&times;</button>
        </div>

        <!-- Custom Tabs -->
        <div class="ml-6 mr-6 mt-4 mb-4 border-[#6B4226] bg-gray-100 flex border-b-2 h-10 justify-between">
            <div class="flex">
                <button class="mr-[1px] bg-[#6B4226] text-white pl-2 pr-2 font-bold rounded-t-lg"
                        data-modal-toggle="create-cart-modal" data-modal-target="create-cart-modal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                              d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>
                <ul
                    class="flex flex-wrap -mb-px text-sm font-medium text-center"
                    id="default-tab"
                    data-tabs-toggle="#default-tab-content"
                    role="tablist"
                >
                    <!-- Visible Tabs (up to 5) -->
                    <li
                        v-for="(cart, index) in visibleTabs"
                        :key="cart.order_id"
                        class=""
                        role="presentation"
                    >
                        <button
                            :class="[
                            'relative flex justify-center items-center pl-3 pr-3 rounded-t-lg border-gray-100 border-2',
                            activeTab === index ? 'pb-[4px] border-[#6B4226] bg-gray-100 h-[102%] border-t-2 border-r-2 border-l-2 border-b-0 text-[#6B4226] font-bold' : 'h-[98%] hover:text-gray-600 bg-white dark:hover:text-gray-300',
                        ]"
                            :id="`tab-${index}`"
                            @click="activeTab = index"
                            type="button"
                            role="tab"
                            :aria-controls="`tab-content-${index}`"
                            :aria-selected="activeTab === index"
                        >
                            {{ cart.name }}
                            <div
                                class="absolute top-[0px] right-[0px] bg-red-500 z-999 text-white text-2xs ml-2 rounded-full w-4 h-4 flex items-center justify-center transform translate-x-1/2 -translate-y-1/2">
                                {{ cart.count_product }}
                            </div>
                        </button>
                    </li>
                </ul>
            </div>
            <!-- Plus Icon for Hidden Tabs -->
            <div v-if="hiddenTabs.length > 0" class="relative">
                <button
                    class="trigger-dropdown inline-block bg-gray-100 p-2 rounded-full hover:text-gray-600 hover:border-gray-300 hover:bg-gray-300 dark:hover:text-gray-300"
                    @click="toggleDropdown"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                        <path fill-rule="evenodd"
                              d="M12.53 16.28a.75.75 0 0 1-1.06 0l-7.5-7.5a.75.75 0 0 1 1.06-1.06L12 14.69l6.97-6.97a.75.75 0 1 1 1.06 1.06l-7.5 7.5Z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>

                <!-- Dropdown for Hidden Tabs -->
                <div
                    v-if="isDropdownOpen"
                    class="dropdown absolute right-0 mt-2 w-48 bg-gray-100 border border-gray-200 rounded-md shadow-lg z-10"
                >
                    <button
                        v-for="(hiddenCart, hiddenIndex) in hiddenTabs"
                        :key="hiddenCart.order_id"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        @click="selectHiddenTab(hiddenIndex + visibleTabs.length)"
                    >
                        {{ hiddenCart.name }} ({{ hiddenCart.count_product }})
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div id="default-tab-content">
            <div
                v-for="(cart, index) in cartData"
                :key="cart.order_id"
                :class="[
                    'rounded-lg bg-gray-100 dark:bg-gray-800',
                    activeTab === index ? '' : 'hidden',
                ]"
                :id="`tab-content-${index}`"
                role="tabpanel"
                :aria-labelledby="`tab-${index}`"
            >
                <PaymentDetail :cart="cart" :is-visible="showPaymentDetail[index]" :index="index" @showPaymentDetail="togglePaymentDetail" @refetchData="fetchData" @closeDrawer="closeDrawer"/>
                <div v-if="!showPaymentDetail[index]">
                    <div class="order-summary flex justify-between pl-6 pr-6 pb-4">
                        <div>
                            <strong>Cart Name:</strong> {{ cart.name }}
                        </div>
                        <div>
                            <strong>Date Created:</strong> {{ formatDate(cart.date_created) }}
                        </div>
                    </div>

                    <div class="order-items overflow-y-auto h-[745px] pl-6 pr-6 scrollbar-thin">
                        <div
                            v-for="(item, itemIndex) in cart.order_detail"
                            :key="item.id"
                            class="p-3 bg-white rounded-xl mb-4 cursor-pointer shadow-lg hover:shadow-xl rounded-xl"
                            @click="toggleToppings(item.id)"
                        >
                            <!-- Product Details -->
                            <div class="flex gap-4 h-full rounded-lg">
                                <div class="w-[12%]">
                                    <img
                                        v-if="item.image === null"
                                        src="@/assets/images/empty-image.jpg"
                                        alt="Product"
                                        class="w-full shadow-lg rounded-lg aspect-square"
                                    >
                                    <img
                                        v-else
                                        :src="item.image"
                                        alt="Product"
                                        class="w-full shadow-lg rounded-lg aspect-square"
                                    >
                                </div>
                                <div class="flex justify-between items-center h-full w-[88%]">
                                    <div class="flex flex-col justify-between">
                                        <div class="flex items-center">
                                            <div class="font-semibold">{{ item.product_name }} ({{ item.size }})</div>
                                            <span class="ml-2 text-sm text-gray-500">x{{ item.quantity }}</span>
                                        </div>
                                        <span class="text-sm text-gray-500">Note: {{ item.note }}</span>
                                        <span class="text-sm text-gray-500">Toppings: {{
                                                item.count_topping
                                            }} toppings</span>
                                    </div>
                                    <div class="font-semibold">{{ formatVietnameseCurrency(item.total_price) }}</div>
                                    <div>
                                        <button
                                            class="text-gray-500 cursor-pointer hover:text-gray-700 rounded-full hover:bg-gray-200 p-1 mr-1"
                                            @click.stop="editProduct(cart.order_id, item.id)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                        </button>
                                        <button
                                            class="text-red-500 cursor-pointer hover:text-red-700 rounded-full hover:bg-red-200 p-1 mr-1"
                                            @click.stop="deleteProduct(cart.order_id, item.id)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- Toppings -->
                            <transition
                                name="topping-transition"
                                appear
                            >
                                <div
                                    v-show="expandedItems[item.id]"
                                    class="toppings mt-2"
                                >
                                    <div v-if="item.toppings.length === 0">No toppings seleted</div>
                                    <div
                                        v-for="(topping, toppingIndex) in item.toppings"
                                        :key="toppingIndex"
                                        class="shadow-item flex justify-between text-sm hover:bg-gray-100 pt-1 pl-2 pr-1 rounded-lg"
                                    >
                                        <div class="w-55 overflow-hidden overflow-x-clip">{{
                                                topping.name
                                            }}
                                        </div>
                                        <div>+{{ formatVietnameseCurrency(topping.price) }}</div>
                                        <button
                                            class="text-red-500 cursor-pointer hover:text-red-700 rounded-full hover:bg-red-200 p-1 mb-1"
                                            @click.stop="deleteTopping(cart.order_id, item.id, topping.id)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                 fill="currentColor"
                                                 class="size-4">
                                                <path fill-rule="evenodd"
                                                      d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                    <div class="flex items-center justify-between h-full p-4 pl-6 pb-4 sticky bottom-0 right-0">
                        <span class="flex h-full justify-center flex-col items-center font-semibold">Total: {{ formatVietnameseCurrency(cart.total_price) }}</span>
                        <div class="flex gap-1">
                            <button
                                class="w-full justify-center sm:w-auto text-gray-500 bg-white inline-flex items-center bg-gray-100 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900"
                                @click="deleteCart(cart.order_id)"
                            >
                                Delete
                            </button>
                            <button @click="togglePaymentDetail(index)"
                                    class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold">
                                Go to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="create-cart-modal" tabindex="-1" aria-hidden="true"
         class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-999 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] md:h-full">
        <div class="relative p-4 w-full max-w-3xl h-full md:h-auto z-9999">
            <!-- Modal content -->
            <div class="relative p-4 bg-gray-100 rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <!-- Modal header -->
                <div
                    class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Cart</h3>
                    <button type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                            data-modal-toggle="create-cart-modal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form @submit.prevent="handleCreateCart">
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        <div>
                            <label for="full_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cart
                                Name</label>
                            <input v-model="form.cart_name" type="text" id="full_name"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Full Name" required>
                        </div>
                        <div>
                            <label for="type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cart
                                Type</label>
                            <select v-model="form.type" id="type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    required>
                                <option value="Personal">Personal</option>
                                <option value="Group">Group</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
                        <button type="submit"
                                class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold">
                            Add cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</template>
<style scoped>
.topping-transition-enter-active, .topping-transition-leave-active {
    transition: all 0.3s ease;
}

.topping-transition-enter-from, .topping-transition-leave-to {
    max-height: 0;
    opacity: 0;
}

.topping-transition-enter-to, .topping-transition-leave-from {
    max-height: 200px; /* Adjust based on expected content height */
    opacity: 1;
}

.shadow-item {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.shadow-item:hover {
    transform: translateY(-2px); /* Slight lift on hover */
}

.order-total {
    font-size: 1.2rem;
}

.dropdown {
    position: absolute;
    right: 0;
    z-index: 10;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dropdown button {
    display: block;
    width: 100%;
    text-align: left;
    padding: 8px 16px;
    border: none;
    background: none;
    cursor: pointer;
}

.dropdown button:hover {
    background-color: #f5f5f5;
}
</style>
