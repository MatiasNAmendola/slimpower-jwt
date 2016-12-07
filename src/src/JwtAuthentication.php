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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SlimPower\JWT\JWT;

class JwtAuthentication extends \Slim\Middleware {

    /**
     * AuthenticatorInterface
     * @var \SlimPower\JWT\AuthenticatorInterface
     */
    protected $authenticationInterface = null;
    protected $logger;
    protected $message; /* Last error message. */
    private $options = array(
        "secure" => true,
        "relaxed" => array("localhost", "127.0.0.1"),
        "environment" => "HTTP_AUTHORIZATION",
        "cookie" => "token",
        "path" => null,
        "callback" => null,
        "error" => null,
        "warningPaths" => null
    );

    /**
     * Create a new JwtAuthentication Instance
     * @param \SlimPower\JWT\AuthenticatorInterface $authenticationInterface
     * @param array $options
     */
    public function __construct(AuthenticatorInterface $authenticationInterface, $options = array()) {
        /* Setup stack for rules */
        $this->rules = new \SplStack;

        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);

        /* If nothing was passed in options add default rules. */
        if (!isset($options["rules"])) {
            $this->addRule(new Authentication\RequestMethodRule(array(
                "passthrough" => array("OPTIONS")
            )));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== ($this->options["path"])) {
            $this->addRule(new Authentication\RequestPathRule(array(
                "path" => $this->options["path"]
            )));
        }

