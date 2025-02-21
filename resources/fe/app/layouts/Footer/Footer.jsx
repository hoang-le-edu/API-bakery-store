import React from "react";
import {Link} from "react-router-dom";
import {FaFacebook, FaInstagram} from "react-icons/fa";

const Footer = () => {
    return (
        <footer className="bg-[#2D2D2D] text-white py-10">
            <div className="container mx-auto px-6 lg:px-20">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
                    {/* First Column: Logo and BEPMETAY */}
                    <div className="flex flex-col items-start">
                        <img src={"/build/assets/logo.png"} alt="Logo" className="w-36 mb-4"/>
                        <span className="text-2xl font-bold tracking-wide">BEPMETAY</span>
                    </div>

                    {/* Second Column: Navigation Links */}
                    <div>
                        <h2 className="text-2xl font-semibold mb-4">Shopping Now</h2>
                        <ul className="space-y-3 text-lg">
                            <li>
                                <Link to="/" className="hover:text-gray-200 transition-all">Home</Link>
                            </li>
                            <li>
                                <Link to="/menu" className="hover:text-gray-200 transition-all">Menu</Link>
                            </li>
                        </ul>
                    </div>

                    {/* Third Column: Contact Information */}
                    <div>
                        <h2 className="text-2xl font-semibold mb-4">Contact Us</h2>
                        <ul className="space-y-3 text-lg">
                            <li>Phone: <a href="tel:0927818888" className="hover:text-gray-200 transition-all">0927818888
                                (Dì Duyên)</a></li>
                            <li>Phone: <a href="tel:0566979979" className="hover:text-gray-200 transition-all">0566979979
                                (Bé Thư)</a></li>
                            <li>Web: <a href="https://bepmetay.com" target="_blank" rel="noopener noreferrer"
                                        className="hover:text-gray-200 transition-all">bepmetay.com</a></li>
                        </ul>
                    </div>

                    {/* Fourth Column: Social Media Links */}
                    <div>
                        <h2 className="text-2xl font-semibold mb-4">Follow Us</h2>
                        <div className="flex space-x-6">
                            <a href="https://www.facebook.com/duyen.nguyen.344258" target="_blank"
                               rel="noopener noreferrer" className="hover:text-gray-200 transition-all">
                                <FaFacebook className="text-3xl"/>
                            </a>
                            <a href="https://www.instagram.com/lat_12_12/" target="_blank" rel="noopener noreferrer"
                               className="hover:text-gray-200 transition-all">
                                <FaInstagram className="text-3xl"/>
                            </a>
                        </div>
                    </div>
                </div>

                {/* Footer Bottom */}
                <div className="mt-5 border-t border-gray-300 pt-5 text-center text-lg font-medium">
                    © 2025 BEPMETAY. All rights reserved.
                </div>
            </div>
        </footer>
    );
};

export default Footer;
