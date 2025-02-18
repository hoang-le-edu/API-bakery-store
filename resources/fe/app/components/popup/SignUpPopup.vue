<script setup>
import {useStore} from "vuex";
import {defineEmits, defineProps, ref} from 'vue';
import {FwbAlert} from "flowbite-vue";

import {useI18n} from "vue-i18n";
const {t} = useI18n();
const store = useStore();
const emit = defineEmits();
const props = defineProps({
    isVisible: {
        type: Boolean,
        required: true
    },
});

// State for the form fields
const name = ref('');
const phone = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const dateOfBirth = ref('');
const gender = ref('');

// Password visibility toggle
const showPassword1 = ref(false);
const togglePassword1 = () => {
    showPassword1.value = !showPassword1.value;
};
const showPassword2 = ref(false);
const togglePassword2 = () => {
    showPassword2.value = !showPassword2.value;
};

// Emit events
const close = () => {
    emit('closePopup');
};
const switchToSignIn = () => {
    emit('switchPopup', 'sign-in');
};

const showAlert = ref(false);
// Sign up function
const loading = ref(false); // State for button loading
const statusMessage = ref(''); // State for success or error messages
const typeMessage = ref(''); // State for success or error messages

const signUp = async () => {
    // Disable the button and clear previous messages
    loading.value = true;
    statusMessage.value = '';

    const formData = {
        name: name.value,
        phone_number: phone.value,
        email: email.value,
        password: password.value,
        user_type: 'customer',
        c_password: confirmPassword.value,
        date_of_birth: dateOfBirth.value,
        gender: gender.value,
    };

    if (formData.password !== formData.c_password) {
        statusMessage.value = t('LBL_PASSWORDS_DO_NOT_MATCH');
        loading.value = false;
        return;
    }

    try {
        // Dispatching the form data to the Vuex `signUp` action
        const response = await store.dispatch('signUp', formData);

        if(response.success) {
            // Clear form fields
            name.value = '';
            phone.value = '';
            email.value = '';
            password.value = '';
            confirmPassword.value = '';
            dateOfBirth.value = '';
            gender.value = '';

            statusMessage.value = t('LBL_SIGNUP_SUCCESS');
            typeMessage.value = 'success';
            close();
        } else {
            statusMessage.value = response.message || t('LBL_SIGNUP_FAILED');
            typeMessage.value = 'danger';
        }

    } catch (error) {
        console.error('Sign Up failed:', error);
        statusMessage.value = error.response?.data?.message || t('LBL_SIGNUP_FAILED');
        typeMessage.value = 'danger';
    } finally {
        loading.value = false; // Enable the button
    }
};

const handleFocus = () => {
    showAlert.value = false;
};
</script>

