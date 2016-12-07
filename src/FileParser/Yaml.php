<?php

namespace SlimPower\Config\FileParser;

use Exception;
use Symfony\Component\Yaml\Yaml as YamlParser;
use SlimPower\Config\Exception\ParseException;

/**
 * YAML file parser
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
class Yaml implements FileParserInterface {

    /**
     * {@inheritDoc}
     * Loads a YAML/YML file as an array
     *
     * @throws ParseException If If there is an error parsing the YAML file
     */
    public function parse($path) {
        try {
            $data = YamlParser::parse(file_get_contents($path));
        } catch (Exception $exception) {
            throw new ParseException(
            array(
        'message' => 'Error parsing YAML file',
        'exception' => $exception,
            )
            );
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedExtensions() {
        return array('yaml', 'yml');
    }

}
