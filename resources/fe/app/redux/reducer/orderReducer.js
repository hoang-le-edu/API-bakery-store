const orders = {
    orders: [],
    loading: false
}

export const orderReducer = (state = orders, action) => {
    switch (action.type) {
        case 'GET_ORDERS_PROCESS':
            return { ...state, loading: true };

        case 'GET_ORDERS_SUCCESS':
            return {
                ...state,
                orders: action.payload.data,
                loading: false,
            };

        default:
            return state;
    }
}
