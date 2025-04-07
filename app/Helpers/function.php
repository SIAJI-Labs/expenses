<?php

/**
 * Generate randomg mix character
 * 
 * @param integer $length
 * @param boolean $ambiguous
 * @return string
 */
if(!(function_exists('generateRandomMixCharacter'))){
    function generateRandomMixCharacter($length = 8, $ambiguous = true)
    {
        $numbers = implode('', range(0, 9));
        $lowercase = implode('', range('a', 'z'));
        $uppercase = implode('', range('A', 'Z'));

        // Remove ambiguous characters if needed
        if (!$ambiguous) {
            // Remove "L" letter (possible conflict with I)
            $lowercase = str_replace('l', '', $lowercase);
            $uppercase = str_replace('L', '', $uppercase);
            // Remove "I" letter (possible conflict with L)
            $lowercase = str_replace('i', '', $lowercase);
            $uppercase = str_replace('I', '', $uppercase);
            // Remove "0" number (possible conflict with O)
            $numbers = str_replace('0', '', $numbers);
            // Remove "O" letter (possible conflict with 0)
            $lowercase = str_replace('o', '', $lowercase);
            $uppercase = str_replace('O', '', $uppercase);
        }

        // Ensure at least one character from each set
        $code = $numbers[random_int(0, strlen($numbers) - 1)]
            . $lowercase[random_int(0, strlen($lowercase) - 1)]
            . $uppercase[random_int(0, strlen($uppercase) - 1)];

        // Create a pool of all available characters
        $allChars = $numbers . $lowercase . $uppercase;

        // Fill the remaining characters
        for ($i = 3; $i < $length; $i++) {
            $code .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the final code to mix positions
        return str_shuffle($code);
    }
}