<template>
    <div v-if="isVisible" class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex lg:items-center justify-end lg:justify-center z-99999" @click="close" >
        <div class="bg-white lg:rounded-lg shadow-lg max-w-md h-fit w-[65%] lg:h-fit lg:w-full relative" @click.stop>
            <div class="flex flex-col items-center justify-center">
                <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <div class="text-xl font-bold leading-tight tracking-tight text-black md:text-2xl dark:text-white">
                            {{ t('LBL_PINY_CLOUD_BREAD_AND_TEA') }}
                        </div>
                        <fwb-alert border :type="typeMessage" v-if="statusMessage">
                            {{ statusMessage }}
                        </fwb-alert>
                        <form class="space-y-4 md:space-y-6" @submit.prevent="signUp">
                            <div>
                                <label for="name" class="block mb-2 text-sm font-inter text-black dark:text-white">{{ t('LBL_FULL_NAME') }}</label>
                                <input @focus="handleFocus" type="text" name="name" id="name" v-model="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <div class="flex justify-between gap-1">
                                <div class="w-full">
                                    <label for="date_of_birth" class="block mb-2 text-sm font-inter text-black dark:text-white">
                                        {{ t('LBL_DATE_OF_BIRTH') }}
                                    </label>
                                    <input
                                        @focus="handleFocus"
                                        type="date"
                                        name="date_of_birth"
                                        id="date_of_birth"
                                        v-model="dateOfBirth"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required>
                                </div>
                                <div class="w-full">
                                    <label for="gender" class="block mb-2 text-sm font-inter text-black dark:text-white">
                                        {{ t('LBL_GENDER') }}
                                    </label>
                                    <select
                                        @focus="handleFocus"
                                        name="gender"
                                        id="gender"
                                        v-model="gender"
                                        class="bg-gray-50 border border-gray-500 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required>
                                        <option value="">{{ t('LBL_SELECT_GENDER') }}</option>
                                        <option value="male">{{ t('LBL_MALE') }}</option>
                                        <option value="female">{{ t('LBL_FEMALE') }}</option>
                                        <option value="other">{{ t('LBL_OTHER') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="phone" class="block mb-2 text-sm font-inter text-black dark:text-white">{{ t('LBL_PHONE_NUMBER') }}</label>
                                <input @focus="handleFocus" type="text" name="phone" id="phone" v-model="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <div>
                                <label for="email" class="block mb-2 text-sm font-inter text-black dark:text-white">{{ t('LBL_EMAIL') }}</label>
                                <input @focus="handleFocus" type="email" name="email" id="email" v-model="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <div class="input-container">
                                <label for="password" class="block mb-2 text-sm font-inter text-black dark:text-white">
                                    {{ t('LBL_PASSWORD') }}
                                </label>
                                <div class="flex items-center relative">
                                    <input
                                        @focus="handleFocus"
                                        :type="showPassword1 ? 'text' : 'password'"
                                        v-model="password"
                                        name="password1"
                                        id="password1"
                                        placeholder=""
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required
                                    />

                                    <!-- SVG biểu tượng mắt -->
                                    <span @click="togglePassword1" class="input-icon cursor-pointer">
                                    <!-- Biểu tượng SVG đôi mắt -->
                                    <svg v-if="!showPassword1" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor"
                                         class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-.739 2.467-2.488 4.573-4.737 5.717a9.956 9.956 0 01-9.622 0C4.946 16.573 3.197 14.467 2.458 12z"/>
                                    </svg>
                                        <!-- Biểu tượng SVG mắt mở -->
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.5C16.4183 4.5 20.209 7.493 21.542 12C20.2087 16.507 16.418 19.5 12 19.5C7.58172 19.5 3.791 16.507 2.458 12C3.79132 7.493 7.582 4.5 12 4.5Z"/>
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9.5C13.3807 9.5 14.5 10.6193 14.5 12C14.5 13.3807 13.3807 14.5 12 14.5C10.6193 14.5 9.5 13.3807 9.5 12C9.5 10.6193 10.6193 9.5 12 9.5Z"/>
                                    </svg>
                                </span>
                                </div>
                            </div>
                            <div class="input-container">
                                <label for="confirm-password"
                                       class="block mb-2 text-sm font-inter text-black dark:text-white">
                                    {{ t('LBL_CONFIRM_PASSWORD') }}
                                </label>
                                <div class="flex items-center relative">
                                    <input
                                        @focus="handleFocus"
                                        v-model="confirmPassword"
                                        :type="showPassword2 ? 'text' : 'password'"
                                        name="password2"
                                        id="password2"
                                        placeholder=""
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required
                                    />

                                    <!-- SVG biểu tượng mắt -->
                                    <span @click="togglePassword2" class="input-icon cursor-pointer">
                                    <!-- Biểu tượng SVG đôi mắt -->
                                    <svg v-if="!showPassword2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor"
                                         class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-.739 2.467-2.488 4.573-4.737 5.717a9.956 9.956 0 01-9.622 0C4.946 16.573 3.197 14.467 2.458 12z"/>
                                    </svg>
                                        <!-- Biểu tượng SVG mắt mở -->
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.5C16.4183 4.5 20.209 7.493 21.542 12C20.2087 16.507 16.418 19.5 12 19.5C7.58172 19.5 3.791 16.507 2.458 12C3.79132 7.493 7.582 4.5 12 4.5Z"/>
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9.5C13.3807 9.5 14.5 10.6193 14.5 12C14.5 13.3807 13.3807 14.5 12 14.5C10.6193 14.5 9.5 13.3807 9.5 12C9.5 10.6193 10.6193 9.5 12 9.5Z"/>
                                    </svg>
                                </span>
                                </div>
                            </div>
                            <button @click="signUp"
                                    :disabled="loading"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#5A3621] focus:ring-4 focus:outline-none focus:ring-primary-300 font-inter rounded-lg text-sm px-5 py-2.5 text-center dark:bg-[#6B4226] dark:hover:bg-[#6B4226] dark:focus:ring-primary-800">
                                <span v-if="!loading">{{ t('LBL_SIGNUP') }}</span>
                                <span v-else>{{ t('LBL_SIGNING_UP') }}...</span>
                            </button>
                            <div class="flex items-center space-x-2">
                                <label for="already_have_an_account"
                                       class=" font-inter text-black dark:text-gray-400">{{
                                        t('LBL_ALREADY_HAVE_AN_ACCOUNT?')
                                    }}</label>
                                <a @click="switchToSignIn" class="hover:underline text-[#6B4226]"
                                   target="_blank">{{ t('LBL_SIGNIN') }}</a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<style scoped>

.input-container {
    position: relative;
}

.input-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}
</style>
