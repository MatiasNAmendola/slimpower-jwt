<?php

/**
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

use SlimPower\Slim\Libs\Net;

abstract class JwtManager extends JwtGenerator implements ManagerInterface {

    /**
     * Application's security scope
     * @var boolean 
     */
    protected $appSecure = false;

    /**
     * Token relaxed
     * @var array 
     */
    protected $tokenRelaxed = array();

    /**
     * Insecure paths (without interaction token scope)
     * @var array 
     */
    protected $insecurePaths = array();

    /**
     * Authenticator Interface
     * @var \SlimPower\JWT\AuthenticatorInterface
     */
    protected $iauth = null;

    /**
     * Insecure paths with posibility to take JWT
     * @var array 
     */
    protected $warningPaths = array();

    /**
     * Constructor
     * @param \SlimPower\Slim\Slim $app SlimPower instance
     * @param \SlimPower\JWT\AuthenticatorInterface $iauth Authenticator Interface
     */
    protected function __construct(\SlimPower\Slim\Slim $app, AuthenticatorInterface $iauth) {
        $this->setAppSecure();
        $this->setIauth($iauth);
        $this->buildTokenRelaxed();
        $this->buildInsecurePaths();

        $class = get_class($this); //get_called_class();     

        $app->container->singleton('jwtManager', function () use ($app, $iauth, $class) {
            return $class::getInstance($app, $iauth);
        });
    }

    /**
     * Set Authenticator Interface
     * @param \SlimPower\JWT\AuthenticatorInterface $iauth Authenticator Interface
     */
    private function setIauth(AuthenticatorInterface $iauth) {
        $this->iauth = $iauth;
    }

    /**
     * Set application's security scope
     */
    private function setAppSecure() {
        $this->appSecure = Net::isSecure();
    }

    /**
     * Get application's security scope
     * @return boolean
     */
    public function getAppSecure() {
        return $this->appSecure;
    }

    /**
     * Build token relaxed
     */
    private function buildTokenRelaxed() {
        $localhost = Net::getLocalHost();
        $localIP = Net::getLocalIP();

        $this->tokenRelaxed = array($localhost, $localIP);
    }

    /**
     * Add token relaxed
     * @param array $tokenRelaxed
     */
    public function addTokenRelaxed(array $tokenRelaxed = array()) {
        $relaxed = $this->tokenRelaxed;

        if (!empty($tokenRelaxed) && is_array($tokenRelaxed)) {
            $relaxed = array_merge($relaxed, $tokenRelaxed);
        }

        $this->tokenRelaxed = $relaxed;
    }

    /**
     * Get token relaxed
     * @return array
     */
    public function getTokenRelaxed() {
        return $this->tokenRelaxed;
    }

    /**
     * Build insecure paths (without interaction token scope)
     */
    private function buildInsecurePaths() {
        $this->insecurePaths = array("/auth", "/token");
    }

    /**
     * Add insecure paths (without interaction token scope)
     * @param array $insecurePaths
     */
    public function addInsecurePaths(array $insecurePaths = array()) {
        $paths = $this->insecurePaths;

        if (!empty($insecurePaths) && is_array($insecurePaths)) {
            $paths = array_merge($paths, $insecurePaths);
        }

        $this->insecurePaths = $paths;
    }

    /**
     * Get insecure paths (without interaction token scope)
     * @return array
     */
    public function getInsecurePaths() {
        return $this->insecurePaths;
    }

    /**
     * Set insecure paths with posibility to take JWT
     * @param array $warningPaths
     */
    public function setWarningPaths(array $warningPaths) {
        $this->warningPaths = $warningPaths;
    }

    /**
     * Get insecure paths with posibility to take JWT
     * @return array
     */
    public function getWarningPaths() {
        return $this->warningPaths;
    }

    /**
     * Start JWT security
     */
    public function start() {
        $this->addJWTAccess();
        $this->addHttpBasicAuthentication();
        $this->addCustomAuthentication();
    }

    /**
     * Add JWT interaction mode
     */
    private function addJWTAccess() {
        $app = $this->getApp();

        $app->add(new JwtAuthentication($this->iauth, array(
            "path" => "/",
            "secret" => $this->tokenSecret,
            "secure" => $this->appSecure,
            "warningPaths" => $this->warningPaths,
            "rules" => array(
                new Authentication\RequestPathRule(array(
                    "path" => "/",
                    "passthrough" => $this->insecurePaths
                        ))
            ),
            "relaxed" => $this->tokenRelaxed,
            "callback" => function ($options) use ($app) {
                $app->jwt = $options['decoded'];
            },
            "error" => function ($arguments) {
                $this->sendErrorResponse($arguments["message"]);
            }
        )));
    }

    /**
     * Send authentication response
     */
    public function sendAuthenticationResponse() {
        if ($this->iauth->hasError()) {
            $errCode = $this->iauth->getErrorCode();
            $errMsg = $this->iauth->getErrorMessage();
            $this->sendErrorResponse($errMsg, $errCode, 401);
            return;
        }

        $token = $this->iauth->getToken();
        $this->sendToken($token);
    }

    /**
     * Add http basic authentication
     */
    private function addHttpBasicAuthentication() {
        $app = $this->getApp();

        $app->add(new \SlimPower\BasicAuth\HttpBasicAuthentication(array(
            "path" => "/token",
            "realm" => "Protected",
            "secure" => $this->appSecure,
            "relaxed" => $this->tokenRelaxed,
            "environment" => "REDIRECT_HTTP_AUTHORIZATION",
            "error" => function ($arguments) {
                $this->sendErrorResponse($arguments["message"]);
            },
            "authenticator" => $this->iauth
        )));

        $app->get('/token(/)', function () use ($app) {
            $app->jwtManager->sendAuthenticationResponse();
        });
    }

    /**
     * Add custom authentication
     */
    private function addCustomAuthentication() {
        $app = $this->getApp();

        $app->get('/auth(/)', function () use ($app) {
            $app->jwtManager->getAuthorization();
        });
    }

    /**
     * Get authorization
     */
    public function getAuthorization() {
        $auth = $this->getUser();

        if (!$auth) {
            $this->sendErrorResponse("Incorrect User.", 1, 401);
            return;
        }

        $this->iauth->__invoke($auth);

        $this->sendAuthenticationResponse();
    }

    abstract protected function getUser();

    abstract protected function sendErrorResponse($errMsg, $errCode = 0, $status = 401);

    abstract protected function sendToken($token);
}
