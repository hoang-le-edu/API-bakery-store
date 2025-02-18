<template>
    <div v-if="isVisible"
         class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex lg:items-center justify-end lg:justify-center z-99999"
         @click="close">
        <div class="bg-white lg:rounded-lg shadow-lg max-w-md h-fit w-[65%] lg:h-fit lg:w-full relative" @click.stop>
            <div class="flex flex-col items-center justify-center">
                <div
                    class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <div
                            class="text-xl font-bold leading-tight tracking-tight text-black md:text-2xl dark:text-white">
                            {{ t('LBL_PINY_CLOUD_BREAD_AND_TEA') }}
                        </div>
                        <form class="space-y-4 md:space-y-6" @submit.prevent="handleSendOTP">
                            <div>
                                <label for="phone" class="block mb-2 text-sm font-inter text-black dark:text-white">{{
                                        t('LBL_PHONE_NUMBER')
                                    }}</label>
                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder=""
                                    v-model="mobile_phone"
                                    required=""
                                />
                            </div>
                            <div>
                                <a @click="switchToPopup('sign-in-password')"
                                   class="text-sm font-inter text-black dark:text-gray-400 hover:underline">
                                    {{ t('LBL_OR_LOGIN_WITH_PASSWORD') }}
                                </a>
                            </div>
                            <button type="submit"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#5A3621] focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-[#6B4226] dark:hover:bg-[#6B4226] dark:focus:ring-primary-800">
                                {{ t('LBL_SEND_OTP') }}
                            </button>
                            <div class="flex items-center space-x-2">
                                <label for="dont_have_an_account"
                                       class="text-sm font-inter text-black dark:text-gray-400">{{
                                        t('LBL_DONT_HAVE_AN_ACCOUNT?')
                                    }}</label>
                                <a @click="switchToPopup('sign-up')"
                                   class="hover:underline font-inter text-[#6B4226]" target="_parent">{{
                                        t('LBL_SIGNUP')
                                    }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div v-if="showOtpPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50"
                 @click="handleOverlayClick">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center" @click.stop>
                    <h2 class="text-sm font-inter text-black dark:text-gray-400">{{ t('LBL_OTP_AUTHENTICATION') }}</h2>
                    <div class="flex justify-center">
                        <svg width="309" height="5" viewBox="0 0 309 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 4L309 1" stroke="black" stroke-opacity="0.25"/>
                        </svg>
                    </div>
                    <p class="text-sm font-inter text-black dark:text-gray-400">
                        {{ t('LBL_A_VERIFICATION_CODE_IS_SENT_TO_YOUR_NUMBER') }}</p>

                    <div class="flex justify-between mt-4 mb-4">
                        <input
                            type="text"
                            v-for="(digit, index) in otpData"
                            :key="index"
                            v-model="otpData[index]"
                            class="w-[50px] h-12 bg-neutral-100 ml-2 mr-2 text-center border-gray-300 rounded"
                            maxlength="1"
                            :ref="el => otpInputRefs[index] = el"
                            @input="autoFocusNext(index)"
                        />
                    </div>

                    <p class="text-sm font-inter text-black dark:text-gray-400 mb-1.5">
                        <span v-if="countdown > 0">{{ t('LBL_YOU_DID_NOT_RECEIVE_THE_CODE_RESEND') }}({{ countdown }}s)</span>
                        <span v-else><a href="#" @click="resendCode" class="font-bold cursor-pointer">{{$lang('LBL_RESEND')}}</a></span>
                    </p>

                    <button class="bg-[#6B4226] text-white px-4 py-2 rounded-lg mt-1 hover:bg-[#5A3621] " @click="authenticateOtp">
                        {{ t('LBL_SIGNIN') }}
                    </button>
                </div>
            </div>

        </div>
        <fwb-toast closable :type="statusType" v-show="statusMessage" class="absolute bottom-10">
            {{ statusMessage }}
        </fwb-toast>
    </div>
</template>

<script setup>
import {ref, onMounted, nextTick, defineEmits, defineProps} from 'vue';
import {useI18n} from 'vue-i18n';
import {useStore} from 'vuex';
import {FwbToast} from 'flowbite-vue';
import {notify} from "notiwind";

const store = useStore();
const {t} = useI18n();
const showOtpPopup = ref(false); // Control the OTP pop-up visibility
const countdown = ref(60); // Countdown timer
const otpData = ref(['', '', '', '', '', '']); // Store OTP input values
const otpInputRefs = ref([]);
const mobile_phone = ref('');
const statusMessage = ref(''); // State for success or error messages
const statusType = ref(''); // State for success or error messages

const emit = defineEmits();
const props = defineProps({
    isVisible: {
        type: Boolean,
        required: true,
    },
});
const close = () => {
    emit('closePopup');
};

const switchToPopup = (popup) => {
    emit('switchPopup', popup);
};

// Function to start the countdown
const startCountdown = () => {
    const interval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            clearInterval(interval); // Clear the interval when countdown reaches 0
        }
    }, 1000);
};

// Function to handle send OTP
const handleSendOTP = async () => {
    try {
        await store.dispatch('genOtp', {mobile_phone: mobile_phone.value});
        showOtpPopup.value = true;
        countdown.value = 30;
        startCountdown();
    } catch (error) {
        console.error('Sign-in failed:', error);
    }
};

// Function to resend OTP code
const resendCode = async () => {
    try {
        await store.dispatch('genOtp', {mobile_phone: mobile_phone.value});
        countdown.value = 30;
        startCountdown();
        //clear otp
        otpData.value = ['', '', '', '', '', ''];
    } catch (error) {
        console.error('Failed to resend code:', error);
    }
};

// Function to authenticate OTP
const authenticateOtp = async () => {
    const otp = otpData.value.join('');
    try {
        const response = await store.dispatch('authOtp', {
            otp: otp,
            user_id: store.getters.user.user_id
        });
        if (response.success) {
            showOtpPopup.value = false;
            notify({
                group: "foo",
                title: "Success",
                text: "Login successfully!",
            }, 4000);
            close();
        } else {
            statusMessage.value = response.message;
            statusType.value = 'danger';
            setInterval(() => {
                statusMessage.value = '';
            }, 3000);
        }
    } catch (error) {
        console.error('Failed to authenticate OTP:', error);
    }
};

// Function to auto-focus the next input field
const autoFocusNext = (index) => {
    nextTick(() => {
        if (otpData.value[index].length === 1 && index < otpData.value.length - 1) {
            // Make sure otpInputRefs[index + 1] is a DOM element before calling focus
            const nextInput = otpInputRefs.value[index + 1];
            if (nextInput && nextInput.focus) {
                nextInput.focus();
            }
        }
    });
};

onMounted(() => {
    // Set up refs for each OTP input element
    otpInputRefs.value = otpData.value.map(() => null);
});

// Function to close OTP pop-up on overlay click
const handleOverlayClick = () => {
    showOtpPopup.value = false;
};

</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kaisei+Decol&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Kaisei+Decol:wght@700&display=swap');

/* Apply Inter font for the entire page */
* {
    font-family: 'Inter', sans-serif;
}

/* Apply Kaisei Decol only for the title */
.text-xl {
    font-family: 'Kaisei Decol', sans-serif;
}
</style>
