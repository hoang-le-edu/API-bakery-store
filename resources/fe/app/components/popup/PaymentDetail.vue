<script setup>
import {ref, onMounted, computed, watch} from 'vue';
import {useI18n} from "vue-i18n";
import {defineProps, defineEmits} from 'vue';
import {useStore} from 'vuex';
import {notify} from "notiwind";
import {formatVietnameseCurrency} from "@/js/helpers/currencyFormat.js";
import provincesData from '@/assets/js/tinh_tp.json';
import districtsData from '@/assets/js/quan_huyen.json';
import axios from "axios";
import router from "@/js/router/index.js";
import io from 'socket.io-client';

const socketStatus = ref(false);
let socket = null;
const store = useStore();
const emit = defineEmits(['showPaymentDetail', 'refetchData', 'closeDrawer']);
const props = defineProps({
    isVisible: {type: Boolean, required: true},
    index: {type: Number, required: true},
    cart: {type: Object, required: true}
});
const {t} = useI18n();
const voucherModalVisible = ref(false);
const dayAfterTomorrow = ref('');
const iframeUrl = ref('');
const discountAmount = ref(0);
const deliveryAmount = ref(0);
const showSuccess = ref(false);
const isProcessing = ref(false);
const isVisiblePaymentPopup = ref(false);
const showButtons = ref(false);
const showPaynowButton = ref(false);

// Initialize the form with values from the store
const form = ref({
    province: store.getters['customerInfo'].province,
    district: store.getters['customerInfo'].district,
    ward: store.getters['customerInfo'].ward,
    street: store.getters['customerInfo'].street,
    receiverName: store.getters['customerInfo'].full_name,
    phoneNumber: store.getters['customerInfo'].phone_number,
    note: '',
    paymentMethod: 'Cash',
    branch: '',
    voucher: '',
    voucher_shipping: '',
});
const vouchers = ref([]);
const deliveryDiscount = ref(0);
const deliveryDiscountName = ref('');
const discountName = ref('');

const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);

function openVoucherModal() {
    voucherModalVisible.value = true;
}

function closeVoucherModal() {
    voucherModalVisible.value = false;
}

onMounted(() => {
    const date = new Date();
    date.setDate(date.getDate() + 2);

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    dayAfterTomorrow.value = `${day}/${month}/${year}`;

    provinces.value = provincesData;
    districts.value = Object.values(districtsData); // Convert to array

    // Fetch wards for the initial district (if available)
    if (form.value.district !== '') {
        fetchWards(form.value.district);
    }
    initSocketListener();
});

const totalAmount = computed(() => {
    return props.cart.total_price - discountAmount.value + deliveryAmount.value;
});

const filteredDistricts = computed(() => {
    if (!form.value.province) return [];
    return districts.value.filter(d => d.ProvinceID.toString() === form.value.province.toString());
});

