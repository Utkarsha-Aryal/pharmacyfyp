<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $response = Http::timeout(120)
            ->acceptJson()
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => $apiKey,
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',
            ]);

        if (!$response->ok()) {
            return back()->with('error', 'OCR service could not process this file right now.');
        }

        $payload = $response->json();
        $parsedResults = $payload['ParsedResults'] ?? [];
        $text = trim(collect($parsedResults)->pluck('ParsedText')->implode("\n"));

        if ($text === '') {
            return back()->with('error', 'No text was extracted from this image.');
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
}
