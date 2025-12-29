<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret');

        if (!$secret) {
            return;
        }

        if (!$value || !is_string($value)) {
            $fail('Please complete the reCAPTCHA challenge.');
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $value,
        ]);

        if (!$response->ok() || !($response->json('success') ?? false)) {
            $fail('reCAPTCHA verification failed. Please try again.');
        }
    }
}