// Fetch wards from the GHN API via Laravel backend
const fetchWards = async (districtId) => {
    try {
        const response = await axios.get('/api/ghn/wards', {
            params: {
                district_id: districtId,
            },
        });
        wards.value = response.data.data; // Update the wards array
    } catch (error) {
        console.error('Error fetching wards:', error);
    }
};
const fetchShippingFee = async (teamId) => {
    try {
        const response = await axios.get('/api/ghn/shipping-fee', {
            params: {
                team_id: teamId,
                to_ward_code: form.value.ward.toString(),
                to_district_id: form.value.district.toString(),
                insurance_value: props.cart.total_price,
            },
        });
        deliveryAmount.value = response.data.data.total; // Update the delivery amount
    } catch (error) {
        console.error('Error fetching shipping fee:', error);
        notify({
            group: "foo",
            title: "Error",
            text: "Failed to fetch shipping fee. Please try again.",
        }, 4000);
    }
};
// Watch for changes in the district and fetch wards
watch(() => form.value.district, (newDistrictId) => {
    if (newDistrictId) {
        fetchWards(newDistrictId);
    } else {
        wards.value = []; // Clear wards if no district is selected
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const orderList = document.getElementById("order-list");
    const orderItems = orderList.getElementsByClassName("order-item");

    if (orderItems.length >= 3) {
        orderList.classList.add("max-h-32", "overflow-y-auto", "rounded-lg");
    }
});

// Watch the Vuex store for changes and update the form
watch(
    () => store.getters['customerInfo'], // Watch the customerInfo getter
    (newCustomerInfo) => {
        // Update the form with the new values from the store
        form.value.province = newCustomerInfo.province;
        form.value.district = newCustomerInfo.district;
        form.value.ward = newCustomerInfo.ward;
        form.value.street = newCustomerInfo.street;
        form.value.receiverName = newCustomerInfo.full_name;
        form.value.phoneNumber = newCustomerInfo.phone_number;
    },
    {deep: true} // Deep watch to detect nested changes
);

const saveAddress = () => {
    store.dispatch('customers/saveAddress', {
        id: store.getters['customerInfo'].id,
        addressData: {
            province: form.value.province.toString(),
            district: form.value.district.toString(),
            ward: form.value.ward,
            street: form.value.street,
        }
    }).then(async () => {
        const response = await axios.get('/api/auth/me');
        store.commit('setUser', response.data.data);
        notify({
            group: "foo",
            title: "Success",
            text: "Address saved successfully",
        }, 4000);
    });
};

watch(() => form.value.branch, async (newTeamId) => {
    if (newTeamId === '487b2a5a-1ebf-4035-a9ea-45a99539db80' && form.value.province === '202') {
        notify({
            group: "error",
            title: "Error",
            text: "Shipping is support for distance <10km, choose another branch",
        }, 4000);
        form.value.branch = '';
    } else {
        if (newTeamId) {
            await fetchShippingFee(newTeamId);
            await fetchVouchers(newTeamId); // Fetch vouchers when the team changes
        } else {
            deliveryAmount.value = 0; // Reset delivery amount if no team is selected
        }
    }
});

const fetchVouchers = async (teamId) => {
    try {
        const response = await axios.get('/api/vouchers/loadCustomerVoucher', {
            params: {
                team_id: teamId,
            },
        });
        vouchers.value = response.data.data; // Store the fetched vouchers
    } catch (error) {
        console.error('Error fetching vouchers:', error);
        notify({
            group: "foo",
            title: "Error",
            text: "Failed to fetch vouchers. Please try again.",
        }, 4000);
    }
};
const chooseVoucher = (voucherId) => {
    if (voucherId === form.value.voucher) {
        form.value.voucher = '';
    } else {
        form.value.voucher = voucherId;
    }
};
const chooseVoucherShipping = (voucherId) => {
    if (voucherId === form.value.voucher_shipping) {
        form.value.voucher_shipping = '';
    } else {
        form.value.voucher_shipping = voucherId;
    }
};
const applyVoucher = () => {
    if (form.value.voucher !== '') {
        const voucher = vouchers.value.discount.find(v => v.id === form.value.voucher);
        if (voucher.discount_type === 'percent') {
            let value = (props.cart.total_price * voucher.discount_percent) / 100;
            if (value > voucher.limit_per_order) {
                discountAmount.value = voucher.limit_per_order;
            } else {
                discountAmount.value = value;
            }
        } else {
            discountAmount.value = voucher.discount_amount;
        }
        discountName.value = voucher.voucher_code;
    }

    if (form.value.voucher_shipping !== '') {
        const voucher = vouchers.value.shipping_fee.find(v => v.id === form.value.voucher_shipping);
        if (voucher.discount_type === 'percent') {
            let value = (props.cart.total_price * voucher.discount_percent) / 100;
            if (value > voucher.limit_per_order) {
                deliveryDiscount.value = voucher.limit_per_order;
            } else {
                deliveryDiscount.value = value;
            }
        } else {
            deliveryDiscount.value = voucher.discount_amount;
        }
        deliveryDiscountName.value = voucher.voucher_code;
    }

    closeVoucherModal(); // Close the modal after applying the voucher
    notify({
        group: "foo",
        title: "Success",
        text: "Voucher applied successfully!",
    }, 4000);
};

const proceedOrder = async () => {
    try {
        //Disable button
        isProcessing.value = true;

        // Gather all the necessary data from the form
        const orderData = {
            order_id: props.cart.order_id, // Assuming the order ID is part of the cart object
            receiver_name: form.value.receiverName,
            receiver_address: `${form.value.street}, ${form.value.ward}, ${form.value.district}, ${form.value.province}`,
            payment_method: form.value.paymentMethod,
            branch: form.value.branch,
            voucher: form.value.voucher,
            voucher_shipping: form.value.voucher_shipping,
            note: form.value.note,
            province: form.value.province,
            district: form.value.district,
            ward: form.value.ward,
            street: form.value.street,
            phone_number: form.value.phoneNumber,
            shipping_fee: deliveryAmount.value - deliveryDiscount.value,
            discount_number: discountAmount.value,
            order_total: props.cart.total_price - discountAmount.value + deliveryAmount.value,
        };

        // Send the order data to the backend API
        const response = await axios.post('/api/orders/proceed', orderData);

        // Handle the response
        if (response.status === 200) {
            notify({
                group: "foo",
                title: "Success",
                text: "Order proceeded successfully!",
            }, 4000);
            if (form.value.paymentMethod === 'Banking') {
                showPaynowButton.value = true;
            }
            showSuccess.value = true;
            setTimeout(() => {
                showButtons.value = true;
            }, 1000);
        } else {
            notify({
                group: "error",
                title: "Error",
                text: "Failed to proceed with the order. Please try again.",
            }, 4000);
        }
        isProcessing.value = false;
        store.commit('setCartCount', store.getters['customerInfo'].count_cart - 1);
    } catch (error) {
        console.error('Error proceeding with the order:', error);
        notify({
            group: "error",
            title: "Error",
            text: "An error occurred while proceeding with the order. Please try again.",
        }, 4000);
        isProcessing.value = false;
    }
};
const closePaymentDetail = () => {
    emit('refetchData')
    emit('showPaymentDetail', props.index);
};

const showOrderDetail = () => {
    emit('closeDrawer');
    router.push('/order');
};

const getCheckoutUrl = async () => {
    try {
        const response = await axios.post('/api/payos/create-payment-link', {
            order_id: props.cart.order_id,
        });
        iframeUrl.value = response.data.checkoutUrl;
        isVisiblePaymentPopup.value = true;
    } catch (error) {
        console.error('Error fetching checkout URL:', error);
    }
}
const closePaymentPopup = () => {
    isVisiblePaymentPopup.value = false;
};

const initSocketListener = () => {
    const socketURL = "https://socket.dotb.cloud/";
    socket = io.connect(socketURL, {
        path: "",
        transports: ["websocket"],
        reconnection: true,
    });

    // Handle socket connection
    socket.on('connect', () => {
        console.log('Socket server is live!');
        socket.emit('join', `triggerPaymentStatus/${props.cart.order_id}`);
    });

    // Handle socket errors
    socket.on('error', () => {
        console.log('Cannot connect to socket server!');
    });

    // Handle custom events
    socket.on('event-phenikaa', (msg) => {
        if (msg.success) {
            if (!socketStatus.value) {
                let message = 'Successfully pay for order ' + props.cart.name + ' with amount ' + formatVietnameseCurrency(props.cart.total_price);
                notify({
                    group: "foo",
                    title: "Success",
                    text: message,
                }, 4000);
                isVisiblePaymentPopup.value = false;
                showPaynowButton.value = false;
                socketStatus.value = true;
            }
        } else {
            notify({
                group: "error",
                title: "Error",
                text: "Payment failed. Please try again.",
            }, 4000);
        }
    });
};

// Cleanup function to disconnect the socket
const cleanupSocket = () => {
    if (socket) {
        socket.disconnect();
        socket = null;
        console.log('Socket disconnected');
    }
};
</script>
<template>
    <div v-if="showSuccess" class="flex flex-col h-full w-full justify-center items-center mt-55">
        <div class="success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        <span v-if="!socketStatus" class="text-xl mt-4 flex flex-col items-center">Place order successfully! <span
            v-if="showPaynowButton">Please pay the order to proceed, you can pay now or later!</span></span>
        <span v-if="socketStatus" class="text-xl mt-4 flex flex-col items-center">Pay successfully!</span>
        <transition name="fade-in">
            <div v-if="showButtons" class="flex gap-2 pt-4">
                <button
                    @click="closePaymentDetail"
                    class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-500"
                >
                    Close
                </button>
                <button class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold"
                        @click="showOrderDetail">
                    View order
                </button>
                <button v-if="form.paymentMethod === 'Banking' && showPaynowButton"
                        class="bg-[#ABBA7C] text-white px-4 py-2 rounded-full font-bold"
                        @click="getCheckoutUrl">
                    Pay now
                </button>
            </div>
        </transition>
    </div>
    <transition
        enter-active-class="transition-transform duration-500 ease-in-out"
        leave-active-class="transition-transform duration-500 ease-in-out"
        enter-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-class="translate-x-full"
        leave-to-class="translate-x-full"
    >
        <section v-if="isVisible && !showSuccess" class="relative bg-gray-100">
            <div class="mx-auto max-w-screen-xl pr-6 pl-6 bg-gray-100 relative min-h-[744px]">
                <div class="flex flex-col mt-2 justify-center bg-gray-100 items-center sm:mt-4 gap-4">
                    <div class="flex gap-2 bg-gray-100 w-full">
                        <div
                            class="bg-white shadow-lg w-[70%] lg:max-w-md h-full divide-y divide-gray-200 overflow-hidden rounded-lg dark:divide-gray-700 dark:border-gray-700 xl:max-w-2xl">
                            <div class="space-y-1 p-5 pt-4">
                                <div class="flex w-full justify-between">
                                    <div class="flex gap-1 items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="#6B4226" class="size-6 mb-[2px]">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                                        </svg>
                                        <label class="font-bold text-[#6B4226] dark:text-white">
                                            {{
                                                t('LBL_DELIVERY')
                                            }}
                                        </label>
                                    </div>
                                    <button class="hover:bg-gray-100 text-[#6B4226] text-sm p-1 rounded-lg"
                                            @click="saveAddress">
                                        Save
                                    </button>
                                </div>
                                <div class="mt-4">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Province</label>
                                            <select v-model="form.province" @change="form.district = ''; form.ward = ''"
                                                    class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                <option value="">Select Province</option>
                                                <option v-for="(province, code) in provinces" :key="province.ProvinceID"
                                                        :value="province.ProvinceID">
                                                    {{ province.ProvinceName }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">District</label>
                                            <select v-model="form.district" @change="form.ward = ''"
                                                    class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                <option value="">Select District</option>
                                                <option v-for="district in filteredDistricts" :key="district.DistrictID"
                                                        :value="district.DistrictID">{{ district.DistrictName }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div>
                                            <label
                                                class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Ward</label>
                                            <select v-model="form.ward"
                                                    class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                <option value="">Select Ward</option>
                                                <option v-for="ward in wards" :key="ward.WardCode"
                                                        :value="ward.WardCode">
                                                    {{ ward.WardName }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Street</label>
                                            <input type="text" v-model="form.street"
                                                   class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                   placeholder="Enter Street">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white w-[50%] shadow-lg lg:max-w-md h-full divide-y divide-gray-200 overflow-hidden rounded-lg dark:divide-gray-700 dark:border-gray-700 xl:max-w-2xl">
                            <div class="space-y-1 p-5 pt-4">
                                <div class="flex gap-1 items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="2"
                                         stroke="#6B4226" class="size-5 mb-[2px]">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                    </svg>
                                    <label class="font-inter font-bold text-[#6B4226] dark:text-white">Receiver</label>
                                </div>
                                <div class="mt-4">
                                    <label class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">{{
                                            t('LBL_RECIPIENT_NAME')
                                        }}</label>
                                    <input type="text"
                                           v-model="form.receiverName"
                                           class="bg-white border border-gray-300 text-gray-500  text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                           placeholder="Enter Name">
                                </div>
                                <div class="mt-4">
                                    <label class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">{{
                                            t('LBL_PHONE_NUMBER')
                                        }}</label>
                                    <input type="text"
                                           v-model="form.phoneNumber"
                                           class="bg-white border border-gray-300 text-gray-500  text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                           placeholder="Enter Phone Number">
                                </div>
                                <div class="mt-4">
                                    <label class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">{{
                                            t('LBL_NOTE')
                                        }}</label>
                                    <input type="text"
                                           v-model="form.note"
                                           class="bg-white border border-gray-300 text-gray-500  text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                           placeholder="Enter Note">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full bg-white shadow-lg pl-6 pr-6 rounded-lg">
                        <div class="flex gap-1 items-center mt-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="#6B4226" class="size-5 mb-[2px]">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                            </svg>
                            <label class="font-inter font-bold text-[#6B4226] dark:text-white">Order details</label>
                        </div>
                        <div class="overflow-y-auto bg-white w-full h-fit scrollbar-thin ">
                            <div
                                v-for="(item, itemIndex) in cart.order_detail"
                                :key="item.id"
                                class="order-item mb-1 rounded-xl w-full last:pb-4"
                            >
                                <!-- Product Details -->
                                <div class="flex gap-1 h-full pt-2 pl-3 w-full border-b border-gray-400 pb-2">
                                    <div class="flex flex-col w-full">
                                        <div class="flex items-center justify-between w-[100%]">
                                            <div class="flex w-full gap-1 items-center">
                                                <span class="text-sm">{{ item.quantity }} x </span>
                                                <span class="font-semibold text-md">{{ item.product_name }} ({{
                                                        item.size
                                                    }})</span>
                                                <span v-if="item.note !== ''" class="text-sm text-gray-500">- {{
                                                        item.note
                                                    }}</span>
                                            </div>
                                            <div class="font-semibold text-md">{{
                                                    formatVietnameseCurrency(item.total_price)
                                                }}
                                            </div>
                                        </div>
                                        <span
                                            class="text-sm text-gray-500 w-[550px] whitespace-nowrap overflow-hidden text-ellipsis">
                                            <span
                                                v-for="(topping, toppingIndex) in item.toppings"
                                                :key="toppingIndex"
                                                class="text-sm"
                                            >
                                                {{ topping.name }}<span
                                                v-if="toppingIndex !== item.toppings.length - 1">, </span>
                                            </span>
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Section -->
                    <div class="mt-6 grow shadow-lg sm:mt-8 rounded-lg bg-white lg:mt-0 w-full flex flex-col gap-2">
                        <div class="flex gap-1 items-center relative p-4 pb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="#6B4226" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                            </svg>
                            <label class="font-inter font-bold text-[#6B4226] dark:text-white">Payment
                                Information</label>
                        </div>
                        <div
                            class="w-full h-full flex justify-between dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-col p-4 pt-0 pb-0 space-y-3 w-[60%]">
                                <div class="grid grid-cols-1">
                                    <label
                                        class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">Method</label>
                                    <div class="flex gap-4">
                                        <div class="flex items-center space-x-2">
                                            <input id="Cash" type="radio" name="payment" class="form-radio"
                                                   v-model="form.paymentMethod" value="Cash">
                                            <label for="Cash" class="text-gray-500">{{ t('LBL_CASH') }}</label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <input id="Banking" type="radio" name="payment" class="form-radio"
                                                   v-model="form.paymentMethod" value="Banking">
                                            <label for="Banking" class="text-gray-500">Online</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label
                                            class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">Branch</label>
                                        <select
                                            v-model="form.branch"
                                            class="bg-white border border-gray-300 text-gray-500  text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        >
                                            <option v-for="team in store.getters['teams/allTeamsOption']"
                                                    :value="team.id" :key="team.id">{{ team.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                </div>
                                <div v-if="voucherModalVisible"
                                     class="fixed inset-0 z-9999 bg-gray-900 bg-opacity-50 flex items-center justify-center">
                                    <div class="bg-gray-100 p-6 pb-4 rounded-lg shadow-lg max-w-lg w-full">
                                        <div class="flex justify-between items-center">
                                            <h2 class="font-bold text-[#6B4226] text-lg mb-4">{{
                                                    t('LBL_VOUCHER')
                                                }}</h2>
                                            <div class="h-6 w-6 text-gray-500 cursor-pointer"
                                                 @click="closeVoucherModal">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-gray-500">Shipping voucher</span>
                                            <div class="flex flex-col items-center gap-3 mt-2">
                                                <div v-for="voucher in vouchers.shipping_fee" :key="voucher.id"
                                                     @click="chooseVoucherShipping(voucher.id)"
                                                     class="shadow-item h-[80px] hover:shadow-xl flex items-center w-[95%] bg-white justify-between gap-4 shadow-lg rounded-lg pr-4">
                                                    <div class="flex gap-1 h-full w-[90%] border-[#6B4226]">
                                                        <div
                                                            class="bg-[#ABBA7C] p-4 h-full rounded-l-lg flex items-center justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1" stroke="white"
                                                                 class="size-12">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                                                            </svg>
                                                        </div>
                                                        <div class="ml-4 flex h-full flex-col justify-center">
                                                            <p class="text-gray-500 font-inter font-semibold">
                                                                {{ voucher.voucher_code }}</p>
                                                            <p class="text-sm text-gray-500">Discount <span
                                                                class="font-semibold">{{
                                                                    voucher.discount_type === 'percent' ? voucher.discount_percent + '%' : formatVietnameseCurrency(voucher.discount_amount)
                                                                }}</span> <span
                                                                v-if="voucher.discount_type == 'percent'"> up to <span
                                                                class="font-semibold">{{ formatVietnameseCurrency(voucher.limit_per_order) }}</span></span>
                                                            </p>
                                                            <p class="text-sm text-gray-500">Minimum order of
                                                                {{ formatVietnameseCurrency(voucher.minimum) }}</p>
                                                        </div>
                                                    </div>
                                                    <input id="voucher_shipping" type="radio" name="voucher_shipping"
                                                           class="form-radio"
                                                           v-model="form.voucher_shipping" :value="voucher.id">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <span class="font-semibold text-gray-500">Discount voucher</span>
                                            <div class="flex flex-col items-center gap-3 mt-2">
                                                <div v-for="voucher in vouchers.discount" :key="voucher.id"
                                                     @click="chooseVoucher(voucher.id)"
                                                     class="shadow-item h-[80px] hover:shadow-xl flex items-center w-[95%] bg-white justify-between gap-4 shadow-lg rounded-lg pr-4">
                                                    <div class="flex gap-1 h-full w-[90%] border-[#6B4226]">
                                                        <div
                                                            class="bg-[#6B4226] p-4 h-full rounded-l-lg flex items-center justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1" stroke="white"
                                                                 class="size-12">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="ml-4 flex h-full flex-col justify-center">
                                                            <p class="text-gray-500 font-inter font-semibold">
                                                                {{ voucher.voucher_code }}</p>
                                                            <p class="text-sm text-gray-500">Discount <span
                                                                class="font-semibold">{{
                                                                    voucher.discount_type === 'percent' ? voucher.discount_percent + '%' : formatVietnameseCurrency(voucher.discount_amount)
                                                                }}</span> <span
                                                                v-if="voucher.discount_type == 'percent'"> up to <span
                                                                class="font-semibold">{{ formatVietnameseCurrency(voucher.limit_per_order) }}</span></span>
                                                            </p>
                                                            <p class="text-sm text-gray-500">Minimum order of
                                                                {{ formatVietnameseCurrency(voucher.minimum) }}</p>
                                                        </div>
                                                    </div>
                                                    <input id="voucher_normal" type="radio" name="voucher"
                                                           class="form-radio"
                                                           v-model="form.voucher" :value="voucher.id">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 justify-end bg-gray-100 w-full pt-4">
                                            <button
                                                class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-500 "
                                                @click="closeVoucherModal"
                                            >
                                                Close
                                            </button>
                                            <button @click="applyVoucher"
                                                    class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold">
                                                Apply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="w-full bg-white dark:bg-gray-800 border-gray-700 p-4 pb-0 pt-0 flex flex-col justify-center">
                                <div class="space-y-2">
                                    <div
                                        class="border rounded-lg border-gray-300 h-[33px] flex items-center justify-start">
                                        <button @click="openVoucherModal"
                                                class="bg-[#6B4226] h-full p-1.5 rounded-l-lg w-fit flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="2" stroke="white" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                                            </svg>
                                            <span class="text-white text-sm">Voucher</span>
                                        </button>
                                        <div class="flex justify-end w-full pr-2 gap-1">
                                        <span
                                            class="border p-1 pr-2 pl-2 bg-[#6B4226] text-white font-semibold rounded-lg text-xs"
                                            v-if="deliveryDiscountName">
                                            {{ deliveryDiscountName }}
                                        </span>
                                            <span
                                                class="border p-1 pr-2 pl-2 bg-[#ABBA7C] border-[#ABBA7C] text-white font-semibold rounded-lg text-xs"
                                                v-if="discountName">
                                            {{ discountName }}
                                        </span>
                                        </div>
                                    </div>
                                    <div class="pt-0">
                                        <dl class="flex items-center justify-between gap-4">
                                            <dt class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">
                                                {{ t('LBL_PRICE') }}
                                            </dt>
                                            <dd class="font-inter text-gray-500 dark:text-white">
                                                {{ formatVietnameseCurrency(cart.total_price) }}
                                            </dd>
                                        </dl>
                                        <dl class="flex items-center justify-between gap-4">
                                            <dt class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">
                                                {{ t('LBL_DISCOUNT') }}
                                            </dt>
                                            <dd class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">-
                                                {{ formatVietnameseCurrency(discountAmount) }}
                                            </dd>
                                        </dl>
                                        <dl class="flex items-center justify-between gap-4">
                                            <dt class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">
                                                {{ t('LBL_DELIVERY_FEE') }}
                                            </dt>
                                            <dd class="block mb-2 text-sm font-medium text-gray-500  dark:text-white">
                                                <span
                                                    v-if="deliveryDiscount">( -{{ formatVietnameseCurrency(deliveryDiscount, false) }})</span>
                                                {{ formatVietnameseCurrency(deliveryAmount - deliveryDiscount) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <dl class="flex items-center m-4 mt-0 justify-between gap-4 pl-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                            <dt class="text-lg font-bold text-gray-500 dark:text-white">{{ t('LBL_TOTAL') }}</dt>
                            <dd class="text-lg font-bold text-gray-500 dark:text-white">
                                {{ formatVietnameseCurrency(totalAmount) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 justify-end mt-4 mr-4 pr-4 sticky bottom-0 right-0 bg-gray-100 w-full p-4 pl-6 pb-4">
                <button
                    :disabled="isProcessing"
                    class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-500 "
                    @click="emit('showPaymentDetail', index)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="size-4 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                    Go back to cart
                </button>
                <button :disabled="isProcessing" @click="proceedOrder"
                        class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold">
                    Proceed
                </button>
            </div>
        </section>
    </transition>
    <div v-if="isVisiblePaymentPopup" class="payment-popup">
        <div class="payment-popup-content">
            <button class="close-button" @click="closePaymentPopup">Close</button>
            <iframe
                :src="iframeUrl"
                width="100%"
                height="97%"
                frameborder="0"
            ></iframe>
        </div>
    </div>
</template>
<style scoped>
.payment-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.payment-popup-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    width: 1080px;
    height: 700px;
}

.close-button {
    margin-bottom: 10px;
    cursor: pointer;
}

.checkmark {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: block;
    stroke-width: 2;
    stroke: #4bb71b;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #4bb71b;
    animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    position: relative;
    top: 5px;
    right: 5px;
    margin: 0 auto;
}

.checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: #4bb71b;
    fill: #fff;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;

}

.checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }

    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 30px #4bb71b;
    }
}

.form-radio {
    @apply appearance-none w-4 h-4 border-2 border-[#6B4226] rounded-full cursor-pointer transition-all;
    background-color: white;
    position: relative;
}

.form-radio:checked {
    @apply bg-white border-[#6B4226];
}

.form-radio:checked::after {
    content: '';
    @apply block w-2.5 h-2.5 rounded-full bg-[#6B4226];
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.shadow-item {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.shadow-item:hover {
    transform: translateY(-2px); /* Slight lift on hover */
}
</style>
