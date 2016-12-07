<?php

namespace SlimPower\Config;

use SlimPower\Config\Exception\FileNotFoundException;
use SlimPower\Config\Exception\UnsupportedFormatException;
use SlimPower\Config\Exception\EmptyDirectoryException;

/**
 * Config
 *
 * @package    SlimPower
 * @subpackage Config
 * @author     Matias Nahuel Améndola <soporte.esolutions@gmail.com>
 * @link       https://github.com/MatiasNAmendola/slimpower-config
 * @license    https://github.com/MatiasNAmendola/slimpower-config/blob/master/LICENSE.md
 * @since      0.0.1
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
class Config extends AbstractConfig {

    /**
     * All file formats supported by Config
     *
     * @var array
     */
    private $supportedFileParsers = array(
        'SlimPower\Config\FileParser\Php',
        'SlimPower\Config\FileParser\Ini',
        'SlimPower\Config\FileParser\Json',
        'SlimPower\Config\FileParser\Xml',
        'SlimPower\Config\FileParser\Yaml'
    );

    /**
     * Static method for loading a Config instance.
     *
     * @param  string|array $path
     *
     * @return Config
     */
    public static function load($path) {
        return new static($path);
    }

    /**
     * Loads a supported configuration file format.
     *
     * @param  string|array $path
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     */
    public function __construct($path) {
        $paths = $this->getValidPath($path);
        $this->data = array();

        foreach ($paths as $path) {

            // Get file information
            $info = pathinfo($path);
            $parts = explode('.', $info['basename']);
            $extension = array_pop($parts);
            
            if ($extension === 'dist') {
                $extension = array_pop($parts);
            }
            
            $parser = $this->getParser($extension);

            // Try and load file
            $this->data = array_replace_recursive($this->data, (array) $parser->parse($path));
        }

        parent::__construct($this->data);
    }

    /**
     * Gets a parser for a given file extension
     *
     * @param  string $extension
     *
     * @return SlimPower\Config\FileParser\FileParserInterface
     *
     * @throws UnsupportedFormatException If `$path` is an unsupported file format
     */
    private function getParser($extension) {
        $parser = null;

        foreach ($this->supportedFileParsers as $fileParser) {
            $tempParser = new $fileParser;

            if (in_array($extension, $tempParser->getSupportedExtensions($extension))) {
                $parser = $tempParser;
                continue;
            }
        }

        // If none exist, then throw an exception
        if ($parser === null) {
            throw new UnsupportedFormatException('Unsupported configuration format');
        }

        return $parser;
    }

    /**
     * Gets an array of paths
     *
     * @param  array $path
     *
     * @return array
     *
     * @throws FileNotFoundException   If a file is not found at `$path`
     */
    private function getPathFromArray($path) {
        $paths = array();

        foreach ($path as $unverifiedPath) {
            try {
                // Check if `$unverifiedPath` is optional
                // If it exists, then it's added to the list
                // If it doesn't, it throws an exception which we catch
                if ($unverifiedPath[0] !== '?') {
                    $paths = array_merge($paths, $this->getValidPath($unverifiedPath));
                    continue;
                }
                $optionalPath = ltrim($unverifiedPath, '?');
                $paths = array_merge($paths, $this->getValidPath($optionalPath));
            } catch (FileNotFoundException $e) {
                // If `$unverifiedPath` is optional, then skip it
                if ($unverifiedPath[0] === '?') {
                    continue;
                }
                // Otherwise rethrow the exception
                throw $e;
            }
        }

        return $paths;
    }

    /**
     * Checks `$path` to see if it is either an array, a directory, or a file
     *
     * @param  string|array $path
     *
     * @return array
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     *
     * @throws FileNotFoundException   If a file is not found at `$path`
     */
    private function getValidPath($path) {
        // If `$path` is array
        if (is_array($path)) {
            return $this->getPathFromArray($path);
        }

        // If `$path` is a directory
        if (is_dir($path)) {
            $paths = glob($path . '/*.*');
            if (empty($paths)) {
                throw new EmptyDirectoryException("Configuration directory: [$path] is empty");
            }
            return $paths;
        }

        // If `$path` is not a file, throw an exception
        if (!file_exists($path)) {
            throw new FileNotFoundException("Configuration file: [$path] cannot be found");
        }
        return array($path);
    }

}
