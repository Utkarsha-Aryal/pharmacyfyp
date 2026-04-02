<?php

namespace App\Http\Controllers;

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

        $response = Http::timeout(120)
            ->acceptJson()
            ->asForm()
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => $apiKey,
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',
                'scale' => 'true',
                'detectOrientation' => 'true',
                'base64Image' => $base64,
                'filetype' => Str::lower($file->getClientOriginalExtension()),
            ]);

        if (!$response->ok()) {
            return back()->with('error', $this->ocrFailureMessage($response->json()) ?: 'OCR service could not process this file right now.');
        }

        $payload = $response->json();
        if (!empty($payload['IsErroredOnProcessing'])) {
            return back()->with('error', $this->ocrFailureMessage($payload) ?: 'OCR service could not process this file right now.');
        }

        $parsedResults = $payload['ParsedResults'] ?? [];
        $text = trim(collect($parsedResults)->pluck('ParsedText')->implode("\n"));

        if ($text === '') {
            return back()->with('error', $this->ocrFailureMessage($payload) ?: 'No text was extracted from this image.');
        }

        $lines = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();

        return back()->with('ocr_result', [
            'file_name' => $file->getClientOriginalName(),
            'text' => $text,
            'lines' => $lines,
        ])->with('success', 'OCR text extracted successfully.');
    }

    // Store the extracted OCR text so the purchase screen can use it as a draft note.
    public function draftPurchase(Request $request)
    {
        $validated = $request->validate([
            'ocr_text' => ['required', 'string'],
        ]);

        return redirect()
            ->route('admin.purchase.addpurchase')
            ->with('ocr_text', $validated['ocr_text'])
            ->with('success', 'OCR draft loaded into purchase entry.');
    }

    // Build one readable OCR error message because the free service can fail in a few different ways.
    private function ocrFailureMessage(?array $payload): ?string
    {
        if (empty($payload)) {
            return null;
        }

        $errors = $payload['ErrorMessage'] ?? $payload['ErrorDetails'] ?? null;

        if (is_array($errors)) {
            $errors = implode(' ', array_filter(array_map('strval', $errors)));
        }

        $errors = trim((string) $errors);

        return $errors !== '' ? $errors : null;
    }
}
