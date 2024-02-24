<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use JetBrains\PhpStorm\ArrayShape;

class CaptchaService
{
    /**
     * Generates the captcha to show on the page.
     *
     * @return array
     */
    #[ArrayShape(['image' => "string", 'prompt' => 'string', 'token' => "int"])]
    public function generateCaptcha(): array
    {
        $token = now()->timestamp;
        $image = $this->generateCaptchaImage($token);
        $prompt = $this->generateCaptchaPrompt($token);
        return [
            'image' => $image,
            'prompt' => $prompt,
            'token' => $token,
        ];
    }

    /**
     * @param int $token
     * @return array
     *
     * Base on the token return the letters to be written in the captcha image.
     *
     */
    #[ArrayShape(['red' => "string", 'green' => "string", 'blue' => "string", "color" => "string"])]
    public function generateCaptchaDefinition(int $token): array
    {
        // Generate a hash from the token to get randomized input
        $hash = hash_hmac('sha256', $token, config('app.key'));
        $offset = 8; // Skip the first 8 characters of the hash as they are not random
        $red_count = 2 + ord($hash[$offset++]) % 4;
        $green_count = 2 + ord($hash[$offset++]) % 4;
        $blue_count = 2 + ord($hash[$offset++]) % 4;
        $red_letters = substr($hash, $offset, $red_count);
        $offset += $red_count;
        $green_letters = substr($hash, 3 + $red_count, $green_count);
        $offset += $green_count;
        $blue_letters = substr($hash, 3 + $red_count + $green_count, $blue_count);
        $definition = [
            'red' => '',
            'green' => '',
            'blue' => ''
        ];
        for ($i = 0; $i < $red_count; $i++) {
            $definition['red'] .= chr(ord('A') + ord($red_letters[$i]) % 26);
        }
        for ($i = 0; $i < $green_count; $i++) {
            $definition['green'] .= chr(ord('A') + ord($green_letters[$i]) % 26);
        }
        for ($i = 0; $i < $blue_count; $i++) {
            $definition['blue'] .= chr(ord('A') + ord($blue_letters[$i]) % 26);
        }
        // which letters should be returned
        $definition['color'] = ['red', 'green', 'blue'][ord($hash[$offset]) % 3];
        return $definition;
    }

    /**
     * The text to prompt the user
     *
     * @param int $token
     * @return string
     */
    public function generateCaptchaPrompt(int $token): string
    {
        $definition = $this->generateCaptchaDefinition($token);
        return __("Please note all :color letters from the CAPTCHA.", [
            'color' => __($definition['color'])
        ]);
    }

    /**
     * @param int $token
     * @return string
     *
     * Generate a captcha image based on the token.
     *
     */
    public function generateCaptchaImage(int $token): string
    {
        $width = 256;
        $height = 64;
        $font_size = 5;
        $img = imagecreate($width + $font_size, $height + $font_size);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        $red = imagecolorallocate($img, 255, 0, 0);
        $green = imagecolorallocate($img, 0, 255, 0);
        $blue = imagecolorallocate($img, 0, 0, 255);
        $colors = [
            'red' => $red,
            'green' => $green,
            'blue' => $blue
        ];
        $definition = $this->generateCaptchaDefinition($token);
        foreach ($colors as $color_name => $color) {
            $letters = $definition[$color_name];
            for ($i = 0; $i < strlen($letters); $i++) {
                $x = rand(0, $width - 32);
                $y = rand(0, $height - 24);
                $letter = $letters[$i];
                imagestring($img, $font_size, $x, $y, $letter, $color);
            }
        }
        ob_start();
        imagepng($img);
        $image_data = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($image_data);
    }

    /**
     * Check if the captcha is solved correctly.
     * @param int $token
     * @param string $solution
     * @return bool
     */
    public function isCaptchaSolved(int $token, string $solution): bool
    {
        $definition = $this->generateCaptchaDefinition($token);
        $solution = str_split(strtoupper($solution));
        $letters = str_split(strtoupper($definition[$definition['color']]));
        for ($i = 0; $i < count($letters); $i++) {
            $letter = $letters[$i];
            $index = array_search($letter, $solution);
            if ($index === false) {
                return false;
            }
            $solution[$index] = '';
        }
        return true;
    }

    /**
     * The user has solved the captcha for the current session.
     *
     * @return void
     */
    public function markSessionAsSolvedCaptcha(): void
    {
        session(['solved_captcha' => true]);
    }

    /**
     * Check if the user should be shown a captcha.
     *
     * @return bool
     */
    public function isCaptchaRequired(): bool
    {
        return auth()->guest() && session('solved_captcha') !== true;
    }

}
