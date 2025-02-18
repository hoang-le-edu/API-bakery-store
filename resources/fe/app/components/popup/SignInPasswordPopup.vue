<template>
    <div v-if="isVisible"
         class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex lg:items-center justify-end lg:justify-center z-99999"
         @click="close">
        <div class="bg-white lg:rounded-lg shadow-lg max-w-md h-fit w-[65%] lg:h-fit lg:w-full relative" @click.stop>
            <div class="flex flex-col items-center justify-center ">
                <div
                    class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <div
                            class="text-xl font-bold leading-tight tracking-tight text-black md:text-2xl dark:text-white">
                            {{ t('LBL_PINY_CLOUD_BREAD_AND_TEA') }}
                        </div>
                        <form class="space-y-4 md:space-y-6" action="#">
                            <div>
                                <label for="phone" class="block mb-2 text-sm font-inter text-black dark:text-white">{{
                                        t('LBL_PHONE_NUMBER')
                                    }}</label>
                                <input type="text" name="phone" id="phone" v-model="phone" @input="validatePhone"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       placeholder="" required="">
                            </div>

                            <div class="input-container">
                                <label for="password" class="block mb-2 text-sm font-inter text-black dark:text-white">{{
                                        t('LBL_PASSWORD')
                                    }}</label>
                                <div class="flex items-center relative">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password"
                                        id="password"
                                        placeholder=""
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required
                                    />

                                    <span @click="togglePassword" class="input-icon cursor-pointer">
                                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
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

                            <div>
                                <a @click="switchToPopup('forgot-password')"
                                   class="hover:underline text-sm font-inter text-black dark:text-blue-400 hover:underline"
                                   target="_parent">{{ t('LBL_FORGOT_PASSWORD?') }}</a>
                            </div>

                            <button type="submit"
                                    class="w-full text-white bg-[#6B4226] hover:bg-[#6B4226] focus:ring-4 focus:outline-none focus:ring-primary-300 font-inter rounded-lg text-sm px-5 py-2.5 text-center dark:bg-[#6B4226] dark:hover:bg-[#6B4226] dark:focus:ring-primary-800">
                                {{ t('LBL_SIGNIN') }}
                            </button>
                            <div class="flex items-center space-x-2">
                                <label for="dont_have_an_account"
                                       class="text-sm font-inter text-black dark:text-gray-400">{{
                                        t('LBL_DONT_HAVE_AN_ACCOUNT?')
                                    }}</label>
                                <a  @click="switchToPopup('sign-up')"
                                    class="hover:underline text-[#6B4226]"
                                   target="_self">{{ t('LBL_SIGNUP') }}</a>
                            </div>
                        </form>

                        <div class="flex space-x-1 items-center">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M1.6665 5.83366C1.6665 3.53247 3.53198 1.66699 5.83317 1.66699H14.1665C16.4677 1.66699 18.3332 3.53247 18.3332 5.83366V14.167C18.3332 16.4682 16.4677 18.3337 14.1665 18.3337H5.83317C3.53198 18.3337 1.6665 16.4682 1.6665 14.167V5.83366ZM5.83317 3.33366C4.45246 3.33366 3.33317 4.45295 3.33317 5.83366V14.167C3.33317 15.5477 4.45246 16.667 5.83317 16.667H14.1665C15.5472 16.667 16.6665 15.5477 16.6665 14.167V5.83366C16.6665 4.45295 15.5472 3.33366 14.1665 3.33366H5.83317ZM10.5891 6.91107C10.9145 7.23651 10.9145 7.76414 10.5891 8.08958L9.51168 9.16699H12.4998C12.9601 9.16699 13.3332 9.54009 13.3332 10.0003C13.3332 10.4606 12.9601 10.8337 12.4998 10.8337H9.51168L10.5891 11.9111C10.9145 12.2365 10.9145 12.7641 10.5891 13.0896C10.2637 13.415 9.73602 13.415 9.41058 13.0896L6.91058 10.5896C6.58514 10.2641 6.58514 9.73651 6.91058 9.41107L9.41058 6.91107C9.73602 6.58563 10.2637 6.58563 10.5891 6.91107Z"
                                      fill="black"/>
                            </svg>
                            <!-- Nút quay lại màn hình OTP -->
                            <button @click="switchToPopup('sign-in')"
                                    class="text-sm font-inter text-black dark:text-blue-400 hover:underline">
                                {{ t('LBL_BACK_TO_SIGN_IN_WITH_OTP') }}
                            </button>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {useI18n} from 'vue-i18n';
import {ref, computed, defineEmits, defineProps} from 'vue';
import {useRouter} from 'vue-router'; // Import useRouter

const {t} = useI18n();
const showPassword = ref(false);
const phone = ref('');
const isPhoneValid = computed(() => phone.value.length > 0); // Kiểm tra tính hợp lệ của số điện thoại

const router = useRouter(); // Khởi tạo router
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
// Hàm chuyển đổi trạng thái hiển thị/ẩn mật khẩu
const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

// Hàm xử lý nhấn vào "Quên mật khẩu?"
const handleForgotPassword = () => {
    router.push('/sign-in/forgot-password'); // Điều hướng đến trang Forgot Password
};
</script>


<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kaisei+Decol&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Kaisei+Decol:wght@700&display=swap');

/* Áp dụng font Inter cho toàn bộ trang */
* {
    font-family: 'Inter', sans-serif;
}

/* Áp dụng Kaisei Decol chỉ cho tiêu đề */
.text-xl {
    font-family: 'Kaisei Decol', sans-serif;
}

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
