import {BsCart3} from "react-icons/bs";
import Cookies from "js-cookie";
import React, {useCallback, useEffect, useState} from "react";
import {useDispatch, useSelector} from "react-redux";
import {Link, useNavigate} from "react-router-dom";
import NavMenuElement from "./NavMenuElement.jsx";
import {getUserInfo,} from "../../redux/action/userAction.js";
import SearchInputElement from "./SearchInputElement.jsx";
import UserProfileElement from "./UserProfileElement.jsx";
import SignInPasswordPopup from "../../components/popup/SignInPasswordPopup.jsx";
import CartDrawerPopup from "../../components/popup/CartDrawerPopup.jsx";
import DetailProductPopup from "../../components/popup/DetailProductPopup.jsx";
import {useAuth} from "../../hooks/contexts/authContext/index.jsx";
import {doSignOut} from "../../modules/firebase/auth.js";
import AddPhoneNumberPopup from "../../components/popup/AddPhoneNumberPopup.jsx";
import RegisterPopup from "../../components/popup/RegisterPopup.jsx";
import {usePopup} from "../../hooks/contexts/popupContext/popupState.jsx";
import CartSelectionPopup from "../../components/popup/CartSelectionPopup.jsx";
import QRPaymentPopup from "../../components/popup/QRPaymentPopup.jsx";
import {FaRegUserCircle} from "react-icons/fa";


const Header = () => {
    const accessToken = Cookies.get("accessToken");
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [state, setState] = useState({
        cartOpen: false,
        profileOpen: false,
    });
    const {cartOpen, profileOpen} = state;
    const [openMenu, setOpenMenu] = useState(false);

    const {currentPopup, openPopup, closePopup, switchPopup} = usePopup();

    // wait data from switch popup detailProduct
    const selectedProduct = useSelector(state => state.product.product);

    // return user logged in or not
    const {userLoggedIn} = useAuth();

    const handleNavMenu = () => {
        setOpenMenu(!openMenu);
    };

    useEffect(() => {
        if (accessToken) {
            dispatch(getUserInfo());
        }
    }, [dispatch, accessToken]);

    const handleCartClick = useCallback((e) => {
        if (!userLoggedIn)
            openPopup({popupName: 'login'});
        else
            openPopup({popupName: 'cartDrawer'});
    }, [openPopup]);

    useEffect(() => {
        console.log("currentPopup in header: ", currentPopup);
    }, [currentPopup]);

    return (
        <header className="bg-white fixed top-0 w-full z-10 px-2 border-b">
            {/* Overlay when cart or profile is open */}
            {(cartOpen || profileOpen) && (
                <div
                    onClick={closeModals}
                    className="fixed top-0 bottom-0 right-0 left-0 bg-secondary/50 z-30"
                ></div>
            )}

            <div className="container mx-auto">
                <div className="flex flex-row items-center justify-between space-x-2 py-4 max-h-[70px]">
                    {/* Logo */}
                    <div className="lg:flex hidden items-center text-xl font-semibold tracking-[2px] space-x-2"
                         onClick={() => navigate("/")}>
                        <Link to="/" className="flex items-center space-x-2">
                            <img
                                src="/build/assets/logo.png"
                                alt="logo"
                                className="h-12 w-auto object-contain"
                            />
                            <span>BEPMETAY</span>
                        </Link>
                    </div>
                    {/* Search bar */}
                    <SearchInputElement/>

                    {/* Navigation */}
                    <div className="flex flex-row justify-between w-[30%] px-2  md:w-auto">
                        <NavMenuElement
                            handleNavMenu={handleNavMenu}
                            openMenu={openMenu}

                            userLoggedIn={userLoggedIn}
                            switchPopup={switchPopup}

                        />

                        {/* Buttons (Cart, Profile, Menu) */}
                        <div className="flex flex-row justify-evenly items-center w-full gap-x-0 md:gap-x-3">
                            {/* Cart button */}
                            <button className="relative" onClick={handleCartClick}>
                                <BsCart3 className="text-lg lg:text-xl"/>
                                {/*<div*/}
                                {/*    className={`${cart.length ? "flex" : "hidden"} items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white text-[12px] absolute -top-3 -right-3`}>*/}
                                {/*    {cart.length}*/}
                                {/*</div>*/}
                            </button>

                            {/* login pop up */}
                            {currentPopup?.popupName === 'login' &&
                                <SignInPasswordPopup isVisible={currentPopup?.popupName === 'login'}
                                                     closePopup={closePopup} switchPopup={switchPopup}/>}

                            {/* register popup */}
                            {currentPopup?.popupName === 'register' &&
                                <RegisterPopup isVisible={currentPopup?.popupName === 'register'}
                                               closePopup={closePopup} switchPopup={switchPopup}/>
                            }

                            {/* Add phone number when user login by Google first time */}
                            {currentPopup?.popupName === 'addPhone' &&
                                <AddPhoneNumberPopup isVisible={currentPopup?.popupName === 'addPhone'}
                                                     closePopup={closePopup}
                                                     switchPopup={switchPopup}
                                                     registerData={currentPopup.registerData}/>}

                            {/* Select available cart when user add product to cart */}
                            {currentPopup?.popupName === 'cartSelection' &&
                                <CartSelectionPopup isVisible={currentPopup?.popupName === 'cartSelection'}
                                                    cartData={currentPopup?.cartData}
                                                    product={currentPopup?.product}
                                                    resetState={currentPopup?.resetState}/>}

                            {/* cart drawer */}
                            {currentPopup?.popupName === 'cartDrawer' &&
                                <CartDrawerPopup isVisible={currentPopup?.popupName === 'cartDrawer'}/>}

                            {/* Add Detail product popup opened from Menu */}
                            {currentPopup?.popupName === 'details' &&
                                <DetailProductPopup isVisible={currentPopup?.popupName === 'details'} isEdit={false}/>}

                            {/* Update Detail product pop up opened from CartDrawer */}
                            {currentPopup?.popupName === 'details' && currentPopup?.productDetailInCart && (
                                <DetailProductPopup
                                    isVisible={currentPopup?.popupName === 'details'}
                                    isEdit={true}
                                    productDetailInCart={currentPopup?.productDetailInCart}
                                />
                            )}

                            {/* QR payment popup */}
                            {currentPopup?.popupName === 'qrPayment' && currentPopup?.paymentLink &&
                                <QRPaymentPopup isVisible={currentPopup?.popupName === 'qrPayment'} paymentLink={currentPopup?.paymentLink} cart={currentPopup?.order} />}

                            {/* Select Profile button popup */}
                            {currentPopup?.popupName === 'logout' && (
                                <UserProfileElement isVisible={currentPopup?.popupName === 'logout'}/>
                            )}

                            {/* Profile button */}
                            {userLoggedIn && (
                                <div className="relative flex items-center">
                                    <button onClick={() => openPopup({popupName : 'logout'})} className="px-2 text-lg">
                                        <FaRegUserCircle/>
                                    </button>
                                </div>
                            )}

                            {/* Menu button */}
                            {/*<button onClick={handleNavMenu} className="block md:hidden text-lg ">*/}
                            {/*    <MdOutlineMenu/>*/}
                            {/*</button>*/}
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
};

export default Header;
