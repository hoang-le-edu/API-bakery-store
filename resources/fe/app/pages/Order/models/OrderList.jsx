import React, {useEffect, useRef, useState} from 'react';
import {formatVietnameseCurrency} from '../../../locales/currencyFormat.js';
import {formatDateTime} from '../../../locales/dateFormat.js';
import {createPaymentLink} from "../../../redux/action/paymentAction.js";
import {useDispatch, useSelector} from "react-redux";
import {usePopup} from "../../../hooks/contexts/popupContext/popupState.jsx";
import SpinnerLoading from "../../../components/loading/SpinnerLoading.jsx";

const OrderList = ({orders}) => {

    const dispatch = useDispatch();
    const {openPopup} = usePopup();

    const {paymentLink, loading} = useSelector(state => state.payment);
    // const [currentIndex, setCurrentIndex] = useState(null);
    const currentIndexRef = useRef(null); // Use useRef to store index

    const payNow = async (index) => {
        try {
            currentIndexRef.current = index;
            dispatch(createPaymentLink(orders[index].order_id));
        } catch (error) {
            console.error('Error fetching checkout URL:', error);
        }
    };

    useEffect(() => {
        if(paymentLink !== undefined && paymentLink !== null) {
            openPopup({popupName: 'qrPayment', paymentLink: paymentLink, order: orders[currentIndexRef.current]});
        }

        // reset to open 1 order continuously
        return () => {
            dispatch({ type: 'RESET_PAYMENT_LINK' }); // Dispatch an action to reset the state
        };

    }, [paymentLink]);


    return (<div className="flex mt-4 shadow-lg rounded-lg">
            <div
                className="scrollbar-thin h-[700px] w-full gap-6 grid overflow-y-auto rounded-lg p-6 bg-gray-50 dark:bg-gray-900">
                {orders !== undefined && orders.length > 0 ? (orders.map((order, index) =>
                    (<div key={order.order_id}
                          className="w-[100%] h-full border flex flex-col justify-between border-gray-300 p-5 rounded-xl shadow-md cursor-pointer hover:bg-gray-100 transition-all duration-300 ease-in-out">

                        {/* Basic order info */}
                        {/*<div className="order-info flex w-full justify-between items-center">*/}
                        {/*    <p className="text-lg font-semibold flex items-center gap-2">*/}
                        {/*        #{order.order_number}*/}
                        {/*        <span*/}
                        {/*            className="text-sm font-normal text-gray-500">- {formatDateTime(order.order_date)}</span>*/}
                        {/*    </p>*/}
                        {/*    <span className={`text-md font-semibold px-3 py-1 rounded-full*/}
                        {/*        ${order.status === "Completed" ? "bg-green-200 text-green-800" : order.status === "Pending" ? "bg-yellow-200 text-yellow-800" : "bg-red-200 text-red-800"}`}>*/}
                        {/*        {order.status}*/}
                        {/*    </span>*/}
                        {/*</div>*/}
                        {/* Basic order info */}
                        {/* Basic order info */}
                        <div className="order-info flex w-full justify-between items-center">
                            {/* Order Number & Date */}
                            <div className="flex items-center gap-3">
                                <p className="text-lg font-semibold flex items-center gap-2">
                                    #{order.order_number}
                                    <span className="text-sm font-normal text-gray-500">- {formatDateTime(order.order_date)}</span>
                                </p>

                                {/* Payment Method */}
                                <span className={`text-sm font-medium px-2 py-1 rounded-lg shadow-sm
                                    ${order.payment_method === "Cash" ? "bg-yellow-100 text-yellow-700" : "bg-blue-100 text-blue-700"}`}>
                                    {order.payment_method === 'Cash' ? '🛵 Cash on delivery' : '💳 Online banking'}
                                </span>
                            </div>

                            <span className={`text-sm font-medium px-2 py-0.5 rounded-md shadow-sm
                                ${order.status === "Completed" ? "bg-green-200 text-green-800" :
                                                    order.status === "Pending" ? "bg-yellow-200 text-yellow-800" :
                                                        order.status === "Wait For Approval" ? "bg-[#f26d78] text-white" :
                                                            "bg-red-200 text-red-800"}`}>
                                {order.status}
                            </span>
                        </div>


                        {/* Order details */}
                        <div className="overflow-y-auto w-full h-full scrollbar-thin mt-3">
                            {order.order_detail.map((item) => (
                                <div key={item.id} className="order-item rounded-xl w-full last:pb-4">
                                    <div
                                        className="flex h-full p-2 w-full items-center bg-white shadow-sm rounded-lg">
                                        {/* Product image */}
                                        <div className="w-[10%] min-w-[60px]">
                                            <img src={item.image || '/build/assets/Product/empty-image.png'}
                                                 alt="Product"
                                                 className="w-full shadow-md rounded-lg aspect-square"/>
                                        </div>
                                        {/* Product info */}
                                        <div className="flex flex-col w-full pl-3">
                                                <span
                                                    className="text-md font-semibold text-gray-700">{item.product_name} ({item.size})</span>
                                            {item.count_topping !== 0 && (<span
                                                className="text-xs text-gray-500">+ {item.count_topping} toppings</span>)}
                                            <span className="text-xs text-gray-500">Quantity: {item.quantity}</span>
                                            {item.note &&
                                                <span className="text-xs text-gray-400">Note: {item.note}</span>}
                                        </div>
                                        {/* Price */}
                                        <div
                                            className="font-semibold text-md text-green-600">{formatVietnameseCurrency(item.total_price)}</div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Payment Info */}
                        <div className="mt-3">
                            {/* Total price and buttons */}
                            <div className="w-full flex justify-between items-center mt-3">
                                <p className="text-gray-600 text-md">
                                    Total ({order.count_product} products): <span
                                    className="font-bold text-lg text-green-500">{formatVietnameseCurrency(order.total_price)}</span>
                                </p>
                                <div>
                                    <button
                                        onClick={() => cancelOrder(order.order_id)}
                                        className="text-sm border border-red-400 mr-2 text-red-600 font-semibold hover:text-red-700 hover:bg-red-200 transition-all px-3 py-1 rounded-full"
                                    >
                                        Cancel
                                    </button>
                                    {order.payment_status === 'pending' && order.payment_method === 'Banking' && (
                                        <button
                                            onClick={() => payNow(index)}
                                            className="text-sm bg-green-500 text-white font-semibold hover:bg-green-600 transition-all px-3 py-1 rounded-full"
                                        >
                                            {/*Pay now*/}
                                            {loading && currentIndexRef.current === index ? <SpinnerLoading/> : 'Pay now'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>))) : (<div className="flex flex-col items-center justify-center w-full h-full">
                        <img src="/assets/no-data.png" alt="No data" className="w-40 opacity-60"/>
                        <span className="text-gray-500 mt-2">Nothing to show</span>
                    </div>
                )}
            </div>
        </div>

    );
};

export default OrderList;
