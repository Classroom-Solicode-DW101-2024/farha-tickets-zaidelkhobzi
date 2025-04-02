// Toggle dropdown
document.querySelector('.select-box--selected-item').addEventListener('click', function() {
    const items = document.querySelector('.select-box--items');
    const arrow = document.querySelector('.select-box--arrow');
    items.classList.toggle('hidden');
    arrow.classList.toggle('open');

    document.querySelector(".selected span").style.marginRight = "20px";
    document.querySelector(".select-box--selected-item span").style.marginRight = "20px";
});

// Toggle dropdown
document.querySelector('.select-box--arrow').addEventListener('click', function() {
    const items = document.querySelector('.select-box--items');
    const arrow = document.querySelector('.select-box--arrow');
    items.classList.toggle('hidden');
    arrow.classList.toggle('open');

    document.querySelector(".selected span").style.color = "#6C63FF";
    document.querySelector(".select-box--selected-item span").style.marginRight = "20px";
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const container = document.querySelector('.select-box--container');
    if (!container.contains(event.target)) {
        document.querySelector('.select-box--items').classList.add('hidden');
        document.querySelector('.select-box--arrow').classList.remove('open');
    }
});

// Handle ticket type selection
document.querySelectorAll('.select-box--items div').forEach(item => {
    item.addEventListener('click', function() {
        // Update displayed selection
        const ticketType = this.getAttribute('data-type');
        const ticketPrice = this.getAttribute('data-price');
        document.querySelector('.select-box--selected-item').innerHTML = 
            `${ticketType}<span>${ticketPrice} MAD</span>`;
        
        // Update hidden form fields
        document.getElementById('ticketType').value = ticketType;
        document.getElementById('ticketPrice').value = ticketPrice;
        
        // Mark selected item
        document.querySelectorAll('.select-box--items div').forEach(el => {
            el.classList.remove('selected');
        });
        this.classList.add('selected');
        
        // Hide dropdown after selection
        document.querySelector('.select-box--items').classList.add('hidden');
        document.querySelector('.select-box--arrow').classList.remove('open');
    });
});

// Quantity buttons functionality
const quantityInput = document.getElementById('cart-quantity');

document.querySelector('.decrease-btn').addEventListener('click', function() {
    if (quantityInput.value > 1) {
        quantityInput.value = parseInt(quantityInput.value) - 1;
    }
});

document.querySelector('.increase-btn').addEventListener('click', function() {
    quantityInput.value = parseInt(quantityInput.value) + 1;
});

// Handle popup display
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.querySelector('.popup');
    if (popup) {
        popup.classList.add('active');

        document.querySelector('.close-btn').addEventListener('click', function() {
            popup.classList.remove('active');
        });
    }
});