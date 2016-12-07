<?php

/**
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

class JwtGenerator {

    /**
     * Basic Token validity
     * @var int
     */
    const BASIC_VALIDITY = 300; // 5 minutes.

    /**
     * SlimPower instance
     * @var \SlimPower\Slim\Slim 
     */
    protected $app = null;

    /**
     * Secret key
     * @var string
     */

    protected $tokenSecret = 'secret';

    /**
     * Token validity
     * @var int 
     */
    protected $tokenValidity = self::BASIC_VALIDITY;

    /**
     * Constructor
     * @param \SlimPower\Slim\Slim $app SlimPower instance
     */
    function __construct(\SlimPower\Slim\Slim $app) {
        $this->app = $app;
    }

    /**
     * Sets token secret key
     * @param string $tokenSecret Secret key.
     * @throws InvalidArgumentException
     */
    public function setTokenSecret($tokenSecret) {
        if (empty($tokenSecret)) {
            throw new \InvalidArgumentException("Must enter token secret key!");
        }

        $this->tokenSecret = $tokenSecret;
    }

    /**
     * Sets token validity
     * @param int $tokenValidity Token validity.
     */
    public function setTokenValidity($tokenValidity) {
        $tknVal = intval($tokenValidity);

        if (empty($tknVal)) {
            $tknVal = self::BASIC_VALIDITY;
        }

        $this->tokenValidity = intval($tknVal);
    }

    /**
     * Gets token secret key
     * @return string
     */
    public function getTokenSecret() {
        return $this->tokenSecret;
    }

    /**
     * Gets token validity
     * @return int
     */
    public function getTokenValidity() {
        return $this->tokenValidity;
    }

    /**
     * Generate JWT
     * @param array $data Aditional data.
     * @return boolean|string JWT encoded
     */
    public function generateToken(array $data = array()) {
        // http://anexsoft.com/p/125/autenticacion-usando-json-web-token
        // http://anexsoft.com/p/126/implementacion-de-json-web-token-con-php
        // https://scotch.io/tutorials/the-anatomy-of-a-json-web-token

        if (empty($this->tokenSecret)) {
            return false;
        }

        $now = time();

        // Get request object
        $req = $this->app->request;
        // Get Basic URL
        $basicUrl = $req->getUrl();
        // Get URL
        $url = $basicUrl . $req->getRootUri();

        $req->getRootUri();

        $payload = array(
            // Date on which the token is generated.
            'iat' => $now,
            // Enable token after ...
            'nbf' => $now,
            // Date until the token is valid.
            'exp' => $now + $this->tokenValidity,
            'iss' => $basicUrl,
            'typ' => $url,
            'aud' => $req->getUserAgent(),
            // Unique id of the token.
            'jti' => RandomStringGenerator::generateBasic(40)
        );

        if (!empty($data) && is_array($data)) {
            // Para guardar información del usuario.
            $payload['data'] = $data;
        }

        $token = JWT::encode($payload, $this->tokenSecret);
        return $token;
    }

}