        $this->authenticationInterface = $authenticationInterface;
    }

    /**
     * Valid JWT from AuthenticationInterface
     * @param string $token JWT.
     * @return bool
     */
    private function validFromAuthInterface($token) {
        $this->authenticationInterface->setToken($token);
        $this->authenticationInterface->validToken();

        if ($this->authenticationInterface->hasError()) {
            //$errCode = $this->authenticationInterface->getErrorCode();
            $this->message = $this->authenticationInterface->getErrorMessage();
            $this->log(LogLevel::WARNING, $this->message, array($token));
            return false;
        } else {
            return true;
        }
    }

    /**
     * Call the middleware
     */
    public function call() {
        $environment = $this->app->environment;
        $scheme = $environment["slim.url_scheme"];

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate()) {
            $this->next->call();
            return;
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($environment["SERVER_NAME"], $this->options["relaxed"])) {
                $message = sprintf(
                        "Insecure use of middleware over %s denied by configuration.", strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        $uri = $this->app->request->getResourceUri();

        $freePass = false;

        /* If request path is matches warningPaths should not authenticate. */
        foreach ((array) $this->options["warningPaths"] as $warningPaths) {
            $warningPaths = rtrim($warningPaths, "/");
            if (!!preg_match("@^{$warningPaths}(/.*)?$@", $uri)) {
                $freePass = true;
            }
        }

        /* If token cannot be found return with 401 Unauthorized. */
        if ((false === $token = $this->fetchToken()) && !$freePass) {
            $this->app->response->status(401);
            $this->error(array(
                "message" => $this->message
            ));
            return;
        }

        if (false === $token && $freePass) {
            $this->next->call();
            return;
        }

        if (!$this->validFromAuthInterface($token)) {
            $this->app->response->status(401);
            $this->error(array(
                "message" => $this->message
            ));
            return;
        }

        /* If token cannot be decoded return with 401 Unauthorized. */
        if (false === $decoded = $this->decodeToken($token)) {
            $this->app->response->status(401);
            $this->error(array(
                "message" => $this->message
            ));
            return;
        }

        /* If callback returns false return with 401 Unauthorized. */
        if (is_callable($this->options["callback"])) {
            $params = array("decoded" => $decoded, "app" => $this->app);
            if (false === $this->options["callback"]($params)) {
                $this->app->response->status(401);
                $this->error(array(
                    "message" => "Callback returned false"
                ));
                return;
            }
        }

        /* Everything ok, add custom property! */
        $this->app->jwtenc = $token;

        /* Everything ok, call next middleware. */
        $this->next->call();
    }

    /**
     * Check if middleware should authenticate
     *
     * @return boolean True if middleware should authenticate.
     */
    public function shouldAuthenticate() {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($this->app)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Call the error handler if it exists
     *
     * @return void
     */
    public function error($params) {
        if (is_callable($this->options["error"])) {
            return $this->options["error"]($params);
        }
    }

    /**
     * Fetch the access token
     *
     * @return string|null Base64 encoded JSON Web Token or null if not found.
     */
    public function fetchToken() {
        /* If using PHP in CGI mode and non standard environment */
        if (isset($_SERVER[$this->options["environment"]])) {
            $message = "Using token from environent";
            $header = $_SERVER[$this->options["environment"]];
        } else {
            $message = "Using token from request header";
            $header = $this->app->request->headers("Authorization");
        }
        if (preg_match("/Bearer\s+(.*)$/i", $header, $matches)) {
            $this->log(LogLevel::DEBUG, $message);
            return $matches[1];
        }

        /* Bearer not found, try a cookie. */
        if ($this->app->getCookie($this->options["cookie"])) {
            $this->log(LogLevel::DEBUG, "Using token from cookie");
            return $this->app->getCookie($this->options["cookie"]);
        };

        /* If everything fails log and return false. */
        $this->message = "Token not found";
        $this->log(LogLevel::WARNING, $this->message);
        return false;
    }

    public function decodeToken($token) {
        try {
            return JWT::decode(
                            $token, $this->options["secret"], array("HS256", "HS512", "HS384", "RS256")
            );
        } catch (\Exception $exception) {
            $this->message = $exception->getMessage();
            $this->log(LogLevel::WARNING, $exception->getMessage(), array($token));
            return false;
        }
    }

    /**
     * Hydate options from given array
     *
     * @param array $data Array of options.
     * @return self
     */
    private function hydrate($data = array()) {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
        return $this;
    }

    /**
     * Get path where middleware is be binded to
     *
     * @return string
     */
    public function getPath() {
        return $this->options["path"];
    }

    /**
     * Set path where middleware should be binded to
     *
     * @return self
     */
    public function setPath($path) {
        $this->options["path"] = $path;
        return $this;
    }

    /**
     * Get the environment name where to search the token from
     *
     * @return string Name of environment variable.
     */
    public function getEnvironment() {
        return $this->options["environment"];
    }

    /**
     * Set the environment name where to search the token from
     *
     * @return self
     */
    public function setEnvironment($environment) {
        $this->options["environment"] = $environment;
        return $this;
    }

    /**
     * Get the cookie name where to search the token from
     *
     * @return string
     */
    public function getCookie() {
        return $this->options["cookie"];
    }

    /**
     * Set the cookie name where to search the token from
     *
     * @return self
     */
    public function setCookie($cookie) {
        $this->options["cookie"] = $cookie;
        return $this;
    }

    /**
     * Get the secure flag
     *
     * @return string
     */
    public function getSecure() {
        return $this->options["secure"];
    }

    /**
     * Set the secure flag
     *
     * @return self
     */
    public function setSecure($secure) {
        $this->options["secure"] = !!$secure;
        return $this;
    }

    /**
     * Get hosts where secure rule is relaxed
     *
     * @return string
     */
    public function getRelaxed() {
        return $this->options["relaxed"];
    }

    /**
     * Set hosts where secure rule is relaxed
     *
     * @return self
     */
    public function setRelaxed(array $relaxed) {
        $this->options["relaxed"] = $relaxed;
        return $this;
    }

    /**
     * Get the secret key
     *
     * @return string
     */
    public function getSecret() {
        return $this->options["secret"];
    }

    /**
     * Set the secret key
     *
     * @return self
     */
    public function setSecret($secret) {
        $this->options["secret"] = $secret;
        return $this;
    }

    /**
     * Get the callback
     *
     * @return string
     */
    public function getCallback() {
        return $this->options["callback"];
    }

    /**
     * Set the callback
     *
     * @return self
     */
    public function setCallback($callback) {
        $this->options["callback"] = $callback;
        return $this;
    }

    /**
     * Get the error handler
     *
     * @return string
     */
    public function getError() {
        return $this->options["error"];
    }

    /**
     * Set the error handler
     *
     * @return self
     */
    public function setError($error) {
        $this->options["error"] = $error;
        return $this;
    }

    /**
     * Get the rules stack
     *
     * @return \SplStack
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * Set all rules in the stack
     *
     * @return self
     */
    public function setRules(array $rules) {
        /* Clear the stack */
        unset($this->rules);
        $this->rules = new \SplStack;
        /* Add the rules */
        foreach ($rules as $callable) {
            $this->addRule($callable);
        }
        return $this;
    }

    /**
     * Add rule to the stack
     *
     * @param callable $callable Callable which returns a boolean.
     * @return self
     */
    public function addRule($callable) {
        $this->rules->push($callable);
        return $this;
    }

    /* Cannot use traits since PHP 5.3 should be supported */

    /**
     * Get the logger
     *
     * @return Psr\Log\LoggerInterface $logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * Set the logger
     *
     * @param Psr\Log\LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger = null) {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        if ($this->logger) {
            return $this->logger->log($level, $message, $context);
        }
    }

}
