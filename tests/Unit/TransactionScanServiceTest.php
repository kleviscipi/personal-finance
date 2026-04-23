<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use App\Services\TransactionScanService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransactionScanServiceTest extends TestCase
{
    public function test_scan_draft_sends_image_inputs_and_normalizes_the_response(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.openai.transaction_scan_model', 'gpt-5-mini');

        Http::fake(function (Request $request) {
            $this->assertSame('https://api.openai.com/v1/responses', (string) $request->url());
            $this->assertSame('Bearer test-key', $request->header('Authorization')[0]);
            $this->assertSame('gpt-5-mini', $request['model']);
            $this->assertFalse($request['store']);
            $this->assertSame('input_image', $request['input'][1]['content'][0]['type']);
            $this->assertStringStartsWith(
                'data:image/jpeg;base64,',
                $request['input'][1]['content'][0]['image_url']
            );

            return Http::response([
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'document_kind' => 'receipt',
                            'transaction_type' => 'expense',
                            'merchant' => 'Fuel Station',
                            'amount' => '6500.00',
                            'currency' => 'ALL',
                            'date' => '2026-04-23',
                            'description' => 'Fuel refill',
                            'payment_method' => 'card',
                            'confidence' => 0.93,
                            'warnings' => ['VAT line was partially obscured.'],
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]);
        });

        $service = new TransactionScanService(new CurrencyService());
        $result = $service->scanDraft(UploadedFile::fake()->image('receipt.jpg'), 'ALL');

        $this->assertSame('expense', $result['draft']['type']);
        $this->assertSame('6500.00', $result['draft']['amount']);
        $this->assertSame('ALL', $result['draft']['currency']);
        $this->assertSame('2026-04-23', $result['draft']['date']);
        $this->assertSame('Fuel refill', $result['draft']['description']);
        $this->assertSame('card', $result['draft']['payment_method']);
        $this->assertSame('receipt', $result['document']['kind']);
        $this->assertSame('Fuel Station', $result['document']['merchant']);
        $this->assertSame(0.93, $result['document']['confidence']);
        $this->assertSame(['VAT line was partially obscured.'], $result['document']['warnings']);
    }

    public function test_scan_draft_sends_pdf_inputs_as_files(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');

        Http::fake(function (Request $request) {
            $this->assertSame('input_file', $request['input'][1]['content'][0]['type']);
            $this->assertSame('invoice.pdf', $request['input'][1]['content'][0]['filename']);
            $this->assertNotEmpty($request['input'][1]['content'][0]['file_data']);

            return Http::response([
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'document_kind' => 'invoice',
                            'transaction_type' => 'expense',
                            'merchant' => 'City Gym',
                            'amount' => '2500.00',
                            'currency' => null,
                            'date' => '2026-04-01',
                            'description' => null,
                            'payment_method' => 'unknown',
                            'confidence' => 0.67,
                            'warnings' => ['Currency was not visible on the PDF.'],
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]);
        });

        $service = new TransactionScanService(new CurrencyService());
        $result = $service->scanDraft(
            UploadedFile::fake()->createWithContent('invoice.pdf', '%PDF-1.4 fake invoice'),
            'ALL'
        );

        $this->assertSame('City Gym', $result['draft']['description']);
        $this->assertNull($result['draft']['currency']);
        $this->assertNull($result['draft']['payment_method']);
        $this->assertContains('Currency was not visible on the PDF.', $result['document']['warnings']);
        $this->assertContains('Currency could not be confirmed. Review it before saving.', $result['document']['warnings']);
    }
}
