<?php namespace DCarbone;

use \XMLWriter;

/**
 * Class XMLWriterPlus
 * @package DCarbone\Helpers
 */
class XMLWriterPlus extends \XMLWriter
{
    /**
     * str_replace search value(s)
     * @var array
     */
    public $strSearchCharacters = array(
        '&#169;',
        '&#xa9;',
        '&copy;'
    );

    /**
     * str_replace replace value(s)
     * @var array
     */
    public $strReplaceCharacters = array(
        '\u00A9', // copyright symbol
        '\u00A9',
        '\u00A9'
    );

    /**
     * RegExp search value(s)
     * @var array
     */
    public $regexpSearchCharacters = array();

    /**
     * RegExp replace value(s)
     * @var array
     */
    public $regexpReplaceCharacters = array();

    /** @var string */
    protected $encoding = 'UTF-8';

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
        if (array_key_exists($prefix, $this->nsArray))
            unset($this->nsArray[$prefix]);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function hasNSPrefix($prefix)
    {
        return array_key_exists($prefix, $this->nsArray);
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
        if ($this->hasNSPrefix($prefix))
            return $this->nsArray[$prefix];

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
        if (is_float($version) || is_int($version))
            $version = number_format((float)$version, 1);

        $this->encoding = $encoding;
        parent::startDocument($version, $encoding, $standalone);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @return bool
     */
    public function startAttributeNS($prefix, $name, $uri = null)
    {
        $this->nsArray[$prefix] = $uri;
        return parent::startAttributeNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @param string $content
     * @return bool
     */
    public function writeAttributeNS($prefix, $name, $uri = null, $content)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::writeAttributeNS($prefix, $name, $uri, $content);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @return bool
     */
    public function startElementNS($prefix, $name, $uri = null)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::startElementNS($prefix, $name, $uri);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $uri
     * @param null|string $content
     * @return bool
     */
    public function writeElementNS($prefix, $name, $uri = null, $content = null)
    {
        if (!$this->hasNSPrefix($prefix))
            $this->nsArray[$prefix] = $uri;

        return parent::writeElementNS($prefix, $name, $uri, $content);
    }

    /**
     * @param string $name
     * @param string|null $nsPrefix
     * @return bool
     */
    public function startElement($name, $nsPrefix = null)
    {
        if ($nsPrefix === null)
            return parent::startElement($name);

        return $this->startElementNS($nsPrefix, $name);
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
        if (is_string($text) || settype($text, 'string' ) !== false)
        {
            $converted = $this->convertCharacters($text);
            $encoded = $this->encodeString($converted);
            return parent::text($encoded);
        }

        throw new \InvalidArgumentException('XMLWriterPlus::text - Cannot cast passed value to string (did you forget to define a __toString on your object?)');
    }

    /**
     * @param string $name
     * @param string|null $content
     * @param string|null $nsPrefix
     * @return bool
     */
    public function writeElement($name, $content = null, $nsPrefix = null)
    {
        if ($nsPrefix === null)
            return $this->startElement($name) &&
            $this->text($content) &&
            $this->endElement(($content === null ? true : false));

        if ($this->hasNSPrefix($nsPrefix))
            return $this->writeElementNS(
                $nsPrefix,
                $name,
                $this->getNSUriFromPrefix($nsPrefix),
                $content);

        return $this->writeElementNS($nsPrefix, $name, null, $content);
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
        if ($full === true)
            return $this->fullEndElement();

        return parent::endElement();
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|null $nsPrefix
     * @return bool
     */
    public function writeCDataElement($name, $content, $nsPrefix = null)
    {
        if ($nsPrefix === null)
            return $this->startElement($name) &&
            $this->writeCdata($content) &&
            $this->endElement(true);

        if ($this->hasNSPrefix($nsPrefix))
            return $this->startElementNS($nsPrefix, $name, $this->getNSUriFromPrefix($nsPrefix)) &&
            $this->writeCdata($content) &&
            $this->endElement(true);

        return $this->writeElementNS($nsPrefix, $name, null) &&
        $this->writeCdata($content) &&
        $this->endElement(true);
    }

    /**
     * Append an integer index array of values to this XML document
     *
     * @param array $data
     * @param string $elementName
     * @param null|string $nsPrefix
     * @return bool
     */
    public function appendList(array $data, $elementName, $nsPrefix = null)
    {
        foreach($data as $value)
        {
            $this->writeElement($elementName, $value, $nsPrefix);
        }

        return true;
    }

    /**
     * Append an associative array or object to this XML document
     *
     * @param array|object $data
     * @param string|null $previousKey
     * @return bool
     */
    public function appendHash($data, $previousKey = null)
    {
        if (is_array($data) || (is_object($data) && $data instanceof \Iterator))
        {
            foreach($data as $key=>$value)
            {
                $this->appendHashData($key, $value, $previousKey);
            }
            return true;
        }

        if (is_object($data))
        {
            foreach(get_object_vars($data) as $key=>$value)
            {
                $this->appendHashData($key, $value, $previousKey);
            }

            return true;
        }

        return false;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param null|string $previousKey
     */
    protected function appendHashData($key, $value, $previousKey)
    {
        if (is_scalar($value))
        {
            if (is_string($key) && !is_numeric($key))
            {
                if (strstr($key, ':') !== false)
                {
                    $exp = explode(':', $key);
                    $this->writeElement($exp[1], $value, $exp[0]);
                }
                else
                {
                    $this->writeElement($key, $value);
                }
            }
            else if (is_numeric($key) && $previousKey !== null && !is_numeric($previousKey))
            {
                $this->writeElement($previousKey, $value);
            }
            else
            {
                $this->writeElement($key, $value);
            }

            return;
        }

        if (is_numeric($key))
        {
            foreach($value as $k=>$v)
            {
                $this->appendHashData($k, $v, $previousKey);
            }
        }
        else if (strstr($key, ':') !== false)
        {
            $exp = explode(':', $key);
            $this->startElementNS($exp[0], $exp[1]);
            $this->appendHash($value, $key);
            $this->endElement(true);
        }
        else
        {
            $this->startElement($key);
            $this->appendHash($value, $key);
            $this->endElement(true);
        }
    }

    /**
     * Convert characters for output in an XML file
     *
     * @param   string $string  Input string
     * @throws \InvalidArgumentException
     * @return  string
     */
    protected function convertCharacters($string)
    {
        $strSearch = null;
        $strReplace = null;
        $regexpSearch = null;
        $regexpReplace = null;

        // See if we have str_replace keys
        if ((is_string($this->strSearchCharacters) && $this->strSearchCharacters !== "") ||
            (is_array($this->strSearchCharacters) && count($this->strSearchCharacters) > 0))
        {
            $strSearch = $this->strSearchCharacters;
        }

        // If we have search keys, see if we have replace keys
        if ($strSearch !== null &&
            (is_string($this->strReplaceCharacters) && $this->strReplaceCharacters !== "") ||
            (is_array($this->strReplaceCharacters) && count($this->strReplaceCharacters) > 0))
        {
            $strReplace = $this->strReplaceCharacters;
        }

        // See if we have preg_replace keys
        if ((is_string($this->regexpSearchCharacters) && $this->regexpSearchCharacters !== "") ||
            (is_array($this->regexpSearchCharacters) && count($this->regexpSearchCharacters) > 0))
        {
            $regexpSearch = $this->regexpSearchCharacters;
        }

        // If we have search keys, see if we have replace keys
        if ($regexpSearch !== null &&
            (is_string($this->regexpReplaceCharacters) && $this->regexpReplaceCharacters !== "") ||
            (is_array($this->regexpReplaceCharacters) && count($this->regexpReplaceCharacters) > 0))
        {
            $regexpReplace = $this->regexpReplaceCharacters;
        }

        // Execute str_replace
        if ($strSearch !== null && $strReplace !== null)
            $string = str_replace($strSearch, $strReplace, $string);

        // Execute preg_replace
        if ($regexpSearch !== null && $regexpReplace !== null)
            $string = preg_replace($regexpSearch, $regexpReplace, $string);

        return $string;
    }

    /**
     * Apply requested encoding type to string
     *
     * @link  http://php.net/manual/en/function.mb-detect-encoding.php
     * @link  http://www.php.net/manual/en/function.mb-convert-encoding.php
     *
     * @param   string  $string  un-encoded string
     * @return  string
     */
    protected function encodeString($string)
    {
        // If no encoding value was passed in...
        if ($this->encoding === null)
            return $string;

        $detect = mb_detect_encoding($string);

        // If the current encoding is already the requested encoding
        if (is_string($detect) && $detect === $this->encoding)
            return $string;

        // Failed to determine encoding
        if (is_bool($detect))
            return $string;

        // Convert it!
        return mb_convert_encoding($string, $this->encoding, $detect);
    }

    /**
     * @param bool $flush
     * @param bool $endDoc
     * @throws \Exception
     * @return null|\SimpleXMLElement
     */
    public function getSXEFromMemory($flush = false, $endDoc = false)
    {
        if ($this->memory === true)
        {
            if ($endDoc === true)
                $this->endDocument();

            try {
                if (defined('LIBXML_PARSEHUGE'))
                    $arg = LIBXML_COMPACT | LIBXML_PARSEHUGE;
                else
                    $arg = LIBXML_COMPACT;

                return new \SimpleXMLElement($this->outputMemory((bool)$flush), $arg);
            }
            catch (\Exception $e) {
                if (libxml_get_last_error() !== false)
                    throw new \Exception('DCarbone\XMLWriterPlus::getSXEFromMemory - Error encountered: "'.libxml_get_last_error()->message.'"');
                else
                    throw new \Exception('DCarbone\XMLWriterPlus::getSXEFromMemory - Error encountered: "'.$e->getMessage().'"');
            }
        }

        return null;
    }

    /**
     * @param bool $flush
     * @param bool $endDoc
     * @return \DOMDocument|null
     * @throws \Exception
     */
    public function getDOMFromMemory($flush = false, $endDoc = false)
    {
        if ($this->memory === true)
        {
            if ($endDoc === true)
                $this->endDocument();

            try {
                $dom = new \DOMDocument();
                $dom->loadXML($this->outputMemory((bool)$flush));

                return $dom;
            }
            catch (\Exception $e) {
                if (libxml_get_last_error() !== false)
                    throw new \Exception('DCarbone\XMLWriterPlus::getDOMFromMemory - Error encountered: "'.libxml_get_last_error()->message.'"');
                else
                    throw new \Exception('DCarbone\XMLWriterPlus::getDOMFromMemory - Error encountered: "'.$e->getMessage().'"');
            }
        }

        return null;
    }
}