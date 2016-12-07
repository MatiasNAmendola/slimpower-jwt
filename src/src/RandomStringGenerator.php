<?php

/*
 * This file is part of Slim JSON Web Token Authentication middleware
 *
 * JSON Web Token implementation, based on this spec:
 * http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
 *
 * PHP version 5.3
 *
 * @category    Authentication
 * @package     SlimPower
 * @subpackage  JWT
 * @author      Matias Nahuel Améndola <soporte.esolutions@gmail.com>
 * @link        https://github.com/matiasnamendola/slimpower-jwt
 * @license     http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright   2016
 * 
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace SlimPower\JWT;

class RandomStringGenerator {

    /** @var string */
    protected $alphabet;

    /** @var int */
    protected $alphabetLength;

    /**
     * @param string $alphabet
     */
    public function __construct($alphabet = '') {
        if ('' !== $alphabet) {
            $this->setAlphabet($alphabet);
        } else {
            $this->setAlphabet(
                    implode(range('a', 'z'))
                    . implode(range('A', 'Z'))
                    . implode(range(0, 9))
            );
        }
    }

    /**
     * Generate basic token
     * @param int $tokenLength Set token length.
     * @return string
     */
    public static function generateBasic($tokenLength = 32) {
        // Create new instance of generator class.
        $generator = new RandomStringGenerator();

        // Call method to generate random string.
        $token = $generator->generate($tokenLength);

        return $token;
    }

    /**
     * Generate token with custom alphabet
     * @param string $customAlphabet Set custom alphabet
     * @param int $tokenLength Set token length.
     * @return string
     */
    public static function generateWithCustomAlphabet($customAlphabet = '0123456789ABCDEF', $tokenLength = 32) {

        // Set initial alphabet.
        $generator = new RandomStringGenerator($customAlphabet);

        // Change alphabet whenever needed.
        //$generator->setAlphabet($customAlphabet);
        // Call method to generate random string.
        $token = $generator->generate($tokenLength);

        return $token;
    }

    /**
     * @param string $alphabet
     */
    public function setAlphabet($alphabet) {
        $this->alphabet = $alphabet;
        $this->alphabetLength = strlen($alphabet);
    }

    /**
     * @param int $length
     * @return string
     */
    public function generate($length) {
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomKey = $this->getRandomInteger(0, $this->alphabetLength);
            $token .= $this->alphabet[$randomKey];
        }

        return $token;
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function getRandomInteger($min, $max) {
        $range = ($max - $min);

        if ($range < 0) {
            // Not so random...
            return $min;
        }

        $log = log($range, 2);

        // Length in bytes.
        $bytes = (int) ($log / 8) + 1;

        // Length in bits.
        $bits = (int) $log + 1;

        // Set all lower bits to 1.
        $filter = (int) (1 << $bits) - 1;

        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));

            // Discard irrelevant bits.
            $rnd = $rnd & $filter;
        } while ($rnd >= $range);

        return ($min + $rnd);
    }

}
