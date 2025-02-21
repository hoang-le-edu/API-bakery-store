import {createContext, useContext, useEffect, useState} from "react";
import {auth} from "../../../modules/firebase/firebase.js"
import {onAuthStateChanged, signInWithCustomToken} from "firebase/auth"

const AuthContext = createContext();

export function useAuth() {
    return useContext(AuthContext);
}

export const AuthProvider = ({children}) => {
    const [currentUser, setCurrentUser] = useState(null);
    const [userLoggedIn, setUserLoggedIn] = useState(false);
    const [loading, setLoading] = useState(true);
    const [isEmailUser, setIsEmailUser] = useState(false);
    const [isGoogleUser, setIsGoogleUser] = useState(false);
    const [isPremiumUser, setIsPremiumUser] = useState(false);

    useEffect(() => {
        const unsubscribe = onAuthStateChanged(auth, initializeUser);
        return unsubscribe;
    }, []);

    async function initializeUser(user) {
        if (user) {
            setCurrentUser({...user});

            // check if provider is email and password login
            const isEmail = user.providerData.some(
                (provider) => provider.providerId === "password"
            );
            setIsEmailUser(isEmail);

            // check if the auth provider is google or not
            //   const isGoogle = user.providerData.some(
            //     (provider) => provider.providerId === GoogleAuthProvider.PROVIDER_ID
            //   );
            //   setIsGoogleUser(isGoogle);

            setUserLoggedIn(true);

            // check if user is premium user
            // signInWithCustomToken(auth, customToken)
            //     .then((userCredential) => {
            //         // Signed in successfully.
            //         const user = userCredential.user;
            //
            //         // Now get the ID token result
            //         user.getIdTokenResult()
            //             .then((idTokenResult) => {
            //                 console.log("Claims:", idTokenResult.claims);
            //                 const premiumAccount = idTokenResult.claims.premiumAccount;
            //                 console.log("premiumAccount claim:", premiumAccount);
            //             })
            //             .catch((error) => {
            //                 console.error("Error getting token result:", error);
            //             });
            //     })
            //     .catch((error) => {
            //         console.error("Error signing in with custom token:", error);
            //     });
            // setIsPremiumUser(!!token.claims.premiumAccount);
        } else {
            setCurrentUser(null);
            setUserLoggedIn(false);
        }

        setLoading(false);
    }

    const value = {
        currentUser,
        userLoggedIn,
        loading,
        isEmailUser,
        isGoogleUser,
        isPremiumUser
    }

    return (
        <AuthContext.Provider value={value}>
            {!loading && children}
        </AuthContext.Provider>
    );
}
