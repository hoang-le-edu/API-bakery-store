export const mapProducts = (data) => {
    return data.map((category) => ({
        id: category.category_id,
        name: category.category_name,
        product_list: category.product_list.map((product) => ({
            id: product.product_id,
            name: product.product_name,
            description: product.product_description,
            image_path: product.product_image,
            price: product.product_price,
        })),
    }));
};
