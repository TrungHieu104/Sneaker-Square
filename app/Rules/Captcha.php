<?php

namespace App\Rules;

use ReCaptcha\ReCaptcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Captcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $recaptcha = new ReCaptcha(env('CAPTCHA_SECRET'));
        $response = $recaptcha->verify($value, $_SERVER['REMOTE_ADDR']);

        if (!$response->isSuccess()) {
            $fail('Vui lòng xác nhận bạn không phải là người máy');
        }
    }

}
