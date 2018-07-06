<?php namespace DCarbone;

use PHPUnit\Framework\TestCase;

/**
 * Class XMLWriterPlusTest
 * @package DCarbone\Tests
 */
class XMLWriterPlusTest extends TestCase
{
    const HASH_NO_URI_EXPECTED_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Root><tem:usuarioCliente>1234</tem:usuarioCliente><tem:senhaCliente>123</tem:senhaCliente><tem:codigoAgencia>123</tem:codigoAgencia></Root>

XML;
    const HASH_URI_EXPECTED_XML    = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Root><tem:usuarioCliente xmlns:tem="http://127.0.0.1/tem.xsd">1234</tem:usuarioCliente><tem:senhaCliente xmlns:tem="http://127.0.0.1/tem.xsd">123</tem:senhaCliente><tem:codigoAgencia xmlns:tem="http://127.0.0.1/tem.xsd">123</tem:codigoAgencia></Root>

XML;

    const LIST_NO_URI_EXPECTED_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Root><MyList>list value 1</MyList><MyList>list value 2</MyList><MyList>list value 3</MyList></Root>

XML;
    const LIST_URI_EXPECTED_XML    = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Root><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 1</h:MyList><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 2</h:MyList><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 3</h:MyList></Root>

XML;

    /** @var array */
    private static $testHash = [
        'tem:usuarioCliente' => '1234',
        'tem:senhaCliente'   => '123',
        'tem:codigoAgencia'  => '123',
    ];

    private static $testList = [
        'list value 1',
        'list value 2',
        'list value 3',
    ];

    public function testAppendHashNoURI()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->startDocument();
        $writer->startElement('Root');
        $writer->appendHash(self::$testHash);
        $writer->endElement();
        $writer->endDocument();
        $this->assertEquals(self::HASH_NO_URI_EXPECTED_XML, $writer->outputMemory());
    }

    public function testApepndHashURI()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->startDocument();
        $writer->startElement('Root');
        $writer->addNS('tem', 'http://127.0.0.1/tem.xsd');
        $writer->appendHash(self::$testHash);
        $writer->endElement();
        $writer->endDocument();
        $this->assertEquals(self::HASH_URI_EXPECTED_XML, $writer->outputMemory());
    }

    public function testAppendListNoURI()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->startDocument();
        $writer->startElement('Root');
        $writer->appendList(self::$testList, 'MyList');
        $writer->endElement();
        $writer->endDocument();
        $this->assertEquals(self::LIST_NO_URI_EXPECTED_XML, $writer->outputMemory());
    }

    public function testAppendListURI()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->addNS('h', 'http://www.w3.org/TR/html4/');
        $writer->startDocument();
        $writer->startElement('Root');
        $writer->appendList(self::$testList, 'MyList', 'h');
        $writer->endElement();
        $writer->endDocument();
        $this->assertEquals(self::LIST_URI_EXPECTED_XML, $writer->outputMemory());
    }
}
