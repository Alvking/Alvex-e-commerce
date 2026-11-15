
document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("dealSearch");
    const dealItems = document.querySelectorAll(".deal-item");

    if (!searchInput) return;

    searchInput.addEventListener("input", () => {

        const searchValue = searchInput.value.toLowerCase().trim();

        dealItems.forEach(item => {

            const productName = item
                .querySelector(".card-title")
                .textContent
                .toLowerCase();

            const productDescription = item
                .querySelector(".card-body p")
                .textContent
                .toLowerCase();

            item.style.display =
                productName.includes(searchValue) ||
                productDescription.includes(searchValue)
                    ? ""
                    : "none";

        });

    });

});
