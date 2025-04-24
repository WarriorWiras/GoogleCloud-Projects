// Confirmation for the Clear Cart button
document.querySelectorAll('button[name="clear_cart"]').forEach(button => {
    button.addEventListener('click', function(event) {
        if (!confirm('Are you sure you want to clear your cart?')) {
            event.preventDefault();
        }
    });
});

// Add a global variable to track the current cart total
let currentCartTotal = parseFloat(document.querySelector('.cart-total')?.textContent.replace('$', '') || 0);

// Handle quantity adjustments with AJAX
document.querySelectorAll('button[name="adjust_quantity"]').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent form submission
        
        const form = this.form;
        const action = form.querySelector('input[name="action"]').value;
        const quantityDisplay = this.closest('.quantity-controls').querySelector('.quantity-display');
        const currentQuantity = parseInt(quantityDisplay.textContent);
        
        // Confirm before removing item
        if (action === 'decrease' && currentQuantity === 1) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
        }
        
        // Get form data
        const formData = new FormData(form);
        
        // Explicitly add the button's name and value (which isn't included by default in FormData)
        formData.append('adjust_quantity', 'true');
        
        // Use fetch API to update cart without page refresh
        fetch('cart-update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update the global cart total
                currentCartTotal = data.total;

                if (data.removed) {
                    // Handle item removal without page refresh
                    const itemRow = this.closest('tr');
                    const detailsRow = itemRow.nextElementSibling;
                    
                    // Remove both rows from the DOM
                    if (itemRow && detailsRow) {
                        itemRow.remove();
                        detailsRow.remove();
                    }
                    
                    // Update the indexes for all remaining items
                    updateRemainingItemIndexes();
                    
                    // Update total
                    const totalElement = document.querySelector('.cart-total');
                    if (totalElement) {
                        totalElement.textContent = `$${data.total.toFixed(2)}`;
                    }
                    
                    // Update final total if points are being redeemed
                    updateFinalTotal(data.total);
                    
                    // If cart is now empty, show empty cart message
                    if (data.cartEmpty || data.cartCount === 0 || document.querySelectorAll('.table tbody tr').length === 0) {
                        showEmptyCartMessage();
                    }
                } else {
                    // Update quantity display
                    quantityDisplay.textContent = data.quantity;
                    
                    // Find and update the subtotal
                    const detailsRow = this.closest('tr').nextElementSibling;
                    const subtotalElement = detailsRow.querySelector('.item-subtotal');
                    if (subtotalElement) {
                        subtotalElement.innerHTML = `<strong>Subtotal:</strong> $${data.itemTotal.toFixed(2)}`;
                    }
                    
                    // Update the total amount
                    const totalElement = document.querySelector('.cart-total');
                    if (totalElement) {
                        totalElement.textContent = `$${data.total.toFixed(2)}`;
                    }
                    
                    // Update final total if points are being redeemed
                    updateFinalTotal(data.total);
                }
            } else {
                // Show error message
                console.error('Error updating cart:', data.message);
                alert('Error updating cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the cart. Please try again.');
        });
    });
});

