document.addEventListener('DOMContentLoaded', function() {
    // Get elements and initialize variables
    const priceElement = document.getElementById('price-summary');
    const basePrice = parseFloat(priceElement.dataset.basePrice);
    
    const toppingsCheckboxes = document.querySelectorAll('input[name="toppings[]"]');
    const toppingsPriceDisplay = document.getElementById('toppings-price');
    const totalPriceDisplay = document.getElementById('total-price');
    const drinkPreviewImage = document.getElementById('drink-preview-image');
    const drinkPreviewContainer = document.getElementById('drink-preview-container');
    const draggableToppings = document.querySelectorAll('.draggable-topping');
    const customizationForm = document.getElementById('customization-form');
    
    // Store selected toppings
    let selectedToppings = [];
    
    // Build topping prices object from data attributes
    const toppingPrices = {};
    document.querySelectorAll('#topping-prices-data span').forEach(el => {
        toppingPrices[el.dataset.topping] = parseFloat(el.dataset.price);
    });
    
    // Initialize price display
    updatePriceDisplay();
    
    // Functions
    function updatePrice() {
        let toppingsTotal = 0;
        
        toppingsCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                toppingsTotal += toppingPrices[checkbox.value];
            }
        });
        
        const totalPrice = basePrice + toppingsTotal;
        
        toppingsPriceDisplay.textContent = '$' + toppingsTotal.toFixed(2);
        totalPriceDisplay.textContent = '$' + totalPrice.toFixed(2);
    }
    
    function updatePriceDisplay() {
        const toppingsPrice = selectedToppings.reduce((sum, topping) => sum + topping.price, 0);
        const totalPrice = basePrice + toppingsPrice;
        
        document.getElementById('toppings-price').textContent = `$${toppingsPrice.toFixed(2)}`;
        document.getElementById('total-price').textContent = `$${totalPrice.toFixed(2)}`;
    }
    
    function toggleTopping(toppingElement, forceAdd = false) {
        const toppingName = toppingElement.getAttribute('data-topping-name');
        const toppingPrice = parseFloat(toppingElement.getAttribute('data-topping-price'));
        const toppingImage = toppingElement.getAttribute('data-topping-image');
        
        // Check if topping is already selected
        const existingIndex = selectedToppings.findIndex(t => t.name === toppingName);
        
        // Find the corresponding checkbox - now using the sanitized ID with underscores
        const toppingSanitizedId = toppingName.replace(/\s+/g, '_');
        const checkboxId = `topping_${toppingSanitizedId}`;
        const checkbox = document.getElementById(checkboxId);
        
        if (existingIndex !== -1 && !forceAdd) {
            // Topping is already selected - remove it
            selectedToppings.splice(existingIndex, 1);
            toppingElement.classList.remove('selected');
            
            // Remove from visual display
            const toppingDisplay = document.querySelector(`.selected-topping-item[data-name="${toppingName}"]`);
            if (toppingDisplay) {
                toppingDisplay.remove();
            } 
            
            // Uncheck the checkbox
            if (checkbox) {
                checkbox.checked = false;
            }
        } else if (existingIndex === -1) {
            // Add new topping
            selectedToppings.push({
                name: toppingName,
                price: toppingPrice,
                image: toppingImage
            });
            toppingElement.classList.add('selected');
            
            // Add visual representation to the header
            addToppingVisual(toppingName, toppingImage);
            
            // Check the checkbox
            if (checkbox) {
                checkbox.checked = true;
            } 
        }
        
        // Update price displays
        updatePriceDisplay();
        updatePrice();
    }
    
    function addToppingVisual(toppingName, toppingImage) {
        // Create visual element
        const visual = document.createElement('div');
        visual.className = 'selected-topping-item clickable';
        visual.dataset.name = toppingName;
        visual.title = "Click to remove";
        
        // Add topping icon
        const toppingIcon = document.createElement('img');
        toppingIcon.src = `images/toppings/${toppingImage}`; 
        toppingIcon.alt = toppingName;
        toppingIcon.onerror = function() {
            this.src = 'images/toppings/default.png';
        };
        toppingIcon.className = 'topping-icon';
        toppingIcon.style.width = '30px';
        toppingIcon.style.height = '30px';
        
        // Add click handler
        visual.addEventListener('click', function() {
            const toppingElement = document.querySelector(`.draggable-topping[data-topping-name="${toppingName}"]`);
            if (toppingElement) toggleTopping(toppingElement);
        });
        
        visual.appendChild(toppingIcon);
        document.getElementById('selected-toppings-display').appendChild(visual);
    }
    
    // Setup event handlers
    toppingsCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updatePrice);
    });
    
    // Make toppings draggable
    draggableToppings.forEach(topping => {
        topping.addEventListener('dragstart', function(e) {
            document.body.classList.add('dragging');
            this.classList.add('dragging');
            
            e.dataTransfer.setData('text/plain', JSON.stringify({
                name: this.getAttribute('data-topping-name'),
                price: this.getAttribute('data-topping-price')
            }));
            
            e.dataTransfer.effectAllowed = 'copy';
        });
        
        topping.addEventListener('dragend', function() {
            document.body.classList.remove('dragging');
            this.classList.remove('dragging');
        });
        
        // Click to select/deselect topping
        topping.addEventListener('click', function() {
            toggleTopping(this);
        });
    });
    
    // Handle drop zone
    function setupDropZone(element) {
        element.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            drinkPreviewContainer.classList.add('drag-over');
        });
        
        element.addEventListener('dragleave', function(e) {
            e.preventDefault();
            drinkPreviewContainer.classList.remove('drag-over');
        });
        
        element.addEventListener('drop', function(e) {
            e.preventDefault();
            drinkPreviewContainer.classList.remove('drag-over');
            
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            const toppingElement = document.querySelector(`.draggable-topping[data-topping-name="${data.name}"]`);
            
            if (toppingElement) toggleTopping(toppingElement, true);
        });
    }
    
    // Set up drop zones
    setupDropZone(drinkPreviewContainer);
    setupDropZone(drinkPreviewImage);
    
    // Handle form submission
    customizationForm.addEventListener('submit', function() {
        // Make sure checkboxes match the visual selection
        selectedToppings.forEach(topping => {
            const toppingSanitizedId = topping.name.replace(/\s+/g, '_');
            const checkbox = document.getElementById(`topping_${toppingSanitizedId}`);
            if (checkbox) checkbox.checked = true;
        });
    });
    
    // Make updatePrice available globally
    window.updatePrice = updatePrice;
});
