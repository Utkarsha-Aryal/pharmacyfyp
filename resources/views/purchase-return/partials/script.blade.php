<script>
    $(function () {
        var $supplierSelect = $('#purchaseReturnSupplier');
        var $purchaseSelect = $('#purchaseReturnPurchase');
        var $modeInputs = $('input[name="return_mode"]');
        var $itemsTbody = $('#purchaseReturnItemsTable tbody');
        var $loadBillButton = $('#purchaseReturnLoadBillItems');
        var $addManualButton = $('#purchaseReturnAddManualItem');
        var $manualRowTemplate = $('#purchaseReturnManualRowTemplate');
        var initialSelectedPurchaseId = '{{ (string) old('purchase_id', $purchaseReturn?->purchase_id ?? '') }}';
        var preserveInitialBillRows = !!initialSelectedPurchaseId && $itemsTbody.find('tr').not('.purchase-return-empty-row').length > 0;

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function safeNumber(value) {
            var parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function emptyMessage(message) {
            return '<tr class="purchase-return-empty-row"><td colspan="13" class="text-center text-muted">' + escapeHtml(message) + '</td></tr>';
        }

        function purchaseReturnPricingNote(row) {
            return row && row.original_pricing_note
                ? row.original_pricing_note
                : '';
        }

        function hasDataRows() {
            return $itemsTbody.find('tr').not('.purchase-return-empty-row').length > 0;
        }

        function resetItemsTable(message) {
            $itemsTbody.html(emptyMessage(message || 'Select a purchase bill or switch to product mode to start adding return rows.'));
            updateRowNumbers();
            updateTotals();
        }

        function updateRowNumbers() {
            $itemsTbody.find('tr').not('.purchase-return-empty-row').each(function (index) {
                $(this).find('.purchase-return-row-number').text(index + 1);
            });
        }

        function updateTotals() {
            var totalQty = 0;
            var grossTotal = 0;
            var discountTotal = 0;
            var totalAmount = 0;

            $itemsTbody.find('tr').not('.purchase-return-empty-row').each(function () {
                var $row = $(this);
                var qty = safeNumber($row.find('.purchase-return-qty-input').val());
                var rate = safeNumber($row.find('.purchase-return-rate-input').val());

                totalQty += qty;
                grossTotal += qty * rate;
                discountTotal += safeNumber($row.find('.purchase-return-discount-amount-input').val());
                totalAmount += safeNumber($row.find('.purchase-return-amount-input').val());
            });

            $('#purchaseReturnQtyTotal').val(totalQty.toFixed(0));
            $('#purchaseReturnGrossTotal').val(grossTotal.toFixed(2));
            $('#purchaseReturnDiscountTotal').val(discountTotal.toFixed(2));
            $('#purchaseReturnAmountTotal').val(totalAmount.toFixed(2));
        }

        function updateQtyLimit($row) {
            var rowMode = $row.data('rowMode');
            var baseMax = safeNumber($row.attr('data-base-max-returnable'));
            var $selectedBatch = $row.find('.purchase-return-batch-select option:selected');
            var batchAvailable = safeNumber($selectedBatch.data('quantityAvailable'));
            var maxAllowed = rowMode === 'bill'
                ? (batchAvailable > 0 ? Math.min(baseMax || batchAvailable, batchAvailable) : baseMax)
                : batchAvailable;

            if (maxAllowed < 0) {
                maxAllowed = 0;
            }

            $row.find('.purchase-return-max-label').text(maxAllowed.toFixed(0));
            $row.find('.purchase-return-qty-input').attr('max', maxAllowed);

            if (safeNumber($row.find('.purchase-return-qty-input').val()) > maxAllowed) {
                $row.find('.purchase-return-qty-input').val(maxAllowed.toFixed(0));
            }
        }

        function setRowInputValue($row, selector, value, decimals, preserveEditing) {
            var $input = $row.find(selector);

            if (!$input.length) {
                return;
            }

            if (preserveEditing && document.activeElement === $input.get(0)) {
                return;
            }

            $input.val(Number(value || 0).toFixed(decimals));
        }

        function recalculateRow($row, source, options) {
            options = options || {};
            var qty = safeNumber($row.find('.purchase-return-qty-input').val());
            var maxAllowed = safeNumber($row.find('.purchase-return-qty-input').attr('max'));
            var rate = safeNumber($row.find('.purchase-return-rate-input').val());
            var discountPercent = safeNumber($row.find('.purchase-return-discount-input').val());
            var discountAmount = safeNumber($row.find('.purchase-return-discount-amount-input').val());
            var netRate = safeNumber($row.find('.purchase-return-net-rate-input').val());

            if (maxAllowed > 0 && qty > maxAllowed) {
                qty = maxAllowed;
            }

            if (qty < 0) {
                qty = 0;
            }

            if (source === 'amount') {
                var amountDiscountPerUnit = qty > 0 ? discountAmount / qty : 0;
                netRate = Math.max(0, rate - amountDiscountPerUnit);
                discountPercent = rate > 0 ? ((rate - netRate) / rate) * 100 : 0;
            } else if (source === 'net') {
                netRate = Math.max(0, Math.min(rate, netRate));
                discountAmount = Math.max(0, (rate - netRate) * qty);
                discountPercent = rate > 0 ? ((rate - netRate) / rate) * 100 : 0;
            } else {
                discountPercent = Math.max(0, Math.min(100, discountPercent));
                netRate = Math.max(0, rate - ((rate * discountPercent) / 100));
                discountAmount = Math.max(0, (rate - netRate) * qty);
            }

            $row.attr('data-pricing-mode', source);
            setRowInputValue($row, '.purchase-return-qty-input', qty, 0, options.preserveEditing);
            setRowInputValue($row, '.purchase-return-rate-input', rate, 2, options.preserveEditing);
            setRowInputValue($row, '.purchase-return-discount-input', discountPercent, 2, options.preserveEditing);
            setRowInputValue($row, '.purchase-return-discount-amount-input', discountAmount, 2, options.preserveEditing);
            setRowInputValue($row, '.purchase-return-net-rate-input', netRate, 2, options.preserveEditing);
            setRowInputValue($row, '.purchase-return-amount-input', qty * netRate, 2, options.preserveEditing);
            updateTotals();
        }

        function applyBatchState($row) {
            var $select = $row.find('.purchase-return-batch-select');
            var $option = $select.find('option:selected');
            var rowMode = $row.data('rowMode');
            var badgeClass = $option.data('badgeClass') || 'bg-light text-dark border';
            var badgeLabel = $option.data('badgeLabel') || (rowMode === 'manual' ? 'Choose batch' : 'Select batch');
            var quantityReceived = safeNumber($option.data('quantityReceived'));
            var purchasePrice = safeNumber($option.data('purchasePrice'));

            $row.find('.purchase-return-batch-badge').attr('class', 'badge purchase-return-batch-badge ' + badgeClass).text(badgeLabel);

            if (rowMode === 'manual') {
                $row.find('.purchase-return-original-label').text(quantityReceived.toFixed(0));
                $row.find('.purchase-return-returned-label').text('0');

                if ($option.val()) {
                    $row.find('.purchase-return-rate-input').val(purchasePrice.toFixed(2));
                    if (!$row.find('.purchase-return-qty-input').val() || safeNumber($row.find('.purchase-return-qty-input').val()) === 0) {
                        $row.find('.purchase-return-qty-input').val(Math.min(1, safeNumber($option.data('quantityAvailable'))).toFixed(0));
                    }
                } else {
                    $row.find('.purchase-return-original-label').text('0');
                    $row.find('.purchase-return-max-label').text('0');
                    $row.find('.purchase-return-qty-input').val('0').attr('max', 0);
                    $row.find('.purchase-return-rate-input, .purchase-return-discount-input, .purchase-return-discount-amount-input, .purchase-return-net-rate-input').val('0');
                }
            }

            updateQtyLimit($row);
            recalculateRow($row, $row.attr('data-pricing-mode') || 'percent');
        }

        function renderBatchOptions(rows, selectedBatchId) {
            var options = '<option value="">Choose batch</option>';

            (rows || []).forEach(function (row) {
                var batch = row.batch || ((row.batch_options || [])[0] || {});
                var optionId = batch.id || row.batch_id || '';
                var isSelected = String(selectedBatchId || '') === String(optionId);
                options += '<option value="' + escapeHtml(optionId) + '"' +
                    ' data-badge-class="' + escapeHtml(batch.badge_class || row.batch_badge_class || 'bg-success') + '"' +
                    ' data-badge-label="' + escapeHtml(batch.badge_label || row.batch_badge_label || 'Valid batch') + '"' +
                    ' data-quantity-available="' + escapeHtml(batch.quantity_available || row.max_returnable || 0) + '"' +
                    ' data-quantity-received="' + escapeHtml(batch.quantity_received || row.original_qty || 0) + '"' +
                    ' data-purchase-price="' + escapeHtml(batch.purchase_price || row.rate || 0) + '"' +
                    (isSelected ? ' selected' : '') +
                    '>' + escapeHtml(batch.text || row.batch_no || 'Batch') + '</option>';
            });

            return options;
        }

        function createManualRow() {
            var nextIndex = parseInt($itemsTbody.data('next-index') || $itemsTbody.find('tr').not('.purchase-return-empty-row').length || 0, 10);
            var html = $manualRowTemplate.html()
                .replace(/__INDEX__/g, nextIndex)
                .replace(/__ROW__/g, nextIndex + 1);

            $itemsTbody.find('.purchase-return-empty-row').remove();
            $itemsTbody.append(html);
            $itemsTbody.data('next-index', nextIndex + 1);
            var $newRow = $itemsTbody.find('tr').last();
            window.initEnhancedSelects($newRow);
            updateRowNumbers();
            updateTotals();
            return $newRow;
        }

        function buildBillRow(row, rowIndex) {
            return '' +
                '<tr data-row-mode="bill" data-pricing-mode="percent" data-base-max-returnable="' + escapeHtml(row.max_returnable || 0) + '">' +
                    '<td class="purchase-return-row-number">' + (rowIndex + 1) + '</td>' +
                    '<td>' +
                        '<div class="fw-semibold">' + escapeHtml(row.product_name || '-') + '</div>' +
                        '<small class="text-muted d-block purchase-return-pricing-note">' + escapeHtml(purchaseReturnPricingNote(row)) + '</small>' +
                        '<input type="hidden" name="items[' + rowIndex + '][purchase_item_id]" value="' + escapeHtml(row.purchase_item_id || '') + '">' +
                        '<input type="hidden" name="items[' + rowIndex + '][product_id]" value="' + escapeHtml(row.product_id || '') + '">' +
                    '</td>' +
                    '<td>' +
                        '<div class="d-flex flex-column gap-1">' +
                            '<select name="items[' + rowIndex + '][batch_id]" class="form-select form-select-sm purchase-return-batch-select">' +
                                renderBatchOptions((row.batch_options || []).map(function (batch) {
                                    return { batch: batch };
                                }), row.selected_batch_id) +
                            '</select>' +
                            '<span class="badge purchase-return-batch-badge ' + escapeHtml(row.batch_badge_class || 'bg-light text-dark border') + '">' + escapeHtml(row.batch_badge_label || 'Select batch') + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td><span class="badge bg-light text-dark border purchase-return-original-label">' + escapeHtml(parseFloat(row.original_qty || 0).toFixed(0)) + '</span></td>' +
                    '<td><span class="badge bg-secondary purchase-return-returned-label">' + escapeHtml(parseFloat(row.already_returned || 0).toFixed(0)) + '</span></td>' +
                    '<td><span class="badge bg-info text-dark purchase-return-max-label">' + escapeHtml(parseFloat(row.max_returnable || 0).toFixed(0)) + '</span></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][return_qty]" class="form-control purchase-return-qty-input" min="0" max="' + escapeHtml(row.max_returnable || 0) + '" value="' + escapeHtml(row.return_qty || 0) + '"></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][rate]" class="form-control purchase-return-rate-input" min="0" step="0.01" value="' + escapeHtml(row.rate || 0) + '"></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][discount_percent]" class="form-control purchase-return-discount-input" min="0" max="100" step="0.01" value="' + escapeHtml(row.discount_percent || 0) + '"></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][discount_amount]" class="form-control purchase-return-discount-amount-input" min="0" step="0.01" value="' + escapeHtml(row.discount_amount || 0) + '"></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][net_rate]" class="form-control purchase-return-net-rate-input" min="0" step="0.01" value="' + escapeHtml(row.net_rate || row.rate || 0) + '"></td>' +
                    '<td><input type="text" name="items[' + rowIndex + '][return_amount]" class="form-control purchase-return-amount-input" value="' + escapeHtml(row.return_amount || 0) + '" readonly></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger removePurchaseReturnRow table-action-btn"><i class="fa-solid fa-trash"></i></button></td>' +
                '</tr>';
        }

        function renderBillRows(rows) {
            if (!(rows || []).length) {
                resetItemsTable('No returnable items found for this purchase bill.');
                return;
            }

            var html = '';
            rows.forEach(function (row, index) {
                html += buildBillRow(row, index);
            });

            $itemsTbody.html(html);
            $itemsTbody.data('next-index', rows.length);
            updateRowNumbers();
            $itemsTbody.find('tr').each(function () {
                applyBatchState($(this));
            });
        }

        function populateManualRowBatches($row, rows, preserveSelection) {
            var selectedBatchId = preserveSelection ? $row.find('.purchase-return-batch-select').val() : '';
            var $batchSelect = $row.find('.purchase-return-batch-select');
            var productName = $row.find('.purchase-return-product-select option:selected').text();

            if (!(rows || []).length) {
                $batchSelect.html('<option value="">No returnable batch found</option>').prop('disabled', true);
                $row.find('.purchase-return-product-note').text(productName ? productName + ' has no returnable batch for this supplier.' : '');
                $row.find('.purchase-return-batch-badge').attr('class', 'badge purchase-return-batch-badge bg-danger').text('No valid batch found');
                $row.find('.purchase-return-original-label').text('0');
                $row.find('.purchase-return-returned-label').text('0');
                $row.find('.purchase-return-max-label').text('0');
                $row.find('.purchase-return-qty-input').val('0').attr('max', 0);
                $row.find('.purchase-return-rate-input, .purchase-return-discount-input, .purchase-return-discount-amount-input, .purchase-return-net-rate-input').val('0');
                recalculateRow($row, 'percent');
                return;
            }

            $batchSelect.html(renderBatchOptions(rows, selectedBatchId)).prop('disabled', false);

            if (!$batchSelect.val() && rows.length > 0) {
                $batchSelect.prop('selectedIndex', 1);
            }

            $row.find('.purchase-return-product-note').text('');
            $row.find('.purchase-return-pricing-note').text(purchaseReturnPricingNote((rows || [])[0] || null));
            applyBatchState($row);
        }

        function loadSupplierPurchases(supplierId, selectedPurchaseId, preserveLoadedRows) {
            $purchaseSelect.empty().append('<option value="">Select purchase</option>').trigger('change.select2');

            if (!supplierId) {
                return;
            }

            $.get('{{ route('admin.purchase-returns.get-purchases') }}', { supplier_id: supplierId }, function (response) {
                (response || []).forEach(function (row) {
                    var option = new Option(row.text, row.id, false, String(selectedPurchaseId || '') === String(row.id));
                    $purchaseSelect.append(option);
                });

                if (selectedPurchaseId) {
                    $purchaseSelect.val(String(selectedPurchaseId)).trigger(preserveLoadedRows ? 'change.select2' : 'change');
                }
            });
        }

        function ensureManualModeHasRow() {
            if (!hasDataRows()) {
                createManualRow();
            }
        }

        function syncModeState() {
            var isManualMode = $modeInputs.filter(':checked').val() === 'product';

            if (isManualMode) {
                $purchaseSelect.val('').trigger('change');
                $purchaseSelect.prop('disabled', true).prop('required', false);
                $loadBillButton.prop('disabled', true);
                $addManualButton.prop('disabled', false);
                ensureManualModeHasRow();
            } else {
                $purchaseSelect.prop('disabled', false);
                $loadBillButton.prop('disabled', false);
                $addManualButton.prop('disabled', true);

                if (!hasDataRows()) {
                    resetItemsTable('Select a purchase bill to load returnable rows.');
                }
            }
        }

        function refreshBillRows() {
            var supplierId = $supplierSelect.val();
            var purchaseId = $purchaseSelect.val();

            if ($modeInputs.filter(':checked').val() !== 'bill') {
                return;
            }

            if (!supplierId) {
                resetItemsTable('Select supplier first to load bill rows.');
                return;
            }

            if (!purchaseId) {
                resetItemsTable('Select a purchase bill to load returnable rows.');
                return;
            }

            $itemsTbody.html(emptyMessage('Loading bill items...'));
            $.get('{{ route('admin.purchase-returns.get-items') }}', { purchase_id: purchaseId }, function (response) {
                renderBillRows(response || []);
            }).fail(function (xhr) {
                resetItemsTable('Could not load purchase bill items right now.');
                if (window.showNotification) {
                    window.showNotification((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load purchase bill items.', 'error');
                }
            });
        }

        $(document).on('click', '#purchaseReturnAddManualItem', function () {
            if ($modeInputs.filter(':checked').val() !== 'product') {
                return;
            }

            if (!$supplierSelect.val()) {
                if (window.showNotification) {
                    window.showNotification('Select supplier first.', 'warning');
                }
                return;
            }

            createManualRow();
        });

        $(document).on('click', '#purchaseReturnLoadBillItems', function () {
            refreshBillRows();
        });

        $(document).on('change', '#purchaseReturnSupplier', function () {
            loadSupplierPurchases($(this).val(), null, false);

            if ($modeInputs.filter(':checked').val() === 'product') {
                resetItemsTable('Add return rows for this supplier.');
                ensureManualModeHasRow();
                return;
            }

            resetItemsTable('Select a purchase bill to load returnable rows.');
        });

        $(document).on('change', '#purchaseReturnPurchase', function () {
            refreshBillRows();
        });

        $(document).on('change', 'input[name="return_mode"]', function () {
            if ($modeInputs.filter(':checked').val() === 'product') {
                resetItemsTable('Add return rows for this supplier.');
            } else {
                resetItemsTable('Select a purchase bill to load returnable rows.');
            }

            syncModeState();
        });

        $(document).on('change', '.purchase-return-product-select', function () {
            var $row = $(this).closest('tr');
            var supplierId = $supplierSelect.val();
            var productId = $(this).val();

            if (!supplierId || !productId) {
                populateManualRowBatches($row, [], false);
                return;
            }

            $.get('{{ route('admin.purchase-returns.get-batches') }}', {
                supplier_id: supplierId,
                product_id: productId
            }, function (response) {
                populateManualRowBatches($row, response || [], false);
            }).fail(function (xhr) {
                if (window.showNotification) {
                    window.showNotification((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load supplier batches.', 'error');
                }
            });
        });

        $(document).on('change', '.purchase-return-batch-select', function () {
            applyBatchState($(this).closest('tr'));
        });

        $(document).on('click', '.removePurchaseReturnRow', function () {
            var $row = $(this).closest('tr');
            var isManualMode = $modeInputs.filter(':checked').val() === 'product';

            if ($itemsTbody.find('tr').not('.purchase-return-empty-row').length === 1) {
                if (isManualMode) {
                    $row.remove();
                    ensureManualModeHasRow();
                } else {
                    resetItemsTable('Select a purchase bill to load returnable rows.');
                }

                updateTotals();
                return;
            }

            $row.remove();
            updateRowNumbers();
            updateTotals();
        });

        function purchaseReturnPricingMode($input, $row) {
            var mode = $row.attr('data-pricing-mode') || 'percent';

            if ($input.hasClass('purchase-return-discount-input')) {
                mode = 'percent';
            } else if ($input.hasClass('purchase-return-discount-amount-input')) {
                mode = 'amount';
            } else if ($input.hasClass('purchase-return-net-rate-input')) {
                mode = 'net';
            }

            return mode;
        }

        $(document).on('input', '.purchase-return-qty-input, .purchase-return-rate-input, .purchase-return-discount-input, .purchase-return-discount-amount-input, .purchase-return-net-rate-input', function () {
            var $row = $(this).closest('tr');
            var mode = purchaseReturnPricingMode($(this), $row);

            recalculateRow($row, mode, { preserveEditing: true });
        });

        $(document).on('change blur', '.purchase-return-qty-input, .purchase-return-rate-input, .purchase-return-discount-input, .purchase-return-discount-amount-input, .purchase-return-net-rate-input', function () {
            var $row = $(this).closest('tr');
            var mode = purchaseReturnPricingMode($(this), $row);

            recalculateRow($row, mode);
        });

        if ($supplierSelect.val()) {
            loadSupplierPurchases($supplierSelect.val(), initialSelectedPurchaseId || null, preserveInitialBillRows);
        }

        updateRowNumbers();
        $itemsTbody.find('tr').not('.purchase-return-empty-row').each(function () {
            applyBatchState($(this));
        });
        syncModeState();
        updateTotals();
    });
</script>
