xml-writer-plus
===============

Simple XML Writer library for PHP 5.4+

The goal of this library is to provide some added functionality to the built-in PHP <a href="http://www.php.net//manual/en/book.xmlwriter.php" target="_blank">XMLWriter</a> class.

## Inclusion in your Composer app

Add

```
"dcarbone/xml-writer-plus" : "0.5.*"
```

To your application's ``` composer.json ``` file.

Learn more about Composer here: <a href="https://getcomposer.org/">https://getcomposer.org/</a>

## Basic usage

To get started creating your own XML document:

```php
use \DCarbone\XMLWriterPlus;

// Initialize writer instance
$xmlWriterPlus = new XMLWriterPlus();

// Start in-memory xml document
$xmlWriterPlus->openMemory();
$xmlWriterPlus->startDocument();

// Write out a comment prior to any elements
$xmlWriterPlus->writeComment('This is a comment and it contains superfluous information');

// Write root element (can be called anything you wish)
$xmlWriterPlus->startElement('Root');

// Write a node value to the root element
$xmlWriterPlus->text('Root element node value');

// Append a child element to the root element with it's own value
// This method opens, writes value, and closes an element all in one go
$xmlWriterPlus->writeElement('Child', 'Root element child element');

// Insert a CDATA element
$xmlWriterPlus->writeCDataElement('MyCData', '<div>This div won\'t confuse XML Parsers! <br></div>');

// Close root element
$xmlWriterPlus->endElement();

// Make document immutable
$xmlWriterPlus->endDocument();

// See our XML!
echo htmlspecialchars($xmlWriterPlus->outputMemory());
```

The above will output:

```xml
<?xml version="1.0" encoding="UTF-8"?> <!--This is a comment and it contains superfluous information--><Root>Root element node value<Child>Root element child element</Child><MyCData><![CDATA[<div>This div won't confuse XML Parsers! <br></div>]]></MyCData></Root>
```

