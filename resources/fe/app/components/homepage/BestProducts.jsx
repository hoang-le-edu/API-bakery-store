/* eslint-disable react/style-prop-object */
import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import ButtonElement from "../element/ButtonElement.jsx";
import { getAllProducts } from "../../redux/action/productAction";
import ProductsCardElement from "../element/ProductsCardElement.jsx";
import ProductsCardLoading from "../loading/ProductsCardLoading";
import { animateScroll as scroll } from 'react-scroll';


const BestProducts = () => {
  const dispatch = useDispatch();
  const [limit, setLimit] = useState(4);
  const {products, total, loading} = useSelector(state => state.products);

  const loadMoreProducts = () => {
    setLimit(limit + 4);
    scroll.scrollMore(350, {
      duration: 300,
      smooth: "easeInOutQuint",
    });
  };

  useEffect(() => {
      const fetchProducts = async () => {
          await dispatch(getAllProducts(limit));
      };
      fetchProducts();
  }, [dispatch, limit]);

    const buttonClassName = "btn w-full md:w-auto min-w-[200px] bg-tertiary text-white font-semibold py-3 px-6 rounded-lg hover:opacity-80 transition-all duration-300";


    return (
    <section className="py-12">
      <div className="container mx-auto px-4">
        {/* section title */}
        <div className="flex justify-between items-center mb-6">
          {/* title */}
          <div className="flex flex-row items-center gap-x-2">
            <div className="h-10 w-4 rounded-md bg-tertiary"></div>
            <h1 className="text-2xl font-medium">Best Products</h1>
          </div>
        </div>

        {/* section details */}

        {products.length === 0 ? (
          // animation while fetching product
          <ProductsCardLoading />
        ) : (
          <div>
            {/* showing product card */}
              {!loading &&  <ProductsCardElement products={products}/>}

            {/* animation while fetching product */}
            {loading && <ProductsCardLoading />}

             {/*product button loading*/}
            <div className="flex justify-center items-center">
              {limit === total ? (
                <></>
              ) : (
                <ButtonElement
                  style={buttonClassName}
                  loading={loading}
                  action={loadMoreProducts}
                  title="More Products"
                />
              )}
            </div>
          </div>
        )}
      </div>
    </section>
  );
};

export default BestProducts;
