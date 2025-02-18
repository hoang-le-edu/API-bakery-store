<template>
    <div v-if="isVisible"
         class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex lg:items-center justify-end lg:justify-center"
         @click="close">
        <div class="bg-white lg:rounded-lg shadow-lg max-w-md h-fit w-[65%] lg:h-fit lg:w-full relative" @click.stop>
            <div class="flex flex-col items-center justify-center">
                <div
                    class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <div v-if="step === 1">
                            <label for="phone" class="block mb-2 font-inter text-black dark:text-white">{{
                                    t('LBL_PHONE_NUMBER')
                                }}</label>
                            <input type="text"
                                   id="phone"
                                   v-model="phone"
                                   placeholder=""
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                   required=""/>
                            <button @click="sendCode"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#6B4226] font-inter rounded-lg  px-5 py-2.5 mt-4">
                                {{ t('LBL_SEND_CODE') }}
                            </button>
                        </div>

                        <div v-else-if="step === 2">
                            <label for="code" class="block mb-2 font-inter text-black dark:text-white">{{
                                    t('LBL_ENTER_CODE')
                                }}</label>
                            <input type="text" id="code" v-model="code" placeholder=""
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                   required/>
                            <button @click="verifyCode"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#6B4226]  font-inter rounded-lg text-sm px-5 py-2.5 mt-4">
                                {{ t('LBL_VERIFY_CODE') }}
                            </button>
                        </div>

                        <div class="space-y-4 md:space-y-6" v-else-if="step === 3">
                            <!-- Mật khẩu mới -->
                            <div class="input-container">
                                <label for="new-password"
                                       class="block mb-2 text-sm font-inter text-black dark:text-white">{{
                                        t('LBL_NEW_PASSWORD')
                                    }}</label>
                                <div class="flex items-center relative">
                                    <input
                                        :type="showNewPassword ? 'text' : 'password'"
                                        name="new-password"
                                        id="newPassword"
                                        v-model="newPassword"
                                        placeholder=""
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required
                                    />
                                    <span @click="toggleNewPassword" class="input-icon cursor-pointer">
                                    <svg v-if="!showNewPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor"
                                         class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-.739 2.467-2.488 4.573-4.737 5.717a9.956 9.956 0 01-9.622 0C4.946 16.573 3.197 14.467 2.458 12z"/>
                                    </svg>
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

                            <!-- Xác nhận mật khẩu -->
                            <div class="input-container">
                                <label for="confirm-password"
                                       class="block mb-2 text-sm font-inter text-black dark:text-white">{{
                                        t('LBL_CONFIRM_PASSWORD')
                                    }}</label>
                                <div class="flex items-center relative">
                                    <input
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        name="confirm-password"
                                        id="confirmPassword"
                                        v-model="confirmPassword"
                                        placeholder=""
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required
                                    />
                                    <span @click="toggleConfirmPassword" class="input-icon cursor-pointer">
                                    <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor"
                                         class="w-6 h-6 text-gray-700 dark:text-gray-300">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-.739 2.467-2.488 4.573-4.737 5.717a9.956 9.956 0 01-9.622 0C4.946 16.573 3.197 14.467 2.458 12z"/>
                                    </svg>
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
                            <button @click="resetPassword"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#6B4226] font-inter rounded-lg text-sm px-5 py-2.5 mt-4">
                                {{ t('LBL_CONFIRM_RESET') }}
                            </button>
                        </div>

                        <!--Popup đổi mật khẩu thành công-->
                        <div v-if="showPopup"
                             class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50"
                             @click="closePopup">
                            <div class="bg-white p-8 rounded-lg w-75" @click.stop>
                                <p class="font-inter text-black text-center">{{ t('LBL_UPDATE_SUCCESSFUL') }}!</p>
                                <button @click="closePopup"
                                        class="w-full text-white bg-[#6B4226] hover:bg-[#6B4226] font-inter rounded-lg text-sm px-5 py-2.5 mt-4">
                                    {{ t('LBL_OK') }}
                                </button>
                            </div>
                        </div>

                        <!--Nút quay lại sign in-->
                        <div class="flex space-x-1 items-center">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M1.6665 5.83366C1.6665 3.53247 3.53198 1.66699 5.83317 1.66699H14.1665C16.4677 1.66699 18.3332 3.53247 18.3332 5.83366V14.167C18.3332 16.4682 16.4677 18.3337 14.1665 18.3337H5.83317C3.53198 18.3337 1.6665 16.4682 1.6665 14.167V5.83366ZM5.83317 3.33366C4.45246 3.33366 3.33317 4.45295 3.33317 5.83366V14.167C3.33317 15.5477 4.45246 16.667 5.83317 16.667H14.1665C15.5472 16.667 16.6665 15.5477 16.6665 14.167V5.83366C16.6665 4.45295 15.5472 3.33366 14.1665 3.33366H5.83317ZM10.5891 6.91107C10.9145 7.23651 10.9145 7.76414 10.5891 8.08958L9.51168 9.16699H12.4998C12.9601 9.16699 13.3332 9.54009 13.3332 10.0003C13.3332 10.4606 12.9601 10.8337 12.4998 10.8337H9.51168L10.5891 11.9111C10.9145 12.2365 10.9145 12.7641 10.5891 13.0896C10.2637 13.415 9.73602 13.415 9.41058 13.0896L6.91058 10.5896C6.58514 10.2641 6.58514 9.73651 6.91058 9.41107L9.41058 6.91107C9.73602 6.58563 10.2637 6.58563 10.5891 6.91107Z"
                                      fill="black"/>
                            </svg>
                            <a @click="switchToPopup('sign-in')"
                               class="hover:underline font-inter text-sm text-black dark:text-blue-400 hover:underline"
                               target="_parent">{{ t('LBL_BACK_TO_SIGN_IN') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {defineEmits, defineProps, ref} from 'vue';
import {useI18n} from 'vue-i18n';

const {t} = useI18n();
const step = ref(1);
const phone = ref('');
const code = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const showPopup = ref(false); // Biến điều khiển hiển thị popup
const emit = defineEmits();
const props = defineProps({
    isVisible: {
        type: Boolean,
        required: true
    },

});
const close = () => {
    emit('closePopup');
};
const switchToPopup = (popup) => {
    emit('switchPopup', popup);
};
const toggleNewPassword = () => {
    showNewPassword.value = !showNewPassword.value;
};

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};

// Hàm gửi mã xác nhận
const sendCode = () => {
    if (!phone.value) {
        alert(t('LBL_ENTER_PHONE_NUMBER'));
        return;
    }
    // Gửi mã tới số điện thoại (thực hiện gọi API ở đây)
    console.log(`Gửi mã đến số điện thoại: ${phone.value}`);

    // Giả lập việc gửi mã thành công
    step.value = 2;
};

// Hàm xác minh mã
const verifyCode = () => {
    if (!code.value) {
        alert(t('LBL_ENTER_CODE'));
        return;
    }
    // Xác minh mã (thực hiện gọi API ở đây)
    console.log(`Xác minh mã: ${code.value}`);

    // Giả lập việc xác minh mã thành công
    step.value = 3;
};

// Hàm đặt lại mật khẩu
const resetPassword = () => {
    if (!newPassword.value || !confirmPassword.value) {
        alert(t('LBL_ENTER_PASSWORD'));
        return;
    }
    if (newPassword.value !== confirmPassword.value) {
        alert(t('LBL_PASSWORDS_DO_NOT_MATCH'));
        return;
    }

    // Hiển thị popup thông báo khi mật khẩu trùng khớp
    showPopup.value = true;
};

const closePopup = () => {
    showPopup.value = false; // Đóng popup
};
</script>

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
