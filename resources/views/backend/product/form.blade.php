<style>
    .ql-container {
        height: 200px;
    }

    .ql-editor {
        min-height: 100% !important;
    }

    input[type="file"] {
        display: block;
    }

    textarea {
        resize: none;
    }

    .imageThumb {
        max-height: 75px;
        border: 2px solid;
        margin-left: 10px;
        margin-bottom: 3px;
        padding: 1px;
        cursor: pointer;
    }

    .pip {
        display: inline-block;
        margin: 10px 10px 0 0;
    }


    .cropper-container {
        width: 100% !important;
    }

    .modal-header {
        position: relative;
    }

    .modal-header .closeCrop {
        position: absolute;
        top: 13px;
        right: 15px;
    }

    label#thumbnail_image-error {
        position: absolute;
        top: 9rem !important
    }

    #ndp-nepali-box {
        top: 60px !important;
        left: 10px !important;
    }

    input#nepali-datepicker {
        width: 100% !important;
        height: 50% !important;
        border-radius: 0.2rem !important;
        border: 0.1px solid rgb(236, 231, 231);
        padding-left: 0.5rem !important;
    }
</style>
<div class="modal-header">
    <h5 class="modal-title" id="staticBackdropLabel">
        {{ empty($prevPost->id) ? 'Add New Product' : 'Edit Product' }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <form action="{{ route('admin.product.save') }}" method="POST" id="productForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $prevPost->id ?? '' }}">

        {{-- Section 1: Basic Info --}}
        <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select" name="category_id" required>
                    <option disabled selected>Select Category</option>
                    @foreach ($category as $categoryProduct)
                        <option value="{{ $categoryProduct->id }}" 
                            {{ @$prevPost->category_id == $categoryProduct->id ? 'selected' : '' }}>
                            {{ $categoryProduct->name }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-4">
                <label class="form-label">Unit Sale <span class="text-danger">*</span></label>
                <select class="form-select" name="unit_sale_id" required>
                    <option disabled selected>Select Sale Unit</option>
                    @foreach ($unit as $unitItem)
                        <option value="{{ $unitItem->id }}"
                            {{ @$prevPost->sale_unit_id == $unitItem->id ? 'selected' : '' }}>
                            {{ $unitItem->unit_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Unit Purchase <span class="text-danger">*</span></label>
                <select class="form-select" name="unit_purchase_id" required>
                    <option disabled selected>Select Purchase Unit</option>
                    @foreach ($unit as $unitItem)
                        <option value="{{ $unitItem->id }}"
                            {{ @$prevPost->purchase_unit_id == $unitItem->id ? 'selected' : '' }}>
                            {{ $unitItem->unit_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Conversion</label>
                <input type="number" step="0.01" name="conversion" id="conversion" class="form-control" placeholder="e.g. 10"
                    value="{{ @$prevPost->conversion }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="product_name" class="form-control" placeholder="e.g. Paracetamol 500mg"
                    value="{{ @$prevPost->product_name }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Generic Name</label>
                <input type="text" name="generic_name" class="form-control" placeholder="e.g. Paracetamol"
                    value="{{ @$prevPost->generic_name }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Composition</label>
                <input type="text" name="composition" class="form-control" placeholder="e.g. Paracetamol 500 mg"
                    value="{{ @$prevPost->composition }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Group Name</label>
                <input type="text" name="group_name" class="form-control" placeholder="e.g. Analgesic"
                    value="{{ @$prevPost->group_name }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Manufacturer</label>
                <input type="text" name="manufacturer" class="form-control" placeholder="Company name"
                    value="{{ @$prevPost->manufacturer }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Product Status</label>
                <select class="form-select" name="product_status">
                    <option value="instock" @selected(old('product_status', $product->product_status ?? '') == 'instock')>In Stock</option>
                    <option value="stockout" @selected(old('product_status', $product->product_status ?? '') == 'stockout')>Stock Out</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Order Number</label>
                <input type="number" name="order_number" class="form-control"
                    value="{{ @$prevPost->order_number }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Alert Quantity</label>
                <input type="number" name="alert_quantity" class="form-control"
                    value="{{ @$prevPost->alert_quantity }}">
            </div>
        </div>

        {{-- Section 2: Pricing --}}
        <h6 class="border-bottom pb-2 mt-4 mb-3">Pricing</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Previous Price</label>
                <input type="number" step="0.01" name="previous_price" class="form-control"
                    value="{{ @$prevPost->previous_price }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">MRP <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="mrp" id="mrp" class="form-control"
                    value="{{ @$prevPost->mrp }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Discount (%)</label>
                <input type="number" step="0.01" name="discount" id="discount" class="form-control"
                    value="{{ @$prevPost->discount }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Display Price</label>
                <input type="number" step="0.01" name="display_price" id="display_price" class="form-control"
                    value="{{ old('display_price') }}" readonly>
            </div>

            <div class="col-md-3">
                <label class="form-label">Profit</label>
                <input type="number" step="0.01" name="profit" id="profit" class="form-control"
                    value="{{ @$prevPost->profit }}" readonly>
            </div>

            <div class="col-md-3">
                <label class="form-label">Purchase Price</label>
                <input type="number" step="0.01" name="purchase_price" class="form-control"
                    value="{{ @$prevPost->purchase_price }}" >
            </div>
        </div>

        {{-- Section 3: SEO --}}
        <h6 class="border-bottom pb-2 mt-4 mb-3">SEO Settings</h6>
        <div class="row">
            <div class="col-12">
                <label class="form-label">Meta Keywords</label>
                <textarea name="keywords" class="form-control" rows="2" placeholder="e.g. pain killer, analgesic">{{@$prevPost->keywords}}</textarea>
            </div>
        </div>

        {{-- Section 4: Description --}}
        <h6 class="border-bottom pb-2 mt-4 mb-3">Product Description</h6>
        <div class="mb-3">
            <div id="descriptionEditor" style="min-height:200px;">
                {!! @$prevPost->description !!}
            </div>
            <input type="hidden" name="description" id="description">
        </div>

        {{-- Section 5: Thumbnail --}}
        <h6 class="border-bottom pb-2 mt-4 mb-3">Thumbnail Image</h6>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Upload Thumbnail</label>
                <div class="relative mb-2">
                    <label for="thumbnail_image" class="fe fe-camera profile-edit text-primary absolute"></label>
                    <input type="file" id="thumbnail_image" name="image" accept="image/*" class="d-none">
                    <div class="img-rectangle mt-2">
                        @if(!empty($prevPost->image))
                            <img src="{{ asset('storage/product/'.$prevPost->image) }}" class="_image img-thumbnail" alt="Thumbnail">
                        @else
                            <img src="{{ asset('images/no-image.jpg') }}" class="_image img-thumbnail" alt="Default">
                        @endif
                    </div>
                </div>
                <small class="text-muted">Supported: jpg/jpeg/png | Suggested size: 300×475 px</small>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary saveProduct">
        <i class="fa fa-save"></i> {{ empty($prevPost->id) ? 'Save' : 'Update' }}
    </button>
</div>

<script>

   document.addEventListener('input', () => {
        calcPrice();
        calcProfit();
    });

    function calcPrice() {
        const mrp = parseFloat(document.getElementById('mrp').value) || 0;
        const disc = parseFloat(document.getElementById('discount').value) || 0;
        const priceField = document.getElementById('display_price');
        priceField.value = (mrp - (mrp * disc / 100)).toFixed(2);
    }

    function calcProfit() {
        const displayPrice = parseFloat(document.getElementById('display_price').value) || 0;
        const purchasePrice = parseFloat(document.querySelector('[name="purchase_price"]').value) || 0;
        let conversion = parseFloat(document.getElementById('conversion').value);

        if (!conversion || conversion <= 0) {
            conversion = 1; // Default to 1 if invalid
        }

        const profit = displayPrice - (purchasePrice / conversion);
        document.getElementById('profit').value = profit.toFixed(2);
    }

    // Initial call
    calcPrice();
    calcProfit();

    $(document).ready(function() {

        var quill = new Quill('#descriptionEditor', {
            theme: 'snow'
        });

        function updateImagePreview(input, targetImageClass) {
            const selectedFile = input.files[0];
            if (selectedFile) {
                $(targetImageClass).attr('src', URL.createObjectURL(selectedFile));
            }
        }

        $('#thumbnail_image').on('change', function(event) {
            updateImagePreview(this, '._image');
        });

        $('#front_image').on('change', function(event) {
            updateImagePreview(this, '.front_image');
        });

        $('#back_image').on('change', function(event) {
            updateImagePreview(this, '.back_image');
        });

        $('#right_image').on('change', function(event) {
            updateImagePreview(this, '.right_image');
        });

        $('#left_image').on('change', function(event) {
            updateImagePreview(this, '.left_image');
        });

        $('#productForm').validate({
            rules: {
                category_id: "required",
                product_name: "required",
                stock_quantity: "required",
                description: "required",
                order_number: "required",
                price: "required",
                type: "required",
                // keywords: "required",
                image: {
                    required: function() {
                        return $('#id').val() === '';
                    }
                }
            },
            messages: {
                category_id: {
                    required: "Category is required."
                },
                type: {
                    required: "Type is required."
                },
                product_name: {
                    required: "Product name is required."
                },
                order_number: {
                    required: "Order is required."
                },
                stock_quantity: {
                    required: "Stock quantity is required."
                },
                image: {
                    required: "Thumbnail image is required."
                },
                price: {
                    required: "Pirce is required."
                },
                description: {
                    required: "Description is required."
                },
                image: {
                    required: "Image Field is required"
                },
                // keywords: {
                //     required: "keywords Field is required"
                // }
            },
            highlight: function(element) {
                $(element).addClass('border-danger');
            },
            unhighlight: function(element) {
                $(element).removeClass('border-danger');
            }
        });

        $('.saveProduct').on('click', function() {
            if ($('#productForm').valid()) {
                showLoader();
                var specification = quill.root.innerHTML;
                $('#productForm').find('#description').val(specification);
                $('#productForm').ajaxSubmit({
                    success: function(response) {
                        if (response.type === 'success') {
                            showNotification(response.message, 'success');
                            productTable.draw();
                            $('#productForm')[0].reset();
                            $('.img-rectangle img').attr('src',
                                '{{ asset('/no-image.jpg') }}');
                            $('#productModal').modal('hide');
                        } else {
                            showNotification(response.message, 'error');
                        }
                        hideLoader();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        showNotification(response ? response.message : 'An error occurred',
                            'error');
                        hideLoader();
                    }
                });
            }
        });
    });
</script>
