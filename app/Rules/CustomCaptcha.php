<?php

namespace App\Rules;

use App\Helpers\AppHelper;
use App\Services\CaptchaService;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class CustomCaptcha implements ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $captcha_service = AppHelper::resolve(CaptchaService::class);
        if ($value === null) {
            if ($captcha_service->isCaptchaRequired()) {
                $fail('The captcha is required.');
                return;
            }
            // no captcha required and not provided => pass
            return ;
        }
        $token = intval($value['token']);
        $minutes = Carbon::createFromTimestamp($token)->diffInMinutes(now());
        if ($minutes > 60) {
            $fail( 'The captcha has expired.');
            return;
        }
        $solution = $value['solution'];
        if (!$captcha_service->isCaptchaSolved($token, $solution)) {
            $fail( 'CAPTCHA failed.');
        }
        $captcha_service->markSessionAsSolvedCaptcha();
    }
}
