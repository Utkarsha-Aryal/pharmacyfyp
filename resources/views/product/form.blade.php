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
                <label class="form-label">Company <span class="text-danger">*</span></label>
                <select class="form-select js-select2" name="company_id" data-placeholder="Select Company" required>
                    <option disabled selected>Select Company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" data-default-cc-rate="{{ number_format((float) ($company->default_cc_rate ?? 0), 2, '.', '') }}"
                            {{ @$prevPost->company_id == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-4">
                <label class="form-label d-flex justify-content-between align-items-center">Unit Sale <span>
                        @can('inventory.unit')
                            <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add sale unit" data-quick-modal="#quickUnitModal" data-quick-target-select="#productSaleUnitSelect" data-unit-type="sales">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        @endcan
                    </span></label>
                <select class="form-select js-select2" id="productSaleUnitSelect" name="unit_sale_id" data-placeholder="Select Sale Unit" required>
                    <option disabled selected>Select Sale Unit</option>
                    @foreach ($saleUnits as $unitItem)
                        <option value="{{ $unitItem->id }}"
                            {{ @$prevPost->sale_unit_id == $unitItem->id ? 'selected' : '' }}>
                            {{ $unitItem->unit_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label d-flex justify-content-between align-items-center">Unit Purchase <span>
                        @can('inventory.unit')
                            <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add purchase unit" data-quick-modal="#quickUnitModal" data-quick-target-select="#productPurchaseUnitSelect" data-unit-type="purchase">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        @endcan
                    </span></label>
                <select class="form-select js-select2" id="productPurchaseUnitSelect" name="unit_purchase_id" data-placeholder="Select Purchase Unit" required>
                    <option disabled selected>Select Purchase Unit</option>
                    @foreach ($purchaseUnits as $unitItem)
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
                <label class="form-label">Product Code</label>
                <input type="text" name="product_code" class="form-control" placeholder="Optional unique code"
                    value="{{ old('product_code', $prevPost->product_code ?? '') }}">
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
                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Product Status</span>
                    @can('settings.manage')
                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add product status" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#productStatusSelect" data-dropdown-alias="product_status" data-dropdown-label="Product Status" data-dropdown-supports-data="0">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    @endcan
                </label>
                <select class="form-select js-select2" id="productStatusSelect" name="product_status_id" data-placeholder="Select status" data-dropdown-alias="product_status">
                    <option value="">Select status</option>
                    @foreach ($productStatuses as $statusOption)
                        <option value="{{ $statusOption->id }}" @selected((int) old('product_status_id', $prevPost->product_status_id ?? 0) === (int) $statusOption->id)>
                            {{ $statusOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Formulation</span>
                    @can('settings.manage')
                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add formulation" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#productFormulationSelect" data-dropdown-alias="formulation" data-dropdown-label="Formulation" data-dropdown-supports-data="0">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    @endcan
                </label>
                <select class="form-select js-select2" id="productFormulationSelect" name="formulation_id" data-placeholder="Select formulation" data-dropdown-alias="formulation">
                    <option value="">Select formulation</option>
                    @foreach ($formulations as $formulationOption)
                        <option value="{{ $formulationOption->id }}" @selected((int) old('formulation_id', $prevPost->formulation_id ?? 0) === (int) $formulationOption->id)>
                            {{ $formulationOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-control" placeholder="e.g. Strip"
                    value="{{ old('unit', $prevPost->unit ?? '') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Reorder Level</label>
                <input type="number" name="reorder_level" class="form-control"
                    value="{{ old('reorder_level', $prevPost->reorder_level ?? $prevPost->alert_quantity ?? 10) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Active</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" role="switch"
                        id="productActiveSwitch" @checked(old('is_active', $prevPost->is_active ?? true))>
                    <label class="form-check-label" for="productActiveSwitch">Allow this product in backend</label>
                </div>
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
                <label class="form-label">CC Rate (%)</label>
                <input type="number" step="0.01" min="0" name="cc_rate" id="cc_rate" class="form-control"
                    value="{{ old('cc_rate', $prevPost->cc_rate ?? 0) }}">
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
                company_id: "required",
                product_name: "required",
                unit_sale_id: "required",
                unit_purchase_id: "required",
                description: "required",
                mrp: "required",
                cc_rate: {
                    number: true,
                    min: 0
                },
                image: {
                    required: function() {
                        return $('#id').val() === '';
                    }
                }
            },
            messages: {
                company_id: {
                    required: "Company is required."
                },
                unit_sale_id: {
                    required: "Sale unit is required."
                },
                unit_purchase_id: {
                    required: "Purchase unit is required."
                },
                product_name: {
                    required: "Product name is required."
                },
                image: {
                    required: "Thumbnail image is required."
                },
                mrp: {
                    required: "MRP is required."
                },
                cc_rate: {
                    number: "CC Rate must be a number.",
                    min: "CC Rate cannot be negative."
                },
                description: {
                    required: "Description is required."
                },
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
                            var modalElement = document.getElementById('productModal');
                            var modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
                            if (modalInstance) {
                                modalInstance.hide();
                            }
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

        var ccRateManuallyEdited = false;
        var isEditMode = $('#id').val() !== '';
        var $companySelect = $('#productForm select[name="company_id"]');
        var $ccRateInput = $('#cc_rate');

        $ccRateInput.on('input', function() {
            ccRateManuallyEdited = true;
        });

        $companySelect.on('change', function() {
            var selected = $(this).find('option:selected');
            var defaultCcRate = parseFloat(selected.data('defaultCcRate'));
            var safeCcRate = Number.isFinite(defaultCcRate) ? defaultCcRate : 0;

            if (!ccRateManuallyEdited || !isEditMode || !$ccRateInput.val()) {
                $ccRateInput.val(safeCcRate.toFixed(2));
            }
        });

        if (!isEditMode) {
            $companySelect.trigger('change');
        }
    });
</script>
