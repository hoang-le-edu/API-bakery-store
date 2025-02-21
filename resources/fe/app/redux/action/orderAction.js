import connectApi from "../../../settings/ConnectApi.js";
import {GET_ORDERS_PROCESS, GET_ORDERS_SUCCESS} from "../constant/orderType.js";

export const getAllOrders = (limit, form) => async (dispatch) => {
    dispatch({ type: GET_ORDERS_PROCESS });

    const { data } = await connectApi.get(`/api/loadCustomerOrders`, {params: form});
    console.log(data);

    dispatch({ type: GET_ORDERS_SUCCESS, payload: data });
}
