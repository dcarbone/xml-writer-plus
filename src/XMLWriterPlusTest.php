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

    const LIST_URI_EXPECTED_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Root><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 1</h:MyList><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 2</h:MyList><h:MyList xmlns:h="http://www.w3.org/TR/html4/">list value 3</h:MyList></Root>

XML;

    const ELEMENT_WITH_ATTR_EXPECTED_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<element attr="nons" ns:attr="withns">great job</element>

XML;

    const NS_ELEMENT_WITH_ATTR_EXPECTED_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ens:element attr="nons" ns:attr="withns">great job</ens:element>

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

    public function testWriteElementWithAttributes()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->startDocument();
        $writer->writeElement('element', 'great job', ['attr' => 'nons', 'ns:attr' => 'withns']);
        $writer->endDocument();
        $this->assertEquals(self::ELEMENT_WITH_ATTR_EXPECTED_XML, $writer->outputMemory());
    }

    public function testWriteNSElementWithAttributes()
    {
        $writer = new XMLWriterPlus();
        $writer->openMemory();
        $writer->startDocument();
        $writer->writeElementNS('ens', 'element', null, 'great job', ['attr' => 'nons', 'ns:attr' => 'withns']);
        $writer->endDocument();
        $this->assertEquals(self::NS_ELEMENT_WITH_ATTR_EXPECTED_XML, $writer->outputMemory());
    }
}
