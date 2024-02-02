<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use JetBrains\PhpStorm\ArrayShape;

class CaptchaService
{
    /**
     * @param int $token
     * @return array
     *
     * Base on the token return the letters to be written in the captcha image.
     *
     */
    #[ArrayShape(['red' => "string", 'green' => "string", 'blue' => "string"])]
    public function generateCaptchaDefinition(int $token) : array {
        // Generate a hash from the token to get randomized input
        $hash = Hash::make($token);
        $offset = 8; // Skip the first 8 characters of the hash as they are not random
        $red_count = 2 + ord($hash[$offset++]) % 6;
        $green_count = 2 + ord($hash[$offset++]) % 6;
        $blue_count = 2 + ord($hash[$offset++]) % 6;
        $red_letters = substr($hash, $offset, $red_count);
        $offset+= $red_count;
        $green_letters = substr($hash, 3 + $red_count, $green_count);
        $offset+= $green_count;
        $blue_letters = substr($hash, 3 + $red_count + $green_count, $blue_count);
        $letters = [
            'red' => '',
            'green' => '',
            'blue' => ''
        ];
        for($i=0;$i<$red_count;$i++) {
            $letters['red'] .= chr(ord('A') + ord($red_letters[$i]) % 26);
        }
        for($i=0;$i<$green_count;$i++) {
            $letters['green'] .= chr(ord('A') + ord($green_letters[$i]) % 26);
        }
        for($i=0;$i<$blue_count;$i++) {
            $letters['blue'] .= chr(ord('A') + ord($blue_letters[$i]) % 26);
        }
        return $letters;
    }

    /**
     * @param int $token
     * @return string
     *
     * Generate a captcha image based on the token.
     *
     */
    public function generateCaptchaImage(int $token) : string {
        $width = 256;
        $height = 128;
        $font_size = 14;
        $img = imagecreate($width + $font_size,$height + $font_size);
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
        foreach ($definition as $color => $letters) {
            for($i=0;$i<strlen($letters);$i++) {
                $x = rand(0, $width - $font_size);
                $y = rand(0, $height - $font_size);
                $letter = $letters[$i];
                imagestring($img, $font_size, $x, $y, $letter, $colors[$color]);
            }
        }
        ob_start();
        imagepng($img);
        $image_data = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
}
