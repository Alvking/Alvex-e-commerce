
"use strict";

/* =========================================
   VOLTIX - MAIN JAVASCRIPT
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================
       1. INITIALIZE LOCAL STORAGE
    ===================================== */

    let cart = JSON.parse(localStorage.getItem("voltixCart")) || [];
    let wishlist = JSON.parse(localStorage.getItem("voltixWishlist")) || [];

    function localizedText(english, swahili, french) {
        const language = document.documentElement.lang;
        if (language === "sw") return swahili;
        if (language === "fr") return french;
        return english;
    }


    /* =====================================
       2. UPDATE SHOPPING CART COUNT
    ===================================== */

    function updateCartCount() {

        const cartCount = document.querySelector(".cart-count");

        if (!cartCount) return;

        const totalItems = cart.reduce(function (total, item) {
            return total + item.quantity;
        }, 0);

        cartCount.textContent = totalItems;

        // Hide badge when cart is empty
        cartCount.style.display = totalItems > 0 ? "flex" : "none";
    }


    /* =====================================
       3. SAVE CART TO LOCAL STORAGE
    ===================================== */

    function saveCart() {

        localStorage.setItem("voltixCart", JSON.stringify(cart));

        updateCartCount();
    }


    /* =====================================
       4. ADD PRODUCTS TO CART
    ===================================== */

    const addCartButtons = document.querySelectorAll(".add-cart-btn");

    addCartButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const productCard = button.closest(".product-card");

            const productId = button.dataset.productId;

            const productName = productCard.querySelector("h6").textContent;

            const priceElement = productCard.querySelector(".product-price");
            const productPrice = priceElement.dataset.basePrice
                ? Number(priceElement.dataset.basePrice)
                : Number(
                    priceElement.textContent
                        .match(/(?:USD|KSh|KES|\$)\s*([\d,]+(?:\.\d{1,2})?)/i)?.[1]
                        ?.replace(/,/g, "")
                );

            const productImage = productCard.querySelector("img").src;

            // Check whether product already exists
            const existingProduct = cart.find(function (item) {
                return item.id === productId;
            });

            if (existingProduct) {

                existingProduct.quantity++;

            } else {

                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: 1
                });

            }

            saveCart();

            showNotification(productName + localizedText(
                " added to cart!",
                " imeongezwa kwenye kikapu!",
                " ajouté au panier !"
            ));

            // Temporary button feedback
            const originalText = button.textContent;

            button.textContent = localizedText("Added ✓", "Imeongezwa ✓", "Ajouté ✓");
            button.disabled = true;

            setTimeout(function () {

                button.textContent = originalText;
                button.disabled = false;

            }, 1200);

        });

    });


    /* =====================================
       5. WISHLIST FUNCTIONALITY
    ===================================== */

    const wishlistButtons = document.querySelectorAll(".wishlist-btn");

    wishlistButtons.forEach(function (button) {

        const productCard = button.closest(".product-card");

        const productId = productCard.querySelector(".add-cart-btn").dataset.productId;

        const heartIcon = button.querySelector("i");

        // Restore previously saved wishlist state
        if (wishlist.includes(productId)) {

            heartIcon.classList.remove("bi-heart");
            heartIcon.classList.add("bi-heart-fill");

            button.classList.add("active");

        }

        button.addEventListener("click", function () {

            if (wishlist.includes(productId)) {

                // Remove product from wishlist
                wishlist = wishlist.filter(function (id) {
                    return id !== productId;
                });

                heartIcon.classList.remove("bi-heart-fill");
                heartIcon.classList.add("bi-heart");

                button.classList.remove("active");

                showNotification(localizedText(
                    "Removed from your wishlist",
                    "Imeondolewa kwenye orodha yako ya matamanio",
                    "Retiré de votre liste de souhaits"
                ));

            } else {

                // Add product to wishlist
                wishlist.push(productId);

                heartIcon.classList.remove("bi-heart");
                heartIcon.classList.add("bi-heart-fill");

                button.classList.add("active");

                showNotification(localizedText(
                    "Added to your wishlist!",
                    "Imeongezwa kwenye orodha yako ya matamanio!",
                    "Ajouté à votre liste de souhaits !"
                ));

            }

            localStorage.setItem(
                "voltixWishlist",
                JSON.stringify(wishlist)
            );

        });

    });


    /* =====================================
       6. PRODUCT SEARCH FUNCTIONALITY
    ===================================== */

    const searchForm = document.querySelector(".search-form");

    const searchInput = searchForm?.querySelector("input");

    const productCards = document.querySelectorAll(".product-card");

    if (searchForm && searchInput) {

        // Create a message for empty search results
        const noResults = document.createElement("p");

        noResults.className = "no-results text-center w-100 mt-4";

        noResults.textContent = localizedText(
            "No products found. Try another search.",
            "Hakuna bidhaa zilizopatikana. Jaribu kutafuta tena.",
            "Aucun produit trouvé. Essayez une autre recherche."
        );

        noResults.style.display = "none";

        const productContainer = document.querySelector(".trending-section .row");

        productContainer?.appendChild(noResults);

        searchForm.addEventListener("submit", function (event) {

            event.preventDefault();

            const searchTerm = searchInput.value.trim().toLowerCase();

            if (!searchTerm) {

                productCards.forEach(function (card) {
                    card.closest(".col-6").style.display = "";
                });

                noResults.style.display = "none";

                return;
            }

            let matchingProducts = 0;

            productCards.forEach(function (card) {

                const productName = card.querySelector("h6")
                    .textContent.toLowerCase();

                const matches = productName.includes(searchTerm);

                card.closest(".col-6").style.display = matches ? "" : "none";

                if (matches) {
                    matchingProducts++;
                }

            });

            noResults.style.display =
                matchingProducts === 0 ? "block" : "none";

            // Bring results into view
            document.querySelector(".trending-section")
                .scrollIntoView({
                    behavior: "smooth"
                });

        });

        // Restore all products when search is cleared
        searchInput.addEventListener("input", function () {

            if (searchInput.value.trim() === "") {

                productCards.forEach(function (card) {
                    card.closest(".col-6").style.display = "";
                });

                noResults.style.display = "none";

            }

        });

    }


    /* =====================================
       7. NEWSLETTER SUBSCRIPTION
    ===================================== */

    const newsletterForm = document.querySelector(".newsletter-form");

    if (newsletterForm) {

        newsletterForm.addEventListener("submit", function (event) {

            event.preventDefault();

            const emailInput = newsletterForm.querySelector("input");

            const email = emailInput.value.trim();

            // Validate email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email)) {

                showNotification(localizedText(
                    "Please enter a valid email address.",
                    "Tafadhali weka anwani sahihi ya barua pepe.",
                    "Veuillez saisir une adresse e-mail valide."
                ));

                return;
            }

            // Prevent duplicate subscriptions in this browser
            let subscribers = JSON.parse(
                localStorage.getItem("voltixSubscribers")
            ) || [];

            if (subscribers.includes(email.toLowerCase())) {

                showNotification(localizedText(
                    "You are already subscribed!",
                    "Tayari umejisajili!",
                    "Vous êtes déjà abonné !"
                ));

                return;
            }

            subscribers.push(email.toLowerCase());

            localStorage.setItem(
                "voltixSubscribers",
                JSON.stringify(subscribers)
            );

            showNotification(localizedText(
                "Thank you for joining Voltix!",
                "Asante kwa kujiunga na Voltix!",
                "Merci d'avoir rejoint Voltix !"
            ));

            newsletterForm.reset();

        });

    }


    /* =====================================
       8. NOTIFICATION SYSTEM
    ===================================== */

    function showNotification(message) {

        // Create notification element
        const notification = document.createElement("div");

        notification.className = "voltix-notification";

        notification.innerHTML = `
            <i class="bi bi-check-circle-fill"></i>
            <span></span>
        `;

        notification.querySelector("span").textContent = message;

        document.body.appendChild(notification);

        // Show notification
        requestAnimationFrame(function () {
            notification.classList.add("show");
        });

        // Remove notification after 3 seconds
        setTimeout(function () {

            notification.classList.remove("show");

            setTimeout(function () {
                notification.remove();
            }, 300);

        }, 3000);

    }


    /* =====================================
       9. INITIAL PAGE LOAD
    ===================================== */

    updateCartCount();

});
