/**
 * Cart Management Module
 *
 * SINGLE authoritative cart system using localStorage.
 * Every cart item: { id, name, price, image, quantity }
 * Data key:        localStorage.perfume_cart
 * Render target:   #cart-items (cart.html)
 * Counter target:  #cart-count (navbar)
 */
var Cart = (function () {
    'use strict';

    var STORAGE_KEY = 'perfume_cart';

    // ------------------------------------------------------------------
    // localStorage helpers
    // ------------------------------------------------------------------

    function loadCart() {
        try {
            var data = localStorage.getItem(STORAGE_KEY);
            var cart = JSON.parse(data);
            return Array.isArray(cart) ? cart : [];
        } catch (e) {
            return [];
        }
    }

    function saveCart(cart) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
    }

    // ------------------------------------------------------------------
    // Server-sync helpers (best-effort — never block UI)
    // ------------------------------------------------------------------

    // Compute base path for PHP endpoints.
    // When loaded from cart.html (root) → 'php/'
    // When loaded from php/shop.php → '../php/'
    var PHP_BASE = (function () {
        var p = window.location.pathname;
        return p.indexOf('/php/') !== -1 ? '../php/' : 'php/';
    })();

    function syncAddToServer(id) {
        var body = 'product_id=' + encodeURIComponent(id);
        fetch(PHP_BASE + 'add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).catch(function () {});
    }

    function syncRemoveFromServer(id) {
        var body = 'product_id=' + encodeURIComponent(id);
        fetch(PHP_BASE + 'remove_cart_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).catch(function () {});
    }

    function syncUpdateOnServer(id, quantity) {
        var body = 'product_id=' + encodeURIComponent(id) + '&quantity=' + encodeURIComponent(quantity);
        fetch(PHP_BASE + 'update_cart_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).catch(function () {});
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Add a product (or increment if already in cart).
     * Syncs to MySQL cart table via PHP endpoint.
     * @param {number} id    - Product ID
     * @param {string} name  - Product name
     * @param {number} price - Product price
     * @param {string} image - Product image path
     */
    function addItem(id, name, price, image) {
        console.debug('[Cart] addItem called:', { id: id, name: name, price: price });

        var cart = loadCart();
        var found = false;

        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id) {
                cart[i].quantity += 1;
                found = true;
                console.debug('[Cart] incremented quantity for id=' + id + ', now=' + cart[i].quantity);
                break;
            }
        }

        if (!found) {
            cart.push({
                id:       id,
                name:     String(name || 'Product'),
                price:    parseFloat(price) || 0,
                image:    String(image || ''),
                quantity: 1
            });
            console.debug('[Cart] inserted new item:', cart[cart.length - 1]);
        }

        saveCart(cart);
        console.debug('[Cart] localStorage now:', JSON.stringify(loadCart()));

        // Sync to MySQL (best-effort)
        syncAddToServer(id);

        updateCounter();
        renderCart();
        return Promise.resolve({ success: true, count: getCountSync() });
    }

    /**
     * Remove a product entirely.
     * Syncs to MySQL cart table via PHP endpoint.
     * @param {number} id - Product ID
     */
    function removeItem(id) {
        console.debug('[Cart] removeItem called:', { id: id });

        var cart = loadCart();
        var filtered = [];

        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id !== id) {
                filtered.push(cart[i]);
            }
        }

        saveCart(filtered);
        console.debug('[Cart] after remove, localStorage:', JSON.stringify(loadCart()));

        // Sync to MySQL (best-effort)
        syncRemoveFromServer(id);

        updateCounter();
        renderCart();
        return Promise.resolve({ success: true });
    }

    /**
     * Update quantity (removes item if quantity < 1).
     * Syncs to MySQL cart table via PHP endpoint.
     * @param {number} id       - Product ID
     * @param {number} quantity - New quantity
     */
    function updateQuantity(id, quantity) {
        if (quantity < 1) {
            return removeItem(id);
        }

        console.debug('[Cart] updateQuantity called:', { id: id, quantity: quantity });

        var cart = loadCart();
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id) {
                cart[i].quantity = quantity;
                break;
            }
        }

        saveCart(cart);

        // Sync to MySQL (best-effort)
        syncUpdateOnServer(id, quantity);

        updateCounter();
        renderCart();
        return Promise.resolve({ success: true });
    }

    /**
     * Remove every item from the local cart.
     * Does NOT sync to server (the server cart is cleared after checkout).
     */
    function clearCart() {
        console.debug('[Cart] clearCart called');
        saveCart([]);
        updateCounter();
        renderCart();
    }

    /**
     * Submit all cart items to the server to create an order.
     * On success, clears localStorage and redirects to thankyou.html.
     * @returns {Promise}
     */
    function checkout() {
        console.debug('[Cart] checkout called');

        var cart = loadCart();
        if (cart.length === 0) {
            alert('Your cart is empty.');
            return Promise.reject(new Error('Cart is empty'));
        }

        return fetch(PHP_BASE + 'checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart })
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                clearCart();
                window.location.href = 'thankyou.html';
                return data;
            } else {
                throw new Error(data.error || 'Checkout failed');
            }
        })
        .catch(function (err) {
            console.error('[Cart] checkout error:', err);
            alert('Checkout failed: ' + err.message + '\nPlease try again.');
            throw err;
        });
    }

    /** @returns {Promise<Array>} */
    function getCart() {
        return Promise.resolve(loadCart());
    }

    /** @returns {Promise<number>} Total item count */
    function getCount() {
        return Promise.resolve(getCountSync());
    }

    /** Synchronous count (used by updateCounter). */
    function getCountSync() {
        var cart = loadCart();
        var count = 0;
        for (var i = 0; i < cart.length; i++) {
            count += cart[i].quantity;
        }
        return count;
    }

    /** @returns {Promise<string>} Total price formatted to 2 decimals */
    function getTotal() {
        var cart = loadCart();
        var total = 0;
        for (var i = 0; i < cart.length; i++) {
            total += (parseFloat(cart[i].price) || 0) * cart[i].quantity;
        }
        return Promise.resolve(total.toFixed(2));
    }

    /** Update the navbar badge. */
    function updateCounter() {
        var el = document.getElementById('cart-count');
        if (el) {
            el.textContent = getCountSync();
        }
    }

    // ------------------------------------------------------------------
    // Rendering (authoritative — uses internal loadCart)
    // ------------------------------------------------------------------

    /**
     * Render every cart item into #cart-items.
     * Handles: empty state, item rows, total, summary visibility.
     */


    function renderCart() {
        console.debug('[Cart] renderCart called');

        var cart    = loadCart();
        var itemsEl = document.getElementById('cart-items');
        var emptyEl = document.getElementById('cart-empty');
        var sumEl   = document.getElementById('cart-summary');
        var totalEl = document.getElementById('cart-total');

        if (!itemsEl) {
            console.debug('[Cart] #cart-items not found — not on cart page');
            return;
        }

        console.debug('[Cart] rendering', cart.length, 'items');

        // --- Empty state ---
        if (cart.length === 0) {
            itemsEl.innerHTML = '';
            if (emptyEl) emptyEl.style.display = 'block';
            if (sumEl)   sumEl.style.display   = 'none';
            if (totalEl) totalEl.textContent   = '0.00 JD';
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';
        if (sumEl)   sumEl.style.display   = 'block';

        // --- Build item rows ---
        var html  = '';
        var total = 0;

        for (var i = 0; i < cart.length; i++) {
            var item  = cart[i];
            var price = parseFloat(item.price) || 0;
            var qty   = parseInt(item.quantity, 10) || 0;
            var sub   = (price * qty).toFixed(2);
            total    += price * qty;

            html += '<div class="cart-item" data-id="' + item.id + '">';
            html += '  <div class="cart-item-image">';
            html += '    <img src="' + (item.image || '') + '" alt="' + (item.name || '') + '">';
            html += '  </div>';
            html += '  <div class="cart-item-info">';
            html += '    <h3>' + (item.name || '') + '</h3>';
            html += '    <p class="cart-item-price">' + price.toFixed(2) + ' JD</p>';
            html += '  </div>';
            html += '  <div class="cart-item-quantity">';
            html += '    <button class="qty-btn qty-minus" data-id="' + item.id + '">-</button>';
            html += '    <span class="qty-value">' + qty + '</span>';
            html += '    <button class="qty-btn qty-plus" data-id="' + item.id + '">+</button>';
            html += '  </div>';
            html += '  <div class="cart-item-subtotal">' + sub + ' JD</div>';
            html += '  <button class="cart-item-remove" data-id="' + item.id + '">&times;</button>';
            html += '</div>';
        }

        itemsEl.innerHTML = html;
        if (totalEl) totalEl.textContent = total.toFixed(2) + ' JD';
        console.debug('[Cart] render complete, total=' + total.toFixed(2));
    }

    /** Alias for renderCart (used by external code). */
    function renderCartItems() {
        renderCart();
    }

    /** Update counter + re-render. */
    function updateCartUI() {
        updateCounter();
        renderCart();
    }

    // ------------------------------------------------------------------
    // Cart button event delegation (+ / - / remove)
    // Registered ONCE here — never lost after re-render.
    // ------------------------------------------------------------------

    document.addEventListener('click', function (event) {
        var target = event.target;

        // Plus
        if (target.classList.contains('qty-plus')) {
            var id = parseInt(target.getAttribute('data-id'), 10);
            var qtyEl = target.parentElement.querySelector('.qty-value');
            var newQty = parseInt(qtyEl.textContent, 10) + 1;
            updateQuantity(id, newQty);
        }

        // Minus
        if (target.classList.contains('qty-minus')) {
            var id = parseInt(target.getAttribute('data-id'), 10);
            var qtyEl = target.parentElement.querySelector('.qty-value');
            var newQty = parseInt(qtyEl.textContent, 10) - 1;
            if (newQty >= 1) {
                updateQuantity(id, newQty);
            } else {
                removeItem(id);
            }
        }

        // Remove (X)
        if (target.classList.contains('cart-item-remove')) {
            var id = parseInt(target.getAttribute('data-id'), 10);
            removeItem(id);
        }
    });

    // ------------------------------------------------------------------
    // Auto-run on every page load
    // ------------------------------------------------------------------

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            updateCounter();
            renderCart();
        });
    } else {
        updateCounter();
        renderCart();
    }

    return {
        addItem:         addItem,
        removeItem:      removeItem,
        updateQuantity:  updateQuantity,
        getCart:         getCart,
        getCount:        getCount,
        getTotal:        getTotal,
        updateCounter:   updateCounter,
        renderCart:      renderCart,
        renderCartItems: renderCartItems,
        updateCartUI:    updateCartUI,
        clearCart:       clearCart,
        checkout:        checkout
    };
})();
