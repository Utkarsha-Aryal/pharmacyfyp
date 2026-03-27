<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->reference }} Print</title>
    <link href="{{ asset('backpanel/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backpanel/assets/css/custom.css') }}" rel="stylesheet">
</head>
<body>
    <div class="invoice-sheet">
        <div class="invoice-sheet-toolbar">
            <a href="{{ route('admin.sales.show', $invoice) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <button type="button" class="btn btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Now
            </button>
            <a href="{{ route('admin.sales.pdf', $invoice) }}" class="btn btn-pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
        </div>

        @include('backend.sales.partials.invoice-sheet', ['invoice' => $invoice, 'company' => $company])
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
</body>
</html>
