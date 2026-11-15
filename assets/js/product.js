
document.addEventListener("DOMContentLoaded", () => {

    const addToCartButton = document.getElementById("addToCart");
    const quantityInput = document.getElementById("quantity");

    if (!addToCartButton || !quantityInput) return;

    addToCartButton.addEventListener("click", () => {

        const productId = Number(addToCartButton.dataset.id);
        const quantity = Number(quantityInput.value);

        if (!Number.isInteger(quantity) || quantity < 1 || quantity > 99) {
            alert("Please enter a valid quantity.");
            return;
        }

        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        const existingProduct = cart.find(
            item => item.id === productId
        );

        if (existingProduct) {
            existingProduct.quantity += quantity;
        } else {
            cart.push({
                id: productId,
                quantity: quantity
            });
        }

        localStorage.setItem("cart", JSON.stringify(cart));

        alert("Product added to cart successfully!");

    });

});
