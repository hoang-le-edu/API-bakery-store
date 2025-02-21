import React, { useEffect, useRef } from "react";
import {MdLogout, MdOutlinePerson} from "react-icons/md";
import { useNavigate } from "react-router-dom";
import { doSignOut } from "../../modules/firebase/auth.js";
import { usePopup } from "../../hooks/contexts/popupContext/popupState.jsx";

const UserProfileElement = ({ isVisible }) => {
    const navigate = useNavigate();
    const popupRef = useRef(null);
    const { openPopup, closePopup } = usePopup();

    const handleLogout = async () => {
        await doSignOut();
        closePopup();
        navigate("/");
    };

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (popupRef.current && !popupRef.current.contains(event.target)) {
                closePopup();
            }
        };

        if (isVisible) {
            document.addEventListener("mousedown", handleClickOutside);
        } else {
            document.removeEventListener("mousedown", handleClickOutside);
        }

        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, [isVisible, closePopup]);

    if (!isVisible) return null;

    return (
        <div ref={popupRef}
             className="absolute top-17 bg-white shadow-lg rounded-lg border border-gray-200 px-2 py-3 z-50 transition-all transform scale-95 animate-fadeIn">
            <div className="w-36 flex flex-col gap-2">
                <button
                    onClick={() => openPopup({ popupName: 'profile' })}
                    className="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-100 transition duration-200">
                    <MdOutlinePerson className="text-xl text-gray-700" />
                    <span className="text-gray-800">Profile</span>
                </button>
                <button
                    onClick={handleLogout}
                    className="flex items-center gap-3 py-2 px-3 rounded-lg bg-[#f26d78] text-white hover:bg-[#d85563] transition duration-200">
                    <MdLogout className="text-xl" />
                    <span>Logout</span>
                </button>
            </div>
        </div>
    );
};

export default UserProfileElement;
