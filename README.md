xml-writer-plus
===============

Simple XML Writer library for PHP 5.3+

The goal of this library was to provide a simple wrapper class for the built-in PHP <a href="http://www.php.net//manual/en/book.xmlwriter.php" target="_blank">XMLWriter</a> class with
some simple additional functionality.

## Inclusion in your Composer app

Add

```
"dcarbone/xml-writer-plus" : "0.1.*"
```

To your application's ``` composer.json ``` file.

Learn more about Composer here: <a href="https://getcomposer.org/">https://getcomposer.org/</a>

## Basic usage

To get started creating your own XML document:

```php
use \DCarbone\XMLWriterPlus;

// Initialize writer instance
$xmlWriter = new XMLWriterPlus();

// Start xml document
$xmlWriter->startDocument();

// Write out a comment prior to any elements
$xmlWriter->writeComment('This is a comment and it contains superfluous information');

// Write root element (can be called anything you wish)
$xmlWriter->writeStartElement('Root');

// Write a node value to the root element
$xmlWriter->writeText('Root element node value');

// Append a child element to the root element with it's own value
// This method opens, writes value, and closes an element all in one go
$xmlWriter->writeElement('Child', 'Root element child element');

// Insert a CDATA block
$xmlWriter->writeStartCDataElement('MyCDATA');
$xmlWriter->writeText('<div>This div won\'t confuse XML Parsers!</div>');
$xmlWriter->writeEndCDataElement();

// Close root element
$xmlWriter->writeEndElement();

// Make document immutable
$xmlWriter->endDocument();

// See our XML!
echo htmlspecialchars($xmlWriter->getXML());
```

The above will output:

```xml
<?xml version="1.0" encoding="UTF-8"?> <!-- This is a comment and it contains superfluous information --><Root>Root element node value<Child>Root element child element</Child><MyCDATA><![CDATA[<div>This div won't confuse XML Parsers!</div>]]></MyCDATA></Root>
```

Or, more legibly,

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- This is a comment and it contains superfluous information -->
<Root>Root element node value
  <Child>Root element child element</Child>
  <MyCDATA>
    <![CDATA[<div>This div won't confuse XML Parsers!</div>]]>
  </MyCDATA>
</Root>
```

## Character Conversion

One of the potentially most frustrating things when working with data from multiple different systems can be character encoding conversion.

I have tried to make this as simple as possible for you to get around, keeping in mind a few things:

* Json data MUST be encoded in UTF-8
* The ability of your system to convert encodings will depend on your specific PHP instance

There are 4 public arrays that are used to help facilitate this:

* **$strSearchCharacters**
* **$strReplaceCharacters**
* **$regexpSearchCharacters**
* **$regexpReplaceCharacters**

### str_ireplace && preg_replace

For a full debrief on these two functions:
* <a href="http://www.php.net//manual/en/function.str-ireplace.php" target="_blank">str_ireplace</a>
* <a href="http://us3.php.net//manual/en/function.preg-replace.php" target="_blank">preg_replace</a>

Every string value that is written to either an object or an array within this library goes through these two functions if the
**xxSearchCharacters** array for the corresponding action contains values.  It is then replaced with the corresponding position **xxReplaceCharacters** value.

### Encoding

After character replacement has occurred, the method `encodeString` is called, and utilizes
PHP's <a href="http://www.php.net//manual/en/function.mb-detect-encoding.php" target="_blank">mb_detect_encoding</a> and <a href="http://www.php.net//manual/en/function.mb-convert-encoding.php" target="_blank">mb_convert_encoding</a> functions.
