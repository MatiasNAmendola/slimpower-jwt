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

abstract class AbstractAuthenticator implements AuthenticatorInterface {

    /**
     * Has error?
     * @var boolean 
     */
    private $error = false;

    /**
     * Error code
     * @var int
     */
    private $errorCode = 0;

    /**
     * Error message
     * @var int
     */
    private $errorMessage = '';

    /**
     * Interaction token
     * @var string 
     */
    protected $token = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->resetProperties();
    }

    /**
     * Reset properties
     */
    private function resetProperties() {
        $this->error = false;
        $this->errorCode = 0;
        $this->errorMessage = '';
        $this->token = '';
    }

    /**
     * Invoking custom authentication.
     * @param array $arguments Authentication arguments.
     * @return boolean
     */
    abstract public function __invoke(array $arguments);

    /**
     * Set token
     * @param string $token Token
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * Validate token
     * @return boolean
     */
    abstract public function validToken();

    /**
     * Set an error
     * @param int $errCode Error code.
     * @param string $errMsg Error message.
     */
    protected function setError($errCode, $errMsg) {
        $this->error = true;
        $this->errorCode = $errCode;
        $this->errorMessage = $errMsg;
    }

    /**
     * Get error code
     * @return int
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * Get error message
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Get interaction token
     * @return string
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Has error?
     * @return boolean
     */
    public function hasError() {
        return $this->error;
    }

}
