@extends('frontend.layouts.main')
@section('main-content')

<div class="cart-wrapper">
    <!-- Left: Cart Items -->
    <div class="cart-left">
        <h2>MY CART (1)</h2>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Rate</th>
                    <th>Charge</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <tr class="cart-item">
                    <td>
                        <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Hot Bag">
                        <strong>HOT ELECTRIC BAG</strong>
                    </td>
                    <td class="unit-price" data-price="300">Rs 300</td>
                    <td class="item-total">Rs 390</td>
                    <td>
                        <div class="quantity-control">
                            <button onclick="decreaseQty(this)">−</button>
                            <input type="text" value="1" readonly class="qty-input">
                            <button onclick="increaseQty(this)">+</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Right: Order Summary -->
    <div class="cart-right">
        <h2>Order Summary</h2>
        <p><span>Total Price</span><span id="subtotal">Rs. 300</span></p>
        <p><span>Delivery Charge</span><span id="delivery">Rs. 90</span></p>
        <hr>
        <p><strong><span>Total</span><span id="total">  Rs. 390</span></strong></p>
<a href="{{route('checkout')}}" class="btn-confirm">Confirm Order</a>
<a href="{{ route('home') }}" class="btn-continue">Continue Shopping</a>

    </div>
</div>

<script>
    const DELIVERY_CHARGE = 90;

    function updateCartTotals() {
        const items = document.querySelectorAll('.cart-item');
        let subtotal = 0;

        items.forEach(item => {
            const price = parseFloat(item.querySelector('.unit-price').dataset.price);
            const qty = parseInt(item.querySelector('.qty-input').value);
            const total = price * qty;

            // Update item total
            item.querySelector('.item-total').textContent = `Rs ${total}`;
            subtotal += total;
        });

        // Update summary
        document.getElementById('subtotal').textContent = `Rs. ${subtotal}`;
        document.getElementById('delivery').textContent = `Rs. ${DELIVERY_CHARGE}`;
        document.getElementById('total').textContent = `Rs. ${subtotal + DELIVERY_CHARGE}`;
    }

    function increaseQty(btn) {
        const input = btn.previousElementSibling;
        let current = parseInt(input.value);
        if (!isNaN(current)) {
            input.value = current + 1;
            updateCartTotals();
        }
    }

    function decreaseQty(btn) {
        const input = btn.nextElementSibling;
        let current = parseInt(input.value);
        if (!isNaN(current) && current > 1) {
            input.value = current - 1;
            updateCartTotals();
        }
    }

    // Initial total update on page load
    document.addEventListener('DOMContentLoaded', updateCartTotals);
</script>

@endsection
