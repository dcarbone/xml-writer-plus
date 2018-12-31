<?php

namespace DCarbone;

/*
    Copyright 2012-2018 Daniel Carbone (daniel.p.carbone@gmail.com)

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

        http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
 * Class XMLWriterPlus
 * @package DCarbone
 */
class XMLWriterPlus extends \XMLWriter
{
    /** @var string */
    protected $encoding;

    /** @var array */
    protected $nsArray = array();

    /** @var bool */
    protected $memory = false;

    /**
     * Destructor
     *
     * @link http://www.php.net/manual/en/function.xmlwriter-flush.php
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param string $prefix
     * @param string $uri
     */
    public function addNS($prefix, $uri)
    {
        $this->nsArray[$prefix] = $uri;
    }

    /**
     * @param string $prefix
     */
    public function removeNS($prefix)
    {
        unset($this->nsArray[$prefix]);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function hasNSPrefix($prefix)
    {
        return isset($this->nsArray[$prefix]) || array_key_exists($prefix, $this->nsArray);
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function hasNSUri($uri)
    {
        return in_array($uri, $this->nsArray, true);
    }

    /**
     * @return array
     */
    public function getNSArray()
    {
        return $this->nsArray;
    }

    /**
     * @param array $nsArray
     */
    public function setNSArray(array $nsArray)
    {
        $this->nsArray = $nsArray;
    }

    /**
     * @param string $prefix
     * @return string|bool
     */
    public function getNSUriFromPrefix($prefix)
    {
        if ($this->hasNSPrefix($prefix)) {
            return $this->nsArray[$prefix];
        }

        return false;
    }

    /**
     * @param string $uri
     * @return mixed
     */
    public function getNSPrefixFromUri($uri)
    {
        return array_search($uri, $this->nsArray, true);
    }

    /**
     * @return bool
     */
    public function openMemory()
    {
        $this->memory = true;
        return parent::openMemory();
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function openUri($uri)
    {
        $this->memory = false;
        return parent::openUri($uri);
    }

    /**
     * @param float $version
     * @param string $encoding
     * @param bool $standalone
     * @return bool|void
     */
    public function startDocument($version = 1.0, $encoding = 'UTF-8', $standalone = null)
    {
        $type = gettype($version);
        if ('double' === $type || 'integer' === $type || 'string' === $type) {
            $version = number_format((float)$version, 1);
        }

        $this->encoding = $encoding;
        parent::startDocument($version, $encoding, $standalone);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string|null $uri
     * @return bool
     */
    public function startAttributeNS($prefix, $name, $uri = null)
    {
        list($prefix, $uri) = $this->resolveNamespace($prefix, $uri);
        return parent::startAttributeNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string|null $uri
     * @param string|null $content
     * @return bool
     */
    public function writeAttributeNS($prefix, $name, $uri = null, $content = null)
    {
        list($prefix, $uri) = $this->resolveNamespace($prefix, $uri);
        return parent::writeAttributeNS($prefix, $name, $uri, $content);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string|null $uri
     * @return bool
     */
    public function startElementNS($prefix, $name, $uri = null)
    {
        list($prefix, $uri) = $this->resolveNamespace($prefix, $uri);
        return parent::startElementNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string|null $uri
     * @param string|null $content
     * @param array $attributes
     * @return bool
     */
    public function writeElementNS($prefix, $name, $uri = null, $content = null, array $attributes = [])
    {
        list($prefix, $uri) = $this->resolveNamespace($prefix, $uri);
        if (0 === count($attributes)) {
            return parent::writeElementNS($prefix, $name, $uri, $content);
        }
        if (!$this->startElementNS($prefix, $name)) {
            return false;
        }
        foreach ($attributes as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (false !== ($pos = strpos($k, ':'))) {
                if (!$this->writeAttributeNS(substr($k, 0, $pos), substr($k, $pos + 1), null, $v)) {
                    return false;
                }
            } elseif (!$this->writeAttribute($k, $v)) {
                return false;
            }
        }
        return $this->text($content)
            && $this->endElement((null === $content ? true : false));
    }

    /**
     * Write Text into Attribute or Element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-text.php
     *
     * @param string $text Value to write
     * @throws \InvalidArgumentException
     * @return  bool
     */
    public function text($text)
    {
        if (is_string($text) || settype($text, 'string') !== false) {
            return parent::text($this->encodeString($text));
        }
        throw new \InvalidArgumentException(get_class($this) . ':text - Cannot cast passed value to string (did you forget to define a __toString on your object?)');
    }

    /**
     * This is a helper method to fully write an element to an XML Document.  If you provide content, the element will
     * be fully closed (<element>{content}</element>).  Otherwise, it will be written as <element />
     *
     * You may also pass in an array of attributes to be applied to the element. There are 2 accepted form of array key:
     *
     * [
     *      "attr" => "value",          // will result in <element attr="value" />
     *      "prefix:attr" => "value"    // will result in <element prefix:attr="value" />
     * ]
     *
     * There is no way to pass in namespace URI's with this method, however.  If URI's are needed, you may seed them
     * using @see XMLWriterPlus::addNS()
     *
     * @param string $name
     * @param string|null $content
     * @param array $attributes
     * @return bool
     * @params array $attributes
     */
    public function writeElement($name, $content = null, array $attributes = [])
    {
        if (0 === count($attributes)) {
            return $this->startElement($name)
                && $this->text($content)
                && $this->endElement(($content === null ? true : false));
        }
        if (!$this->startElement($name)) {
            return false;
        }
        foreach ($attributes as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (false !== ($pos = strpos($k, ':'))) {
                if (!$this->writeAttributeNS(substr($k, 0, $pos), substr($k, $pos + 1), null, $v)) {
                    return false;
                }
            } elseif (!$this->writeAttribute($k, $v)) {
                return false;
            }
        }
        return $this->text($content)
            && $this->endElement((null === $content ? true : false));
    }

    /**
     * Write ending element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-full-end-element.php
     *
     * @param bool $full
     * @return bool
     */
    public function endElement($full = false)
    {
        if ($full) {
            return $this->fullEndElement();
        }

        return parent::endElement();
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|null $nsPrefix
     * @param string|null $nsUri
     * @return bool
     */
    public function writeCDataElement($name, $content, $nsPrefix = null, $nsUri = null)
    {
        if ($nsPrefix === null) {
            return $this->startElement($name)
                && $this->writeCdata($content)
                && $this->endElement(true);
        }

        return $this->writeElementNS($nsPrefix, $name, $nsUri)
            && $this->writeCdata($content)
            && $this->endElement(true);
    }

    /**
     * Append an integer index array of values to this XML document
     *
     * @param array $data
     * @param string $elementName
     * @param string|null $nsPrefix
     * @param string|null $nsUri
     * @return bool
     */
    public function appendList(array $data, $elementName, $nsPrefix = null, $nsUri = null)
    {
        if (null === $nsPrefix) {
            foreach ($data as $value) {
                $this->writeElement($elementName, $value);
            }
        } else {
            foreach ($data as $value) {
                $this->writeElementNS($nsPrefix, $elementName, $nsUri, $value);
            }
        }

        return true;
    }

    /**
     * Append an associative array or object to this XML document
     *
     * @param array|object $data
     * @param string|null $_previousKey
     * @return bool
     */
    public function appendHash($data, $_previousKey = null)
    {
        foreach ($data as $key => $value) {
            $this->appendHashData($key, $value, $_previousKey);
        }

        return false;
    }

    /**
     * @param bool $flush
     * @param bool $endDocument
     * @param null|int $sxeArgs
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSXEFromMemory($flush = false, $endDocument = false, $sxeArgs = null)
    {
        if ($this->memory === true) {
            if ($endDocument === true) {
                $this->endDocument();
            }

            try {
                if (null === $sxeArgs) {
                    if (defined('LIBXML_PARSEHUGE')) {
                        $sxeArgs = LIBXML_COMPACT | LIBXML_PARSEHUGE;
                    } else {
                        $sxeArgs = LIBXML_COMPACT;
                    }
                }

                return new \SimpleXMLElement($this->outputMemory((bool)$flush), $sxeArgs);
            } catch (\Exception $e) {
                if (libxml_get_last_error() !== false) {
                    throw new \Exception(get_class($this) . '::getSXEFromMemory - Error encountered: "' . libxml_get_last_error()->message . '"');
                } else {
                    throw new \Exception(get_class($this) . '::getSXEFromMemory - Error encountered: "' . $e->getMessage() . '"');
                }
            }
        }

        return null;
    }

    /**
     * @param bool $flush
     * @param bool $endDocument
     * @param float $version
     * @param string $encoding
     * @return \DOMDocument|null
     * @throws \Exception
     */
    public function getDOMFromMemory($flush = false, $endDocument = false, $version = 1.0, $encoding = 'UTF-8')
    {
        if ($this->memory === true) {
            if ($endDocument === true) {
                $this->endDocument();
            }

            try {
                $dom = new \DOMDocument($version, $encoding);
                $dom->loadXML($this->outputMemory((bool)$flush));

                return $dom;
            } catch (\Exception $e) {
                if (libxml_get_last_error() !== false) {
                    throw new \Exception(get_class($this) . '::getDOMFromMemory - Error encountered: "' . libxml_get_last_error()->message . '"');
                } else {
                    throw new \Exception(get_class($this) . '::getDOMFromMemory - Error encountered: "' . $e->getMessage() . '"');
                }
            }
        }

        return null;
    }

    /**
     * @param string|int $key
     * @param mixed $value
     * @param null|string $_previousKey
     */
    protected function appendHashData($key, $value, $_previousKey)
    {
        if (is_scalar($value)) {
            if (is_string($key) && false === ctype_digit($key)) {
                if (false === strpos($key, ':')) {
                    $this->writeElement($key, $value);
                } else {
                    $exp = explode(':', $key, 2);
                    $this->writeElementNS($exp[0], $exp[1], null, $value);
                }
            } else {
                if (is_numeric($key) && $_previousKey !== null && !is_numeric($_previousKey)) {
                    $this->writeElement($_previousKey, $value);
                } else {
                    $this->writeElement($key, $value);
                }
            }

            return;
        }

        if (is_numeric($key)) {
            foreach ($value as $k => $v) {
                $this->appendHashData($k, $v, $_previousKey);
            }
        } else {
            if (false !== strpos($key, ':')) {
                $exp = explode(':', $key, 2);
                $this->startElementNS($exp[0], $exp[1]);
                $this->appendHash($value, $key);
                $this->endElement(true);
            } else {
                $this->startElement($key);
                $this->appendHash($value, $key);
                $this->endElement(true);
            }
        }
    }

    /**
     * @param string $prefix
     * @param string|null $uri
     * @return array
     */
    protected function resolveNamespace($prefix, $uri)
    {
        if (null === $uri) {
            if (isset($this->nsArray[$prefix])) {
                return [$prefix, $this->nsArray[$prefix]];
            }

            $this->nsArray[$prefix] = null;

            return [$prefix, null];
        }

        if (isset($this->nsArray[$prefix])) {
            if ($uri === $this->nsArray[$prefix]) {
                return [$prefix, $uri];
            }

            // TODO: Warn about overwriting?
            $this->nsArray[$prefix] = $uri;
        }

        return [$prefix, $uri];
    }

    /**
     * Apply requested encoding type to string
     *
     * @link  http://php.net/manual/en/function.mb-detect-encoding.php
     * @link  http://www.php.net/manual/en/function.mb-convert-encoding.php
     *
     * @param   string $string un-encoded string
     * @return  string
     */
    protected function encodeString($string)
    {
        // If no encoding value was passed in...
        if ($this->encoding === null) {
            return $string;
        }

        $detect = mb_detect_encoding($string);

        // If the current encoding is already the requested encoding
        if (is_string($detect) && $detect === $this->encoding) {
            return $string;
        }

        // Failed to determine encoding
        if (is_bool($detect)) {
            return $string;
        }

        // Convert it!
        return mb_convert_encoding($string, $this->encoding, $detect);
    }
}