// Handle points redemption checkbox
document.addEventListener('DOMContentLoaded', function() {
    const redeemPointsCheckbox = document.getElementById('redeem-points');
    if (redeemPointsCheckbox) {
        redeemPointsCheckbox.addEventListener('change', function() {
            const pointsDiscountRow = document.getElementById('points-discount-row');
            const finalTotalRow = document.getElementById('final-total-row');
            const pointsDiscountElement = document.querySelector('.points-discount');
            const finalTotalElement = document.querySelector('.final-total');
            const pointsRedeemedInput = document.getElementById('points-redeemed-input');
            
            if (this.checked) {
                // Show the discount and final total rows
                pointsDiscountRow.style.display = '';
                finalTotalRow.style.display = '';
                
                // Calculate maximum points that can be redeemed (100 points = $1)
                const maxPointsValue = currentCartTotal;
                const maxPoints = Math.floor(maxPointsValue * 100); // Convert dollars to points
                
                // Use the smaller of available points or maximum possible points
                const pointsToRedeem = Math.min(availablePoints, maxPoints);
                const actualPointsValue = pointsToRedeem * 0.01; // Convert back to dollars
                
                // Format the points discount
                const formattedDiscount = `-$${actualPointsValue.toFixed(2)}`;
                pointsDiscountElement.textContent = formattedDiscount;
                
                // Calculate and display the final total using the current cart total
                const finalTotal = Math.max(0, currentCartTotal - actualPointsValue).toFixed(2);
                finalTotalElement.textContent = `$${finalTotal}`;
                
                // Set the points being redeemed in the hidden input
                pointsRedeemedInput.value = pointsToRedeem;
            } else {
                // Hide the discount and final total rows
                pointsDiscountRow.style.display = 'none';
                finalTotalRow.style.display = 'none';
                
                // Reset the points being redeemed
                pointsRedeemedInput.value = 0;
            }
        });
    }
    
    // Initialize the current cart total on page load
    const totalElement = document.querySelector('.cart-total');
    if (totalElement) {
        currentCartTotal = parseFloat(totalElement.textContent.replace('$', '') || 0);
    }
});

// Function to update all remaining item indexes after an item is removed
function updateRemainingItemIndexes() {
    // Get all item rows
    const itemRows = document.querySelectorAll('.item-row');
    
    // Update the item_index in each form
    itemRows.forEach((row, newIndex) => {
        // Update the hidden input fields in all forms in this row
        const forms = row.querySelectorAll('form');
        forms.forEach(form => {
            const indexInput = form.querySelector('input[name="item_index"]');
            if (indexInput) {
                indexInput.value = newIndex;
            }
        });
        
        // Update data-item-index attribute if present
        if (row.hasAttribute('data-item-index')) {
            row.setAttribute('data-item-index', newIndex);
        }
    });
}

// Function to show empty cart message when the last item is removed
function showEmptyCartMessage() {
    const cartContent = document.querySelector('.cart-content');
    const container = document.querySelector('main.container');
    
    if (cartContent && container) {
        // Create empty cart message
        const emptyCartHTML = `
            <section class="empty-cart text-center py-5 bg-light rounded">
                <h2 class="mt-3">Your cart is empty</h2>
                <p class="text-muted mb-4">Looks like you haven't added any bubble tea to your cart yet.</p>
                <a href="menu.php" class="btn btn-custom-purple">Start Shopping</a>
            </section>
        `;
        
        // Replace cart content with empty message
        cartContent.outerHTML = emptyCartHTML;
    }
}

// Function to update the final total when cart items change
function updateFinalTotal(newTotal) {
    // Update the global cart total
    currentCartTotal = newTotal;
    
    const redeemPointsCheckbox = document.getElementById('redeem-points');
    const finalTotalElement = document.querySelector('.final-total');
    
    if (redeemPointsCheckbox && redeemPointsCheckbox.checked && finalTotalElement) {
        // Calculate maximum points that can be redeemed
        const maxPointsValue = newTotal;
        const maxPoints = Math.floor(maxPointsValue * 100);
        
        // Use the smaller of available points or maximum possible points
        const pointsToRedeem = Math.min(availablePoints, maxPoints);
        const actualPointsValue = pointsToRedeem * 0.01;
        
        // Update the points discount display
        const pointsDiscountElement = document.querySelector('.points-discount');
        if (pointsDiscountElement) {
            pointsDiscountElement.textContent = `-$${actualPointsValue.toFixed(2)}`;
        }
        
        // Recalculate final total with the adjusted discount
        const finalTotal = Math.max(0, newTotal - actualPointsValue).toFixed(2);
        finalTotalElement.textContent = `$${finalTotal}`;
        
        // Update the hidden input value
        const pointsRedeemedInput = document.getElementById('points-redeemed-input');
        if (pointsRedeemedInput) {
            pointsRedeemedInput.value = pointsToRedeem;
        }
    }
}
