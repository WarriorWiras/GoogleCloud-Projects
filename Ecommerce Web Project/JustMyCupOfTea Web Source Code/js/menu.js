const categories = document.querySelectorAll('.category-button');
const menuItems = document.querySelectorAll('.menu-item');
const addToCartButtons = document.querySelectorAll('.add-to-cart'); // Select the buttons

categories.forEach(category => {
    category.addEventListener('click', () => {
        const selectedCategory = category.dataset.category;

        // Update active category button
        categories.forEach(btn => btn.classList.remove('active'));
        category.classList.add('active');

        // Filter and show menu items
        menuItems.forEach(item => {
            if (selectedCategory === 'all' || item.dataset.category === selectedCategory) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Initially show only 'seasonal special' items
menuItems.forEach(item => {
    if (item.dataset.category !== 'seasonal-special') {
        item.style.display = 'none';
    }
});

// Add to Cart Functionality
addToCartButtons.forEach(button => {
    button.addEventListener('click', () => {
        const drinkId = button.dataset.id;
        const drinkName = button.dataset.name;
        const drinkPrice = button.dataset.price;
        const drinkImage = button.dataset.image;
        
        // Redirect to the customize drink page with the drink details
        window.location.href = `customize_drink.php?id=${drinkId}&name=${encodeURIComponent(drinkName)}&price=${drinkPrice}&image=${encodeURIComponent(drinkImage)}`;
    });
});