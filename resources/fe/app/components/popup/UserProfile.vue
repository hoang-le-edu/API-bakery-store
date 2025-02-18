<template>
    <div
        v-if="isVisible"
        class="fixed inset-0 z-9999 bg-gray-900 bg-opacity-50 flex items-center justify-center"
    >
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-lg text-[#6B4226]">Profile</h2>
                <div
                    class="h-6 w-6 text-gray-500 cursor-pointer"
                    @click="closeProfileModal"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="w-6 h-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </div>
            </div>

            <!-- Profile Picture -->
            <div class="flex justify-center mb-">
                <img
                    src="@/assets/images/user/user-01.png"
                    alt="Profile Picture"
                    class="w-24 h-24 rounded-full shadow-xl object-cover border-2 border-gray-300"
                />
            </div>

            <!-- Profile Details -->
            <div>
                <div class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            v-model="userProfile.full_name"
                            type="text"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 userProfile"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="userProfile.email"
                                type="email"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 userProfile"
                            />
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input
                                v-model="userProfile.phone_number"
                                type="text"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 userProfile"
                            />
                        </div>
                        <!-- Date of Birth -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                            <input
                                v-model="userProfile.date_of_birth"
                                type="date"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 userProfile"
                            />
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <select
                                v-model="userProfile.gender"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 userProfile"
                            >
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="font-semibold ">Delivery info</span>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Province</label>
                            <select v-model="userProfile.province" @change="userProfile.district = ''; userProfile.ward = ''"
                                    class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select Province</option>
                                <option v-for="(province, code) in provinces" :key="province.ProvinceID" :value="province.ProvinceID">
                                    {{ province.ProvinceName }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">District</label>
                            <select v-model="userProfile.district" @change="userProfile.ward = ''"
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
                            <select v-model="userProfile.ward"
                                    class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select Ward</option>
                                <option v-for="ward in wards" :key="ward.WardCode" :value="ward.WardCode">
                                    {{ ward.WardName }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Street</label>
                            <input type="text" v-model="userProfile.street"
                                   class="bg-white border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Enter Street">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 justify-end mt-6">
                <button
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-full border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-500"
                    @click="closeProfileModal"
                >
                    Close
                </button>
                <button
                    @click="saveProfile"
                    class="bg-[#6B4226] text-white px-4 py-2 rounded-full font-bold"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {defineEmits, defineProps, ref, onMounted, watch, computed} from 'vue';
import {useI18n} from "vue-i18n";
import {useStore} from "vuex";
import provincesData from "@/assets/js/tinh_tp.json";
import districtsData from "@/assets/js/quan_huyen.json";
import axios from "axios";


const {t} = useI18n();
const store = useStore();
const emit = defineEmits();
const props = defineProps({
    isVisible: {
        type: Boolean,
        required: true
    },
});
const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);

// State for modal visibility
const isProfileModalOpen = ref(false);

// Default avatar image
const defaultAvatar = 'https://via.placeholder.com/150';

// User profile data
const userProfile = ref(store.getters['customerInfo']);

// Open the profile modal
const openProfileModal = () => {
    isProfileModalOpen.value = true;
};

// Close the profile modal
const closeProfileModal = () => {
    emit('closePopup');
};

// Save the profile
const saveProfile = () => {
    console.log('Profile Saved:', userProfile.value);
    // Add your logic to save the profile (e.g., send to an API)
    closeProfileModal();
};
if(userProfile.value) {
    watch(() => userProfile.value.district, (newDistrictId) => {
        if (newDistrictId) {
            fetchWards(newDistrictId);
        } else {
            wards.value = []; // Clear wards if no district is selected
        }
    });
}

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
onMounted(() => {
    userProfile.value = store.getters['customerInfo'];
    provinces.value = provincesData;
    districts.value = Object.values(districtsData); // Convert to array

    if(userProfile.value) {
        // Fetch wards for the initial district (if available)
        if (userProfile.value.district !== '') {
            fetchWards(userProfile.value.district);
        }
    }

});
const filteredDistricts = computed(() => {
    if (!userProfile.value.province) return [];
    return districts.value.filter(d => d.ProvinceID.toString() === userProfile.value.province.toString());
});

</script>

<style scoped>
/* Optional: Add custom styles for the modal */
</style>
