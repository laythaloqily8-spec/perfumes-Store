/**
 * Shop Page Functionality
 * Handles search, filtering, add-to-cart, and product click on the shop page.
 */
(function () {
    'use strict';

    // --- Product Data ---
    // Use server-provided data (from shop.php) if available, otherwise fall back
    // to the hardcoded array for backward compatibility with the static shop.html.
    var products = window.SHOP_DATA || [
        { id: 1, name: 'Opulent Herenius',   price: 75.00, image: 'images/brown.jpg', category: 'luxury',  description: 'A bold, rich blend of dark oud, dry wood, and desert sand chords.' },
        { id: 2, name: 'Lost Cherry Luxe',   price: 90.00, image: 'images/red.jpg',   category: 'women',   description: 'A luscious, full-bodied journey into the vibrant, sweet-tart cherry notes.' },
        { id: 3, name: 'Baiciel Parrane',    price: 65.00, image: 'images/white.jpg',  category: 'unisex',  description: 'An ethereal, soft powdery mist layered over delicate white floral petals.' },
        { id: 4, name: 'Spectrum Forest',    price: 55.00, image: 'images/green.jpg',  category: 'men',     description: 'Crisp pine needles fused with deep mossy tones and a touch of green warmth.' },
        { id: 5, name: 'Parfum Homme Marine', price: 48.00, image: 'images/blue.jpg',  category: 'men',     description: 'An ocean breeze splash combined with fresh aquatic minerals and clean citrus zest.' },
        { id: 6, name: 'Rosé Clouds Elixir', price: 80.00, image: 'images/pink.jpg',   category: 'women',   description: 'A dreamlike pink vanilla cream cloud surrounding a luxurious blooming rose heart.' }
    ];

    var searchInput = document.getElementById('search-input');
    var filterBtns = document.querySelectorAll('.filter-btn');
    var productCards = document.querySelectorAll('.product-card');
    var currentFilter = 'all';
    var currentQuery = '';

    // Read initial filter/search state from URL parameters so the JS
    // client-side filter stays in sync with the server-side result set.
    var urlParams = new URLSearchParams(window.location.search);
    var urlCategory = urlParams.get('category') || 'all';
    var urlSearch = urlParams.get('search') || '';
    currentFilter = urlCategory;
    currentQuery = urlSearch;

    /**
     * Show or hide product cards based on the current search query and filter.
     */
    function applyFilters() {
        var query = currentQuery.toLowerCase().trim();

        productCards.forEach(function (card) {
            var cardName = card.getAttribute('data-name') || '';
            var cardCategory = card.getAttribute('data-category') || '';

            // Check category filter
            var matchesFilter = (currentFilter === 'all' || cardCategory === currentFilter);

            // Check search query — match against name or category
            var matchesSearch = true;
            if (query) {
                matchesSearch = cardName.toLowerCase().indexOf(query) !== -1 ||
                                cardCategory.toLowerCase().indexOf(query) !== -1;
            }

            // Show the card only if both conditions pass
            if (matchesFilter && matchesSearch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // --- Search Input Handlers ---
    if (searchInput) {
        // Real-time client-side filtering (instant feedback without page reload)
        searchInput.addEventListener('input', function () {
            currentQuery = this.value;
            applyFilters();
        });

        // Enter key triggers a full page reload with the search query as a GET
        // parameter so the server can filter products at the database level.
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var query = this.value.trim();
                var url = 'shop.php';
                var params = new URLSearchParams(window.location.search);
                if (query) {
                    params.set('search', query);
                } else {
                    params.delete('search');
                }
                var qs = params.toString();
                if (qs) {
                    url += '?' + qs;
                }
                window.location.href = url;
            }
        });
    }

    // --- Filter Button Handler ---
    // On click, navigate to the same page with the selected category as a GET
    // parameter. The server then queries only matching products from MySQL,
    // and the full result set is returned for the selected category.
    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var category = this.getAttribute('data-filter');
            var url = 'shop.php';
            var params = new URLSearchParams(window.location.search);

            if (category !== 'all') {
                params.set('category', category);
            } else {
                params.delete('category');
            }

            var qs = params.toString();
            if (qs) {
                url += '?' + qs;
            }
            window.location.href = url;
        });
    });

    // --- Add to Cart Button Handler ---
    document.addEventListener('click', function (event) {
        var target = event.target;

        if (target.classList.contains('add-to-cart-btn')) {
            // Stop the button from submitting the surrounding form
            // (buttons default to type="submit" when inside a <form>).
            event.preventDefault();

            var id = parseInt(target.getAttribute('data-id'), 10);
            var product = null;

            for (var i = 0; i < products.length; i++) {
                if (products[i].id === id) {
                    product = products[i];
                    break;
                }
            }

            if (product) {
                Cart.addItem(id, product.name, product.price, product.image);
                alert(product.name + ' has been added to your cart!');
                Cart.updateCounter();
            }
        }
    });

    // --- Product Card Click: navigate to product detail page ---
    productCards.forEach(function (card) {
        card.addEventListener('click', function (event) {
            // Do not trigger when clicking a button or input inside the card
            if (event.target.tagName === 'BUTTON' || event.target.tagName === 'INPUT') {
                return;
            }

            var id = this.getAttribute('data-id');
            if (id) {
                var base = window.location.pathname.indexOf('/php/') !== -1 ? '../' : '';
                console.debug('[Shop] Clicked card data-id=' + id + ', navigating to ' + base + 'product.html?id=' + id);
                window.location.href = base + 'product.html?id=' + id;
            }
        });
    });
})();
