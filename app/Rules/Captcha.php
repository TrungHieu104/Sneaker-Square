<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ReCaptcha\ReCaptcha;

class Captcha implements ValidationRule
{
    /**
     * The secret comes from config, not env().
     *
     * `php artisan config:cache` stops the .env file from being read at all, so
     * env() returns null from then on — which meant a deployed site handed
     * reCAPTCHA an empty secret and rejected every login as a robot.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $recaptcha = new ReCaptcha((string) config('services.captcha.secret'));
        $response = $recaptcha->verify($value, request()->ip());

        if (! $response->isSuccess()) {
            $fail('Vui lòng xác nhận bạn không phải là người máy');
        }
    }
}
