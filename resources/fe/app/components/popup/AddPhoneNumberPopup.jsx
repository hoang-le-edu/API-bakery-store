import React, {useEffect, useState, useRef} from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {resetStatus, userLogin, userRegister} from '../../redux/action/userAction.js';
import SpinnerLoading from '../loading/SpinnerLoading.jsx';
// import '../../styles/SignInPasswordPopup.css';
import {doSignInWithEmailAndPassword, doSignInWithGoogle} from "../../modules/firebase/auth.js";
import {useAuth} from "../../hooks/contexts/authContext/index.jsx";
import {notify} from "../../layouts/notification/notify.jsx";

const AddPhoneNumberPopup = ({isVisible, closePopup, switchPopup, registerData}) => {
    const inputRef = useRef({phone: '',});
    const dispatch = useDispatch();
    const {loading, success, fail, message} = useSelector((state) => state.user);

    const handleChangeInput = (e) => {
        const {name, value} = e.target;
        inputRef.current[name] = value; // Lưu giá trị vào ref nhưng không làm rerender
    };

    const handleLogin = async (e) => {
        e.preventDefault();

        try {
            // prepare data
            const input = {
                name: registerData.displayName,
                email: registerData.email,
                phone_number: inputRef.current.phone,
                password: '', // password is empty because user login with google
                c_password: '',
                auth_type: registerData.auth_type,
            }

            // save user in database
            dispatch(userRegister(input, registerData.firebase_uid));

            closePopup();
            notify('success', 'Login successfully');
        } catch (error) {
            console.log(error);
        }

    };

    useEffect(() => {
        if (success) {
            dispatch(resetStatus());
            closePopup();
        } else if (fail) {
            dispatch(resetStatus());
        }
    }, [success, fail]);

    if (!isVisible) return null;

    return (
        <div className="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50"
             onClick={closePopup}>
            <div className="bg-white rounded-lg shadow-lg w-[90%] md:w-1/2 lg:w-1/3 p-6"
                 onClick={(e) => e.stopPropagation()}>
                <h2 className="text-xl font-bold text-center mb-4">Add phone number for your account</h2>
                <form className="space-y-4" onSubmit={handleLogin}>
                    <div>
                        <label htmlFor="phone" className="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            onChange={handleChangeInput}
                            className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary"
                            placeholder="Enter your phone number"
                            required={true}
                        />
                    </div>
                    <button type="submit"
                            className="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                        {loading ? <SpinnerLoading/> : 'Submit'}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default AddPhoneNumberPopup;
