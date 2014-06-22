<?php namespace DCarbone;

use \XMLWriter;

/**
 * Class XMLWriterPlus
 * @package DCarbone\Helpers
 */
class XMLWriterPlus
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

    /**
     * Instance of a DOMDocument
     * @var XMLWriter
     */
    protected $writer = null;

    /**
     * XML Version to use
     * @var String
     */
    protected $version = null;

    /**
     * XML charset to use
     * @var String
     */
    protected $charset = null;

    /**
     * Has the document been initialized yet?
     * @var boolean
     */
    protected $started = false;

    /**
     * Has the document been ended?
     * @var boolean
     */
    protected $ended = false;

    /**
     * The output XML
     * @var String
     */
    protected $xml = null;

    /**
     * Namespaces added to this object
     * @var array
     */
    protected $namespaces = array();

    /**
     * Constructor
     */
    public function __construct($version = "1.0", $charset = "UTF-8", $xsd = null)
    {
        $this->version = $version;
        $this->charset = $charset;
    }

    /**
     * Destructor
     *
     * @link http://www.php.net/manual/en/function.xmlwriter-flush.php
     */
    public function __destruct()
    {
        if ($this->started === true && $this->writer instanceof \XMLWriter)
            $this->writer->flush();
    }

    /**
     * Quick helper function to determine if this document
     * is indeed editable
     *
     * @access public
     * @return  Boolean
     */
    public function canEdit()
    {
        return ($this->started === true && $this->ended === false);
    }

    /**
     * Set charset
     *
     * No validation is done on the charset, it's your responsibility
     * to choose the right one
     *
     * @param   String  $charset  desired charset
     * @return  Boolean
     */
    public function setCharset($charset = null)
    {
        if ($this->started || !is_string($charset) || trim($charset) === '')
            return false;

        $this->charset = $charset;
        return true;
    }

    /**
     * Set version
     *
     * No validation is done on the version, it is your responsibility
     * to choose the right one.
     *
     * @param   String  $version  Version to use
     * @return  Boolean
     */
    public function setVersion($version = null)
    {
        if ($this->started || !is_string($version) || trim($version) === "")
            return false;

        $this->version = $version;
        return true;
    }

    /**
     * Add a Namespace to this XML object
     *
     * @param  String  $prefix  Prefix of namespace
     * @param  String  $uri     DTD or XSD file location
     */
    public function registerNamespace($prefix, $uri)
    {
        $this->namespaces[$prefix] = $uri;
    }

    /**
     * Write opening element in xml
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-element.php
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-element-ns.php
     *
     * @param  String $name    Name of Element
     * @param  Mixed $prefix  Namespace Prefix
     * @throws \OutOfBoundsException
     * @return Bool
     */
    public function writeStartElement($name, $prefix = null)
    {
        if ($this->canEdit() && is_string($name))
        {
            // If this is a NS'd element
            if (is_string($prefix))
            {
                if (!isset($this->namespaces[$prefix]))
                    throw new \OutOfBoundsException("Specified Invalid XMLWriterPlus Namespace Prefix");

                return $this->writer->startElementNS($prefix, $name, $this->namespaces[$prefix]);
            }

            // If a non-ns element
            return $this->writer->startElement($name);
        }

        return false;
    }

    /**
     * Write Text into Attribute or Element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-text.php
     *
     * @param String $text Value to write
     * @throws \InvalidArgumentException
     * @return  Bool
     */
    public function writeText($text)
    {
        if ($this->canEdit())
        {
            if (is_string($text) || settype($text, 'string' ) !== false)
            {
                $convert = $this->convertCharacters($text);
                $encode = $this->encodeString($convert);
                return $this->writer->text($encode);
            }
            else
            {
                throw new \InvalidArgumentException("Cannot cast WriteText value to string (did you forget to define a __toString on your object?)");
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Write ending element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-full-end-element.php
     *
     * @return Boolean
     */
    public function writeEndElement()
    {
        if ($this->canEdit())
            return $this->writer->fullEndElement();

        return false;
    }

    /**
     * Write Entire Element to Xml
     *
     * {@link WriteStartElement()}
     * {@link WriteText()}
     * {@link WriteEndElement()}
     *
     * @param String  $name        Name of element
     * @param String  $data        Data of element
     * @param Mixed   $prefix      Namespace Prefix
     * @return Boolean
     */
    public function writeElement($name, $data, $prefix = null)
    {
        if ($this->canEdit())
            return $this->writeStartElement($name, $prefix) &&
                $this->writeText($data) &&
                $this->writeEndElement();

        return false;
    }

    /**
     * Start an attribute on an element
     *
     * @link http://www.php.net/manual/en/function.xmlwriter-start-attribute.php
     *
     * @param  String  $name  Name of Attribute
     * @return Boolean
     */
    public function writeStartAttribute($name)
    {
        if ($this->canEdit())
            return $this->writer->startAttribute($name);

        return false;
    }

    /**
     * End an attribute on an element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-end-attribute.php
     *
     * @return Boolean
     */
    public function writeEndAttribute()
    {
        if ($this->canEdit())
            return $this->writer->endAttribute();

        return false;
    }

    /**
     * Write a whole attribute on an element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-write-attribute.php
     *
     * @param String  $name   Name of Attribute
     * @param String  $value  Value of Attribute
     * @return  Boolean
     */
    public function writeAttribute($name, $value)
    {
        if ($this->canEdit())
            return $this->writeStartAttribute($name) &&
                $this->writeText($value) &&
                $this->writeEndAttribute();

        return false;
    }

    /**
     * Write Comment Start
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-comment.php
     *
     * @return Boolean
     */
    public function writeStartComment()
    {
        if ($this->canEdit())
            return $this->writer->startComment();

        return false;
    }

    /**
     * Write Comment End
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-end-comment.php
     *
     * @return Boolean
     */
    public function writeEndComment()
    {
        if ($this->canEdit())
            return $this->writer->endComment();

        return false;
    }

    /**
     * Write Full Comment Block
     *
     * @param  String  $text  Body of comment
     * @return Boolean
     */
    public function writeComment($text)
    {
        if ($this->canEdit() && is_string($text))
            return $this->writeStartComment() &&
                $this->writeCommentText($text) &&
                $this->writeEndComment();

        return false;
    }

    /**
     * Comments need to be pretty.
     *
     * @param String $text Body of comment
     * @throws \InvalidArgumentException
     * @return  Bool
     */
    public function writeCommentText($text)
    {
        if ($this->canEdit())
        {
            if (is_string($text) || settype($text, 'string' ) !== false)
            {
                $convert = $this->convertCharacters($text);
                $encoded = $this->encodeString($convert);
                $string = " ".$encoded." ";
                return $this->writer->text($string);
            }
            else
            {
                throw new \InvalidArgumentException("Cannot cast WriteText value to string (did you forget to define a __toString on your object?)");
            }
        }

        return false;
    }

    /**
     * Start CData Element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-cdata.php
     *
     * @param $name
     * @param null $prefix
     * @return Boolean
     */
    public function writeStartCDataElement($name, $prefix = null)
    {
        if ($this->canEdit() && is_string($name))
            return $this->writeStartElement($name, $prefix) && $this->writeStartCData();

        return false;
    }

    /**
     * End CData element
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-end-cdata.php
     *
     * @return Boolean
     */
    public function writeEndCDataElement()
    {
        if ($this->canEdit())
            return $this->writeEndCData() && $this->writeEndElement();

        return false;
    }

    /**
     * Start CData
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-cdata.php
     *
     * @return Boolean
     */
    public function writeStartCData()
    {
        if ($this->canEdit())
            return $this->writer->startCData();

        return false;
    }

    /**
     * End CData
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-end-cdata.php
     *
     * @return Boolean
     */
    public function writeEndCData()
    {
        if ($this->canEdit())
            return $this->writer->endCData();

        return false;
    }

    /**
     * Starts XmlWriter instance and defines
     * charset / version
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-open-memory.php
     * @link  http://www.php.net/manual/en/function.xmlwriter-start-document.php
     *
     * @return  Boolean
     */
    public function startDocument()
    {
        if ($this->started === true || $this->ended === true)
            return false;

        $this->writer = new \XmlWriter($this->version, $this->charset);
        $this->started = true;
        $this->writer->openMemory();
        return $this->writer->startDocument($this->version, $this->charset);
    }

    /**
     * End xml doc
     *
     * @link  http://www.php.net/manual/en/function.xmlwriter-end-document.php
     * @link  http://www.php.net/manual/en/function.xmlwriter-output-memory.php
     *
     * @return  Boolean
     */
    public function endDocument()
    {
        if ($this->started === false || $this->ended === true)
            return false;

        $this->ended = true;
        $this->writer->endDocument();
        $this->xml = $this->writer->outputMemory(true);
        return true;
    }

    /**
     * Return's the output of the memory buffer
     *
     * @return String
     */
    public function getXML()
    {
        return $this->xml;
    }

    /**
     * Convert characters for output in an XML file
     *
     * @param   String $string  Input String
     * @throws \InvalidArgumentException
     * @return  String
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
        {
            $string = str_replace($strSearch, $strReplace, $string);
        }

        // Execute preg_replace
        if ($regexpSearch !== null && $regexpReplace !== null)
        {
            $string = preg_replace($regexpSearch, $regexpReplace, $string);
        }

        return $string;
    }

    /**
     * Apply requested encoding type to string
     *
     * @link  http://php.net/manual/en/function.mb-detect-encoding.php
     * @link  http://www.php.net/manual/en/function.mb-convert-encoding.php
     *
     * @param   String  $string  un-encoded string
     * @return  String
     */
    protected function encodeString($string)
    {
        $detect = mb_detect_encoding($string);

        // If the current encoding is already the requested encoding
        if (is_string($detect) && $detect === $this->charset)
        {
            return $string;
        }
        // Failed to determine encoding
        else if (is_bool($detect))
        {
            return $string;
        }
        // Convert it!
        else
        {
            return mb_convert_encoding($string, $this->charset, $detect);
        }
    }
}
