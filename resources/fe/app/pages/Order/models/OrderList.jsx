import React from 'react';
import {formatVietnameseCurrency} from '../../../locales/currencyFormat.js';
import {formatDateTime} from '../../../locales/dateFormat.js';

const OrderList = ({orders}) => {
    return (
        <div className="flex mt-4 shadow-lg rounded-lg">
            <div
                className="scrollbar-thin h-[700px] w-full gap-4 grid grid-cols-2 overflow-y-auto rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
                {orders !== undefined && orders.length > 0 ? (
                    orders.map((order) => (
                        <div key={order.order_id}
                             className="w-[100%] h-full border flex flex-col justify-between border-gray-200 p-4 rounded-lg shadow-sm cursor-pointer hover:bg-gray-50 transition-colors">
                            {/*basic*/}
                            <div className="order-info flex w-full justify-between">
                                <p className="text-lg font-semibold flex items-center gap-2">#{order.order_number} <span
                                    className="text-sm font-normal">- {formatDateTime(order.order_date)}</span></p>
                                <span className="text-md text-[#f26d78]">{order.status}</span>
                            </div>

                            {/*detail*/}
                            <div className="overflow-y-auto w-full h-full scrollbar-thin mt-2">
                                {order.order_detail.map((item) => (
                                    <div key={item.id} className="order-item rounded-xl w-full last:pb-4">
                                        <div className="flex h-full pt-2 pl-3 w-full pb-2">
                                            <div className="flex flex-col w-full">
                                                <div
                                                    className="flex items-center justify-between w-[100%] max-h-[200px] overflow-y-auto scrollbar-thin">
                                                    {/*image*/}
                                                    <div className="w-[8%] min-w-[60px]">
                                                        <img src={item.image || '/build/assets/Product/empty-image.png'}
                                                             alt="Product"
                                                             className="w-full shadow-lg rounded-lg aspect-square"/>
                                                    </div>
                                                    {/*info*/}
                                                    <div className="flex flex-col w-full h-full pl-2 items-start">
                                                        <span
                                                            className="text-md text-gray-700">{item.product_name} ({item.size}) {item.count_topping !== 0 &&
                                                            <span
                                                                className="text-xs text-gray-500">- {item.count_topping} toppings</span>}</span>
                                                        <span className="text-xs">Quantity: {item.quantity}</span>
                                                        {item.note !== '' && <span
                                                            className="text-xs text-gray-500">Note: {item.note}</span>}
                                                    </div>
                                                    <div
                                                        className="font-semibold text-sm">{formatVietnameseCurrency(item.total_price)}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="w-full flex justify-end items-center">
                                <p className="text-gray-500">Total price ({order.count_product} products): <span
                                    className="font-semibold">{formatVietnameseCurrency(order.total_price)}</span></p>
                            </div>
                        </div>
                    ))
                ) : (
                    <span>Nothing to show</span>
                )}
            </div>
        </div>
    );
};

export default OrderList;
