@extends('frontend.layouts.main')

@section('main-content')
<style>
  .checkout-container {
    background-color: #fff;
    padding: 20px;
    font-size: 16px;
  }

  .checkout-container .order-summary,
  .checkout-container .payment-section {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
  }

  .checkout-container h4 {
    margin-bottom: 20px;
    font-weight: bold;
    font-size: 20px;
  }

  .checkout-container .form-control,
  .checkout-container .form-check-input {
    border-radius: 8px;
  }

  .checkout-container .form-check-label {
    font-size: 15px;
    
  }

  .checkout-container .payment-methods {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
  }

  .checkout-container .payment-box {
    flex: 1 1 45%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 10px;
    background-color: #fafafa;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
  }

  .checkout-container .payment-box:hover {
    border-color: #28a745;
    box-shadow: 0 0 10px rgba(40, 167, 69, 0.2);
    background-color: #e9fbe9;
  }

  .checkout-container .payment-box img {
    height: 30px;
    margin-right: 10px;
  }

  .checkout-container .place-order-btn {
    width: 100%;
    padding: 15px;
    background-color: #28a745;
    color: white;
    font-weight: bold;
    font-size: 18px;
    border: none;
    border-radius: 30px;
    margin-top: 20px;
    transition: background-color 0.3s ease;
  }

  .checkout-container .place-order-btn:hover {
    background-color: #218838;
  }

  @media (max-width: 768px) {
    .checkout-container .payment-box {
      flex: 1 1 100%;
    }
  }
</style>

<div class="container checkout-container">
  <div class="row">
    <!-- Left Column -->
    <div class="col-lg-6">
      <div class="order-summary">
        <h4>Billing & Shipping Address</h4>
        <form>
          <div class="form-group mb-3">
            <label>Full Name *</label>
            <input type="text" class="form-control" required />
          </div>
          <div class="form-group mb-3">
            <label>Phone *</label>
            <input type="text" class="form-control" required />
          </div>
          <div class="form-group mb-3">
            <label>Email address *</label>
            <input type="email" class="form-control" required />
          </div>
          <div class="form-group mb-3">
            <label>Enter your Address/Landmark here *</label>
            <input type="text" class="form-control" required />
          </div>
          <div class="form-group mb-3">
            <label>Delivery *</label>
            <div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="delivery" checked>
                <label class="form-check-label">Inside Valley: Rs. 100</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="delivery">
                <label class="form-check-label">Outside Valley: <i>(May vary for bigger items)</i> Rs. 200</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="delivery">
                <label class="form-check-label">Store Pickup: Free</label>
              </div>
            </div>
          </div>
          <div class="form-group mt-3" style="height: 200px;">
            <iframe src="https://maps.google.com/maps?q=London&z=13&output=embed" style="width:100%; height:100%; border:0;" allowfullscreen="" loading="lazy"></iframe>
          </div>
        </form>
      </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-6">
      <div class="payment-section">
        <h4>Choose Payment Options</h4>
        <div class="payment-methods">
          <div class="payment-box">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Esewa_logo.webp/1200px-Esewa_logo.webp.png" alt="eSewa">
            <span>eSewa</span>
          </div>
          <div class="payment-box">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRVm2cDdqOxx_Y_7HzvD6sh_QYx3Nrp-xi07Q&s" alt="Fonepay">
            <span>Fonepay</span>
          </div>
          <div class="payment-box">
            <span>Cash on Delivery</span>
          </div>
          <div class="payment-box">
            <span>Bank Transfer</span>
          </div>
        </div>

        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" id="termsCheck" required>
          <label class="form-check-label" for="termsCheck">
            I agree to the <a href="#" class="text-primary">terms and conditions</a> <span class="text-danger">*</span>
          </label>
        </div>

        <button class="place-order-btn">Place Order</button>
      </div>

      <div class="order-summary mt-3">
        <h4>Your Order</h4>
        <p><strong>Product:</strong>Hot Bag</p>
        <p><strong>Subtotal:</strong> Rs. 3825</p>
        <p><strong>Delivery Charge:</strong> Rs. 100</p>
        <hr>
        <p><strong>Total:</strong> Rs. 3925 <small class="text-muted">(Tax inclusive)</small></p>
      </div>
    </div>
  </div>
</div>
@endsection
