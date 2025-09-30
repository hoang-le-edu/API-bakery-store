import 'dotenv/config';
import admin from "firebase-admin";
import axios from "axios";
// import {doSignInWithCustomToken} from "./auth.js";

// Load service account from env variables instead of JSON file
const serviceAccountFromEnv = {
    type: process.env.FIREBASE_TYPE || "service_account",
    project_id: process.env.FIREBASE_PROJECT_ID,
    private_key_id: process.env.FIREBASE_PRIVATE_KEY_ID,
    private_key: (process.env.FIREBASE_PRIVATE_KEY || "").replace(/\\n/g, "\n"),
    client_email: process.env.FIREBASE_CLIENT_EMAIL,
    client_id: process.env.FIREBASE_CLIENT_ID,
    auth_uri: process.env.FIREBASE_AUTH_URI || "https://accounts.google.com/o/oauth2/auth",
    token_uri: process.env.FIREBASE_TOKEN_URI || "https://oauth2.googleapis.com/token",
    auth_provider_x509_cert_url: process.env.FIREBASE_AUTH_PROVIDER_X509_CERT_URL || "https://www.googleapis.com/oauth2/v1/certs",
    client_x509_cert_url: process.env.FIREBASE_CLIENT_X509_CERT_URL,
    universe_domain: process.env.FIREBASE_UNIVERSE_DOMAIN || "googleapis.com",
};

if (admin.apps.length === 0) {
    admin.initializeApp({
        credential: admin.credential.cert(serviceAccountFromEnv)
    });
    console.log("Firebase has been initialized.");
} else {
    console.log("Firebase was already initialized.");
}

const email = process.env.ADMIN_EMAIL;
const password = process.env.ADMIN_PASSWORD;
const phone_number = process.env.ADMIN_PHONE_NUMBER;
const additionalClaims = {
    premiumAccount: true
};


// Fetch the user by email
admin.auth().getUserByEmail(email).then(async (userRecord) => {
    const firebase_uid = userRecord.uid;

    // Create a custom token with the additional claims
    admin.auth().createCustomToken(firebase_uid, additionalClaims).then(async (custom_token) => {
        try {
            const baseUrl = process.env.VITE_API_BASE_URL || "http://127.0.0.1:8080";
            const response = await axios.post(`${baseUrl}/api/auth/addAdmin`, {
                name: userRecord.email,
                email: userRecord.email,
                password: password,
                phone_number: phone_number,
                c_password: password,
                firebase_uid: firebase_uid,
                // custom_token: custom_token,
            });

            // await doSignInWithCustomToken(custom_token);
            await admin.auth().setCustomUserClaims(firebase_uid, additionalClaims);

            console.log('response', response.data.message);
        } catch (error) {
            console.log('Error creating custom token:', error);
        }
    }).catch((error) => {
        console.log('Error creating custom token:', error)
    })
}).catch((error) => {
    console.log('Error fetching user data:', error)
})
