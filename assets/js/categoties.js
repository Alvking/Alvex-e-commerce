
document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("categorySearch");
    const categoryItems = document.querySelectorAll(".category-item");

    if (!searchInput) return;

    searchInput.addEventListener("input", () => {

        const searchValue = searchInput.value.toLowerCase().trim();

        categoryItems.forEach(category => {

            const categoryName = category
                .querySelector("h5")
                .textContent
                .toLowerCase();

            category.style.display = categoryName.includes(searchValue)
                ? ""
                : "none";

        });

    });

});
