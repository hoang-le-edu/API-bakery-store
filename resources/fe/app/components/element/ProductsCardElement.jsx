/* eslint-disable react/style-prop-object */
import { toast } from "react-toastify";
import { BsEye } from "react-icons/bs";
import React, {useEffect, useState} from "react";
import ButtonElement from "./ButtonElement.jsx";
import { MdFavorite } from "react-icons/md";
import { useNavigate } from "react-router-dom";
import ReviewScoreElement from "./ReviewScoreElement.jsx";
import { useDispatch, useSelector } from "react-redux";
import { addToCart, resetStatus } from "../../redux/action/cartAction.js";

const ProductsCardElement = ({ products }) => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { cart, loading, success, fail } = useSelector((state) => state.cart);

  const handleDetails = (id) => {
    navigate(`/product/${id}`);
  };

  const handleCart = (e) => {
    const id = e.target.value;
    dispatch(addToCart(id, 1));
  };

  // useEffect(() => {
  //   if (success) {
  //     toast.info("Berhasil menambahkan Product ke Cart");
  //     dispatch(resetStatus());
  //   } else if (fail) {
  //     toast.error("Total Amount melebihi Stock Product");
  //     dispatch(resetStatus());
  //   }
  // }, [dispatch, cart, success, fail]);

  const buttonStyle =
    "btn w-full h-[50px]  text-white text-xs md:text-[16px] bg-secondary absolute cursor-pointer group-hover:bottom-0 -bottom-14 ";

  return (
    <div className="flex flex-wrap w-full mb-[50px] ">
      {products.map((category, index) => (
        category.product_list.map((product, subIndex) => {
          const { product_id, product_name, product_description, product_price,product_image } = product;
          return (
            <div className="md:w-1/4 w-1/2 px-2 py-2" key={`${index}-${subIndex}`}>
              {/* image product */}
              <div className="bg-gray-200/50 rounded-md relative mb-4 max-h-[250px] group overflow-hidden">
                <img
                  className="object-center scale-[0.7] group-hover:opacity-60 transition-all durationn-300"
                  src={product_image}
                  alt={name}
                />
                <div className="absolute flex flex-col gap-y-2 mt-2 mr-2 items-center justify-center top-0 group-hover:right-0 -right-14 transition-all ease-in-out duration-300">
                  <BsEye
                    className="text-2xl cursor-pointer"
                    onClick={() => handleDetails(product_id)}
                  />
                  <MdFavorite />
                </div>
                <ButtonElement
                  value={product_id}
                  action={handleCart}
                  style={buttonStyle}
                  title="Add to Cart"
                  loading={loading}
                />
              </div>
              {/* details product */}
              <div className="flex flex-col gap-2">
                <h4>{product_name}</h4>
                <div className="flex flex-row items-center gap-x-4">
                  <p className="text-tertiary">
                    ${product_price}
                  </p>
                </div>
              </div>
            </div>
          );
        })
      ))}
    </div>
  );
};
export default ProductsCardElement;
