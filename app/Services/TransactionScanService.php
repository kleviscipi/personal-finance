<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TransactionScanService
{
    public function __construct(private CurrencyService $currencyService) {}

    public function isConfigured(): bool
    {
        return filled((string) config('services.openai.api_key'));
    }

    public function scanDraft(UploadedFile $document, string $baseCurrency): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Invoice scan is not configured yet. Add OPENAI_API_KEY to enable it.');
        }

        $supportedCurrencies = array_keys($this->currencyService->getSupportedCurrencies());

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'))
                ->withToken((string) config('services.openai.api_key'))
                ->acceptJson()
                ->timeout((int) config('services.openai.transaction_scan_timeout', 45))
                ->post('responses', $this->buildPayload($document, $baseCurrency, $supportedCurrencies))
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'error.message');

            throw new RuntimeException(
                $message ? "Invoice scan failed: {$message}" : 'Invoice scan failed. Please try again.',
                0,
                $exception
            );
        }

        $outputText = $this->extractOutputText($response);
        if (!$outputText) {
            throw new RuntimeException('Invoice scan did not return a usable draft.');
        }

        $parsed = json_decode($outputText, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Invoice scan returned an invalid draft.');
        }

        return $this->normalizeDraft($parsed, $document, $baseCurrency, $supportedCurrencies);
    }

    private function buildPayload(UploadedFile $document, string $baseCurrency, array $supportedCurrencies): array
    {
        return [
            'model' => (string) config('services.openai.transaction_scan_model', 'gpt-5-mini'),
            'store' => false,
            'input' => [
                [
                    'role' => 'developer',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->buildInstructions($baseCurrency, $supportedCurrencies),
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        $this->buildDocumentInput($document),
                        [
                            'type' => 'input_text',
                            'text' => 'Extract a transaction draft from this financial document. Return the JSON schema exactly.',
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'transaction_scan_draft',
                    'description' => 'Extracted transaction draft fields for a single invoice or receipt.',
                    'strict' => true,
                    'schema' => $this->schema($supportedCurrencies),
                ],
            ],
        ];
    }

    private function buildInstructions(string $baseCurrency, array $supportedCurrencies): string
    {
        $currencyList = implode(', ', $supportedCurrencies);

        return implode(' ', [
            'You extract transaction draft fields from a single invoice, receipt, bill, refund, or bank payment document.',
            'Prefer the grand total, amount due, or amount paid, not subtotals, taxes, or unit prices.',
            'Use transaction_type expense for normal purchases and bills, income for refunds or incoming customer payments, and transfer only when the document clearly represents moving money between accounts.',
            'Never invent missing values.',
            'If the document is blurry, partially visible, unpaid, or ambiguous, still return your best extraction and explain the uncertainty in warnings.',
            "Supported currencies are {$currencyList}; if the currency is unclear or unsupported, return null for currency and add a warning.",
            'Dates must use YYYY-MM-DD.',
            'payment_method must be one of cash, card, bank_transfer, mobile_wallet, other, or unknown.',
            'description should be short and useful for a transaction list.',
            "The account base currency is {$baseCurrency}.",
        ]);
    }

    private function buildDocumentInput(UploadedFile $document): array
    {
        $mimeType = $document->getMimeType() ?: $document->getClientMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($document->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded invoice file.');
        }

        $encoded = base64_encode($contents);

        if (str_starts_with($mimeType, 'image/')) {
            return [
                'type' => 'input_image',
                'image_url' => "data:{$mimeType};base64,{$encoded}",
                'detail' => 'high',
            ];
        }

        return [
            'type' => 'input_file',
            'filename' => $document->getClientOriginalName(),
            'file_data' => $encoded,
        ];
    }

    private function schema(array $supportedCurrencies): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'document_kind',
                'transaction_type',
                'merchant',
                'amount',
                'currency',
                'date',
                'description',
                'payment_method',
                'confidence',
                'warnings',
            ],
            'properties' => [
                'document_kind' => [
                    'type' => 'string',
                    'enum' => ['receipt', 'invoice', 'bill', 'refund', 'statement', 'unknown'],
                ],
                'transaction_type' => [
                    'type' => 'string',
                    'enum' => ['expense', 'income', 'transfer', 'unknown'],
                ],
                'merchant' => [
                    'anyOf' => [
                        ['type' => 'string'],
                        ['type' => 'null'],
                    ],
                ],
                'amount' => [
                    'anyOf' => [
                        ['type' => 'string', 'pattern' => '^[0-9]+(\\.[0-9]{1,4})?$'],
                        ['type' => 'null'],
                    ],
                ],
                'currency' => [
                    'anyOf' => [
                        ['type' => 'string', 'enum' => $supportedCurrencies],
                        ['type' => 'null'],
                    ],
                ],
                'date' => [
                    'anyOf' => [
                        ['type' => 'string', 'format' => 'date'],
                        ['type' => 'null'],
                    ],
                ],
                'description' => [
                    'anyOf' => [
                        ['type' => 'string'],
                        ['type' => 'null'],
                    ],
                ],
                'payment_method' => [
                    'type' => 'string',
                    'enum' => ['cash', 'card', 'bank_transfer', 'mobile_wallet', 'other', 'unknown'],
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'warnings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    private function extractOutputText(array $response): ?string
    {
        $outputText = data_get($response, 'output_text');
        if (is_string($outputText) && trim($outputText) !== '') {
            return $outputText;
        }

        foreach ((array) data_get($response, 'output', []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'refusal') {
                    throw new RuntimeException($content['refusal'] ?? 'The document could not be parsed.');
                }

                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    private function normalizeDraft(
        array $parsed,
        UploadedFile $document,
        string $baseCurrency,
        array $supportedCurrencies
    ): array {
        $transactionType = $parsed['transaction_type'] ?? null;
        $paymentMethod = $parsed['payment_method'] ?? null;
        $currency = $parsed['currency'] ?? null;

        $warnings = collect($parsed['warnings'] ?? [])
            ->map(fn ($warning) => $this->normalizeString($warning))
            ->filter()
            ->values()
            ->all();

        if ($currency === null) {
            $warnings[] = 'Currency could not be confirmed. Review it before saving.';
        }

        return [
            'draft' => [
                'type' => in_array($transactionType, ['expense', 'income', 'transfer'], true) ? $transactionType : null,
                'amount' => $parsed['amount'] ?? null,
                'currency' => in_array($currency, $supportedCurrencies, true) ? $currency : null,
                'date' => $parsed['date'] ?? null,
                'description' => $this->normalizeString($parsed['description'] ?? null)
                    ?: $this->normalizeString($parsed['merchant'] ?? null),
                'payment_method' => in_array(
                    $paymentMethod,
                    ['cash', 'card', 'bank_transfer', 'mobile_wallet', 'other'],
                    true
                ) ? $paymentMethod : null,
            ],
            'document' => [
                'kind' => $parsed['document_kind'] ?? 'unknown',
                'merchant' => $this->normalizeString($parsed['merchant'] ?? null),
                'confidence' => round((float) ($parsed['confidence'] ?? 0), 2),
                'warnings' => array_values(array_unique($warnings)),
                'source_name' => $document->getClientOriginalName(),
                'base_currency' => $baseCurrency,
            ],
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $normalized !== '' ? $normalized : null;
    }
}
