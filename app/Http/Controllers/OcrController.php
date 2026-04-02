<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OcrController extends Controller
{
    // Show the invoice OCR page with any previous extraction result.
    public function index()
    {
        return view('ocr.index', [
            'ocrResult' => session('ocr_result'),
            'ocrDraft' => session('ocr_draft'),
        ]);
    }

    // Send the uploaded image to the free OCR service and keep the extracted text in session.
    public function extract(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $file = $validated['image'];
        $apiKey = env('OCR_SPACE_API_KEY', 'helloworld');

        $base64 = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

        $payload = null;
        $text = '';
        $errorMessage = null;

        foreach ([2, 1] as $engine) {
            $response = Http::timeout(120)
                ->acceptJson()
                ->asForm()
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $apiKey,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'OCREngine' => (string) $engine,
                    'scale' => 'true',
                    'detectOrientation' => 'true',
                    'base64Image' => $base64,
                    'filetype' => Str::lower($file->getClientOriginalExtension()),
                ]);

            $payload = $response->json();
            $rawBody = $response->body();

            if (!$response->ok() || !empty($payload['IsErroredOnProcessing'])) {
                $errorMessage = $this->ocrFailureMessage($payload ?: $rawBody) ?: $errorMessage;
                continue;
            }

            $parsedResults = $payload['ParsedResults'] ?? [];
            $text = trim(collect($parsedResults)->pluck('ParsedText')->implode("\n"));

            if ($text !== '') {
                break;
            }
        }

        $lines = collect(preg_split('/\r\n|\r|\n/', $text ?: ''))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();

        if ($text === '') {
            $analysis = [
                'document_type' => 'unknown',
                'invoice_no' => null,
                'invoice_date' => null,
                'supplier_id' => null,
                'supplier_name' => null,
                'total_amount' => null,
                'confidence' => 0,
                'bill_state' => 'manual_review',
                'next_action' => 'fill_manually',
                'match_count' => 0,
            ];

            $result = [
                'file_name' => $file->getClientOriginalName(),
                'text' => '',
                'lines' => [],
                'analysis' => $analysis,
                'matches' => [],
                'extraction_status' => 'failed',
                'failure_message' => $errorMessage ?: 'OCR could not read this image clearly. You can continue manually or try a clearer scan.',
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'type' => 'warning',
                    'message' => $result['failure_message'],
                    'data' => $result,
                ]);
            }

            return back()
                ->with('ocr_result', $result)
                ->with('warning', $result['failure_message']);
        }

        $analysis = $this->analyzeInvoiceText($text, $lines);
        $matches = $this->findMatchingPurchases($analysis);
        $analysis['bill_state'] = empty($matches)
            ? ($analysis['supplier_id'] || $analysis['invoice_no'] ? 'new_bill' : 'manual_review')
            : 'matched_bill';
        $analysis['next_action'] = empty($matches)
            ? ($analysis['supplier_id'] || $analysis['invoice_no'] ? 'create_new' : 'fill_manually')
            : 'open_existing';
        $analysis['match_count'] = count($matches);

        $result = [
            'file_name' => $file->getClientOriginalName(),
            'text' => $text,
            'lines' => $lines,
            'analysis' => $analysis,
            'matches' => $matches,
            'extraction_status' => 'success',
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'type' => 'success',
                'message' => 'OCR text extracted successfully.',
                'data' => $result,
            ]);
        }

        return back()->with('ocr_result', $result)->with('success', 'OCR text extracted successfully.');
    }

    // Store the extracted OCR text so the purchase screen can use it as a draft note.
    public function draftPurchase(Request $request)
    {
        $validated = $request->validate([
            'ocr_text' => ['required', 'string'],
            'ocr_summary' => ['nullable', 'string'],
            'selected_purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
        ]);

        $summary = null;

        if (!empty($validated['ocr_summary'])) {
            try {
                $summary = json_decode($validated['ocr_summary'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $th) {
                $summary = null;
            }
        }

        return redirect()
            ->route('admin.purchase.addpurchase')
            ->with('ocr_text', $validated['ocr_text'])
            ->with('ocr_draft', [
                'ocr_text' => $validated['ocr_text'],
                'summary' => $summary,
                'selected_purchase_id' => $validated['selected_purchase_id'] ?? null,
            ])
            ->with('success', 'OCR draft loaded into purchase entry.');
    }

    // Pull out the useful bill clues from the OCR text so the user can decide whether to create a new bill or review an existing one.
    private function analyzeInvoiceText(string $text, array $lines): array
    {
        $normalized = Str::lower($text);
        $supplier = $this->matchSupplier($normalized);
        $invoiceNo = $this->extractInvoiceNumber($lines, $normalized);
        $invoiceDate = $this->extractInvoiceDate($lines);
        $totalAmount = $this->extractTotalAmount($lines);
        $documentType = $this->detectDocumentType($normalized);

        $confidence = 0;
        $confidence += $invoiceNo ? 25 : 0;
        $confidence += $invoiceDate ? 20 : 0;
        $confidence += $supplier ? 25 : 0;
        $confidence += $totalAmount !== null ? 15 : 0;
        $confidence += $documentType !== 'unknown' ? 15 : 0;

        return [
            'document_type' => $documentType,
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate,
            'supplier_id' => $supplier['id'] ?? null,
            'supplier_name' => $supplier['name'] ?? null,
            'total_amount' => $totalAmount,
            'confidence' => min(100, $confidence),
        ];
    }

    // Try to match the OCR text to one of our stored suppliers.
    private function matchSupplier(string $normalizedText): ?array
    {
        $suppliers = Supplier::query()
            ->where('status', 'Y')
            ->get(['id', 'supplier_name'])
            ->map(function (Supplier $supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => trim((string) $supplier->supplier_name),
                ];
            });

        foreach ($suppliers as $supplier) {
            $needle = Str::lower($supplier['name']);

            if ($needle !== '' && Str::contains($normalizedText, $needle)) {
                return $supplier;
            }
        }

        return null;
    }

    // Read the invoice number from common OCR labels like "invoice no" or "bill no".
    private function extractInvoiceNumber(array $lines, string $normalizedText): ?string
    {
        foreach ($lines as $line) {
            $lineNormalized = Str::lower((string) $line);

            if (preg_match('/(?:invoice|bill|ref|reference)\s*(?:no\.?|number|#|:)?\s*([a-z0-9\-\/]+)/i', $line, $matches)) {
                return trim($matches[1]);
            }
        }

        if (preg_match('/(?:invoice|bill)\s*#?\s*([a-z0-9\-\/]+)/i', $normalizedText, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    // Try to find a valid invoice date from the OCR lines.
    private function extractInvoiceDate(array $lines): ?string
    {
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if (!preg_match('/(?:\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}\b|\b\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2}\b)/', $line, $matches)) {
                continue;
            }

            try {
                return Carbon::parse($matches[0])->format('Y-m-d');
            } catch (\Throwable $th) {
                continue;
            }
        }

        return null;
    }

    // Read the total amount from the last line that looks like the bill total.
    private function extractTotalAmount(array $lines): ?float
    {
        $candidates = collect($lines)
            ->reverse()
            ->filter(function ($line) {
                $line = Str::lower((string) $line);

                return Str::contains($line, ['grand total', 'net amount', 'total amount', 'bill amount', 'amount']);
            });

        foreach ($candidates as $line) {
            if (preg_match('/(\d[\d,]*\.?\d{0,2})\s*$/', (string) $line, $matches)) {
                return (float) str_replace(',', '', $matches[1]);
            }
        }

        return null;
    }

    // Guess the document type from the OCR text so the page can say what kind of bill this looks like.
    private function detectDocumentType(string $normalizedText): string
    {
        $patterns = [
            'purchase_invoice' => ['purchase invoice', 'tax invoice', 'invoice', 'bill'],
            'purchase_order' => ['purchase order', 'po no', 'p.o.'],
            'receipt' => ['receipt', 'payment receipt', 'cash memo'],
            'delivery_note' => ['delivery note', 'dispatch note', 'challan'],
        ];

        foreach ($patterns as $type => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($normalizedText, $needle)) {
                    return $type;
                }
            }
        }

        return 'unknown';
    }

    // Find any already saved purchase bills that look like the OCR document.
    private function findMatchingPurchases(array $analysis): array
    {
        $query = Purchase::query()
            ->with(['supplier', 'reference'])
            ->where('status', 'Y');

        if (!empty($analysis['supplier_id'])) {
            $query->where('supplier_id', $analysis['supplier_id']);
        }

        if (!empty($analysis['invoice_no'])) {
            $invoiceNo = trim((string) $analysis['invoice_no']);
            $query->where(function ($builder) use ($invoiceNo) {
                $builder->where('invoice_no', 'like', '%' . $invoiceNo . '%')
                    ->orWhereHas('reference', function ($referenceQuery) use ($invoiceNo) {
                        $referenceQuery->where('reference_no', 'like', '%' . $invoiceNo . '%');
                    });
            });
        }

        return $query
            ->latest('purchase_date')
            ->limit(5)
            ->get()
            ->map(function (Purchase $purchase) {
                return [
                    'id' => $purchase->id,
                    'reference_no' => $purchase->reference?->reference_no ?: ('PUR-' . $purchase->id),
                    'invoice_no' => $purchase->invoice_no ?: '-',
                    'supplier_name' => $purchase->supplier?->supplier_name ?? '-',
                    'purchase_date' => $purchase->purchase_date_show ?? '-',
                    'grand_total' => money_value($purchase->grand_total),
                    'paid_amount' => money_value($purchase->paid_amount),
                    'due_amount' => money_value($purchase->outstanding_amount),
                    'status' => $purchase->order_status_label,
                ];
            })
            ->values()
            ->all();
    }

    // Build one readable OCR error message because the free service can fail in a few different ways.
    private function ocrFailureMessage(array|string|null $payload): ?string
    {
        if (empty($payload)) {
            return null;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            } else {
                $payload = trim(strip_tags($payload));
                $payload = preg_replace('/\s+/', ' ', $payload);
                $payload = is_string($payload) ? trim($payload) : '';

                if ($payload === '') {
                    return null;
                }

                return Str::limit($payload, 180);
            }
        }

        $errors = $payload['ErrorMessage'] ?? $payload['ErrorDetails'] ?? null;

        if (is_array($errors)) {
            $errors = implode(' ', array_filter(array_map('strval', $errors)));
        }

        $errors = trim((string) $errors);

        return $errors !== '' ? $errors : null;
    }
}