Or, more legibly,

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!--This is a comment and it contains superfluous information-->
<Root>Root element node value
  <Child>Root element child element</Child>
  <MyCData>
    <![CDATA[<div>This div won't confuse XML Parsers! <br></div>]]>
  </MyCData>
</Root>
```

## Namespaces

One of the features of the core <a href="http://www.php.net//manual/en/book.xmlwriter.php" target="_blank">XMLWriter</a> class is the ability to
add namespace prefixes and URI's to individual elements.  It is also one of the more annoying features, as you have to manually define the NS and URI for
each element.

To address this, I have added the following methods:

```php
/**
 * @param string $prefix
 * @param string $uri
 */
public function addNS($prefix, $uri);

/**
 * @param string $prefix
 */
public function removeNS($prefix);

/**
 * @param string $prefix
 * @return bool
 */
public function hasNSPrefix($prefix);

/**
 * @param string $uri
 * @return bool
 */
public function hasNSUri($uri);

/**
 * @return array
 */
public function getNSArray();

/**
 * @param array $nsArray
 */
public function setNSArray(array $nsArray);

/**
 * @param string $prefix
 * @return string|bool
 */
public function getNSUriFromPrefix($prefix);

/**
 * @param string $uri
 * @return mixed
 */
public function getNSPrefixFromUri($uri);
```

These methods interact with an internal associative array of ``` $prefix => $uri ```.  The methods:

```php
/**
 * @param string $prefix
 * @param string $name
 * @param string|null $uri
 * @return bool
 */
public function startAttributeNS($prefix, $name, $uri = null);

/**
 * @param string $prefix
 * @param string $name
 * @param string|null $uri
 * @param string|null $content
 * @return bool
 */
public function writeAttributeNS($prefix, $name, $uri = null, $content = null);

/**
 * @param string $prefix
 * @param string $name
 * @param string|null $uri
 * @return bool
 */
public function startElementNS($prefix, $name, $uri = null);

/**
 * @param string $prefix
 * @param string $name
 * @param string|null $uri
 * @param string|null $content
 * @return bool
 */
public function writeElementNS($prefix, $name, $uri = null, $content = null);
```

Have all been modified to update this internal array.  So if you do:

```php
$xmlWriterPlus->startElementNS('pre', 'ElementName', 'http://my-special-prefix-uri.awesome');
$xmlWriterPlus->text('Test');
$xmlWriterPlus->endElement();
echo '<pre>';
var_export($xmlWriterPlus->getNSArray());
echo '</pre>';
```

You will see:

```php
array (
  'pre' => 'http://my-special-prefix-uri.awesome',
)
```


This can then be used with:

```php
$xmlWriterPlus->writeElementNS('pre', 'ElementName', 'my awesome content');
```

Which will result in:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<pre:ElementName xmlns:pre="http://my-special-prefix-uri.awesome">Test</pre:ElementName>
<pre:ElementName xmlns:pre="http://my-special-prefix-uri.awesome">my awesome content</pre:ElementName>
```

If you execute a ``` writeElement ``` call with a previously undefined namespace URI, NULL will be used (no xmlns attribute will be output).

## Fun stuff

Lets say you have a XMLWriterPlus instance already open and an array already constructed, and you wish to just append the entire thing
to the XML output without looping through and manually performing actions.  Well, good sir/ma'am/fish, you can!

```php
$list = array(
    'list value 1',
    'list value 2',
    'list value 3',
);

$hash = array(
    'HashKey1' => 'hash value 1',
    'h:HashKey2' => 'hash value 2 with namespace',
    'd:HashKey3' => 'hash value 3 with namespace',
    'ChildElement1' => array(
        'SubElement1' => 'sub element value 1',
    ),
);

$xmlWriterPlus = new XMLWriterPlus();

$xmlWriterPlus->openMemory();
$xmlWriterPlus->startDocument();

$xmlWriterPlus->addNS('h', 'http://www.w3.org/TR/html4/');

$xmlWriterPlus->startElement('Root');

$xmlWriterPlus->appendList($list, 'MyList');
$xmlWriterPlus->appendHash($hash);

$xmlWriterPlus->endElement();

$xmlWriterPlus->endDocument();

echo htmlspecialchars($xmlWriterPlus->outputMemory());
```

The above will output:

```xml
<?xml version="1.0" encoding="UTF-8"?> <Root><MyList>list value 1</MyList><MyList>list value 2</MyList><MyList>list value 3</MyList><HashKey1>hash value 1</HashKey1><h:HashKey2 xmlns:h="http://www.w3.org/TR/html4/">hash value 2 with namespace</h:HashKey2><d:HashKey3>hash value 3 with namespace</d:HashKey3><ChildElement1><SubElement1>sub element value 1</SubElement1></ChildElement1></Root>
```

Expanded:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <MyList>list value 1</MyList>
  <MyList>list value 2</MyList>
  <MyList>list value 3</MyList>
  <HashKey1>hash value 1</HashKey1>
  <h:HashKey2 xmlns:h="http://www.w3.org/TR/html4/">hash value 2 with namespace</h:HashKey2>
  <d:HashKey3>hash value 3 with namespace</d:HashKey3>
  <ChildElement1>
    <SubElement1>sub element value 1</SubElement1>
  </ChildElement1>
</Root>
```

You may pass in objects as well:

```php
$object = new \stdClass();

$object->ObjKey1 = 'obj value 1';
$object->ObjKey2 = 'obj value 2';
$object->ObjKey3 = array('ArrayKey1' => 'array value 1');

$xmlWriterPlus = new XMLWriterPlus();

$xmlWriterPlus->openMemory();
$xmlWriterPlus->startDocument();

$xmlWriterPlus->startElement('Root');

$xmlWriterPlus->appendHash($object);

$xmlWriterPlus->endElement();

$xmlWriterPlus->endDocument();

echo htmlspecialchars($xmlWriterPlus->outputMemory());
```

The above will output:

```xml
<?xml version="1.0" encoding="UTF-8"?> <Root><ObjKey1>obj value 1</ObjKey1><ObjKey2>obj value 2</ObjKey2><ObjKey3><ArrayKey1>array value 1</ArrayKey1></ObjKey3></Root>
```

Expanded:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <ObjKey1>obj value 1</ObjKey1>
  <ObjKey2>obj value 2</ObjKey2>
  <ObjKey3>
    <ArrayKey1>array value 1</ArrayKey1>
  </ObjKey3>
</Root>
```

## Hash Key Notes:

* As seen above, you may pass in a hash key with a colon ('h:HashKey2', for instance) and XMLWriterPlus will automatically create a namespaced element.
* You may pass in an object instance of a custom class as long as the class implements some form of PHP's <a href="http://www.php.net/manual/en/class.iterator.php" target="_blank">Iterator interface</a>
* If you wish to have multiple elements with the same name at the same level, you must pass in an array as such:
    * ``` array('Parent' => array( array('Child' => 'Value' ), array('Child' => 'Value') );```
