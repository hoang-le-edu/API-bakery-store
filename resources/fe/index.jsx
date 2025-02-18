// import React from "react";
// import ReactDOM from "react-dom/client";
// import App from "./App.jsx";
//
// const root = ReactDOM.createRoot(document.getElementById("root"));
// root.render(<App />);

import "./index.css";
import MainRoutes from "./app/routes/MainRoutes.jsx";
import React from "react";
import {store} from "./app/redux/store";
import {Provider} from "react-redux";
import ReactDOM from "react-dom/client";
import {AuthProvider} from "./app/hooks/contexts/authContext/index.jsx";
import {PopupProvider} from "./app/hooks/contexts/popupContext/popupState.jsx";

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(
    <Provider store={store}>
        <AuthProvider>
            <PopupProvider>
                <MainRoutes/>
            </PopupProvider>
        </AuthProvider>
    </Provider>
);
