import React, {useEffect, useRef, useState} from "react";
import {useNavigate} from "react-router-dom";
import {useDispatch, useSelector} from "react-redux";
import InputElement from "../element/InputElement.jsx";
import SpinnerLoading from "../loading/SpinnerLoading.jsx";
import {resetStatus, userRegister} from "../../redux/action/userAction.js";
import {doCreateUserWithEmailAndPassword, doUpdateProfile} from "../../modules/firebase/auth.js";
import {notify} from "../../layouts/notification/notify.jsx";

const Register = ({isVisible, closePopup, switchPopup}) => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    // const [input, setInput] = useState({ name: "", email: "", phone_number: "", date_of_birth:"", gender:"", password: "", c_password: "" });
    const inputRef = useRef({
        name: "",
        email: "",
        phone_number: "",
        date_of_birth: "",
        gender: "",
        password: "",
        c_password: ""
    });
    const [error, setError] = useState("");
    const {loading, success, fail, message} = useSelector((state) => state.user);

    const [isRegistering, setIsRegistering] = useState(false);


    // const handleChangeInput = (e) => {
    //     setInput({ ...input, [e.target.name]: e.target.value });
    // }

    const handleChangeInput = (e) => {
        const {name, value} = e.target;
        inputRef.current[name] = value; // Lưu giá trị vào ref nhưng không làm rerender
    };

    const handleRegister = async (e) => {
        e.preventDefault();

        // check password again
        if (inputRef.current.password !== inputRef.current.c_password) {
            notify("error", "Password and Confirm Password are not the same");
            return;
        }

        if (!isRegistering) {
            setIsRegistering(true);
            try {
                // register in firebase
                const userCredential = await doCreateUserWithEmailAndPassword(inputRef.current.email, inputRef.current.password);
                const user = userCredential.user;

                // update name in firebase
                const check = await doUpdateProfile(inputRef.current.name, null);

                // save user in database
                dispatch(userRegister(inputRef.current, user.uid));

                setIsRegistering(false);

                notify("success", "Register successfully");
            } catch (error) {
                setIsRegistering(false);
                notify("error", <>Register failed<br />{error.message}</>);
            }
        }
    };

    useEffect(() => {
        if (success) {
            // navigate("/login");
            dispatch(resetStatus());
            switchPopup('login');
        } else if (fail) {
            setError(message);
            dispatch(resetStatus());
        }
    }, [dispatch, navigate, message, success, fail]);

    if (!isVisible) return null;

    const inputStyle = "mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary";

    return (
        <div className="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50"
             onClick={closePopup}>
            <div className="bg-white rounded-lg shadow-lg w-[90%] md:w-1/2 lg:w-1/3 p-6"
                 onClick={(e) => e.stopPropagation()}>
                <h2 className="text-xl font-bold text-center mb-4">Register</h2>
                <form className="space-y-4" onSubmit={handleRegister}>
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">Full Name</label>
                        <InputElement
                            type="text"
                            style={inputStyle}
                            onChange={handleChangeInput}
                            placeholder="Full Name"
                            name="name"
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">Email</label>
                        <InputElement
                            type="email"
                            style={inputStyle}
                            onChange={handleChangeInput}
                            placeholder="Email"
                            name="email"
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="phone_number" className="block text-sm font-medium text-gray-700">Phone
                            Number</label>
                        <InputElement
                            type="tel"
                            style={inputStyle}
                            onChange={handleChangeInput}
                            placeholder="Phone Number"
                            name="phone_number"
                            required={true}
                        />
                    </div>
                    {/* Dont need data of birth and gender */}
                    {/*<div>*/}
                    {/*    <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700">Date of Birth</label>*/}
                    {/*    <InputElement*/}
                    {/*        type="date"*/}
                    {/*        style={inputStyle}*/}
                    {/*        onChange={handleChangeInput}*/}
                    {/*        placeholder="Date of Birth"*/}
                    {/*        name="date_of_birth"*/}
                    {/*        required*/}
                    {/*    />*/}
                    {/*</div>*/}
                    {/*<div className="flex items-center mb-4">*/}
                    {/*    <label className="mr-4">Gender:</label>*/}
                    {/*    <InputElement*/}
                    {/*        type="radio"*/}
                    {/*        style="mr-2"*/}
                    {/*        onChange={handleChangeInput}*/}
                    {/*        name="gender"*/}
                    {/*        value="Male"*/}
                    {/*        required*/}
                    {/*    />*/}
                    {/*    <label className="mr-4">Male</label>*/}
                    {/*    <InputElement*/}
                    {/*        type="radio"*/}
                    {/*        style="mr-2"*/}
                    {/*        onChange={handleChangeInput}*/}
                    {/*        name="gender"*/}
                    {/*        value="Female"*/}
                    {/*        required*/}
                    {/*    />*/}
                    {/*    <label>Female</label>*/}
                    {/*</div>*/}
                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gray-700">Password</label>
                        <InputElement
                            type="password"
                            style={inputStyle}
                            onChange={handleChangeInput}
                            placeholder="Password"
                            name="password"
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="c_password" className="block text-sm font-medium text-gray-700">Password
                            Again</label>
                        <InputElement
                            type="password"
                            style={inputStyle}
                            onChange={handleChangeInput}
                            placeholder="Password Again"
                            name="c_password"
                            required
                        />
                    </div>
                    <div>
                        <button
                            type="submit"
                            className="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                            {loading ? <SpinnerLoading/> : "Register"}
                        </button>
                    </div>
                    {error && <div className="text-red-500 text-sm">{error}</div>}

                    <div className="text-center mt-4">
                        <span className="text-sm text-gray-700">Already have an account? </span>
                        <button type="button" onClick={() => switchPopup('login')}
                                className="text-sm text-blue-500 hover:underline">Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default Register;


