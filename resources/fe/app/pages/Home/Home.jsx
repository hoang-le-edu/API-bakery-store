// import React from "react";
// import Banner from "../../components/homepage/Banner";
// import BestProducts from "../../components/homepage/BestProducts";
// import Categories from "../../components/homepage/Categories";
//
// const Home = () => {
//   return (
//     <section>
//       <Banner />
//       <Categories />
//       <BestProducts />
//     </section>
//   );
// };
//
// export default Home;

import React from "react";
import Banner from "../../components/homepage/Banner";
import BestProducts from "../../components/homepage/BestProducts";
import Categories from "../../components/homepage/Categories";

const Home = () => {
    return (
        <section className="bg-gray-100">
            {/* 1. Banner */}
            <Banner />

            {/* 2. Categories Section */}
            <div className="container mx-auto px-4 py-10">
                <Categories />
            </div>

            {/* 3. Best Products Section */}
            <div className="container mx-auto px-4 py-10">
                <BestProducts />
            </div>

            {/* 4. CTA Section */}
            <div className="py-10 bg-tertiary text-white text-center">
                <h2 className="text-3xl font-bold mb-4">Get the best deals now!</h2>
                <p className="mb-6">Sign up and get exclusive discounts on top products.</p>
                <button className="bg-white text-tertiary px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-gray-200 transition">
                    Sign Up Now
                </button>
            </div>
        </section>
    );
};

export default Home;
