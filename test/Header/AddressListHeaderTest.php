<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mail\Header;

use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\Mail\Header\Bcc;
use Laminas\Mail\Header\Cc;
use Laminas\Mail\Header\From;
use Laminas\Mail\Header\ReplyTo;
use Laminas\Mail\Header\To;

/**
 * @group      Laminas_Mail
 */
class AddressListHeaderTest extends \PHPUnit_Framework_TestCase
{
    public static function getHeaderInstances()
    {
        return [
            [new Bcc(), 'Bcc'],
            [new Cc(), 'Cc'],
            [new From(), 'From'],
            [new ReplyTo(), 'Reply-To'],
            [new To(), 'To'],
        ];
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeadersExtendAbstractAddressListHeader($header)
    {
        $this->assertInstanceOf('Laminas\Mail\Header\AbstractAddressList', $header);
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeaderFieldNamesAreDiscrete($header, $type)
    {
        $this->assertEquals($type, $header->getFieldName());
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testConcreteHeadersComposeAddressLists($header)
    {
        $list = $header->getAddressList();
        $this->assertInstanceOf('Laminas\Mail\AddressList', $list);
    }

    public function testFieldValueIsEmptyByDefault()
    {
        $header = new To();
        $this->assertEquals('', $header->getFieldValue());
    }

    public function testFieldValueIsCreatedFromAddressList()
    {
        $header = new To();
        $list   = $header->getAddressList();
        $this->populateAddressList($list);
        $expected = $this->getExpectedFieldValue();
        $this->assertEquals($expected, $header->getFieldValue());
    }

    public function populateAddressList(AddressList $list)
    {
        $address = new Address('api-tools-devteam@zend.com', 'Laminas DevTeam');
        $list->add($address);
        $list->add('api-tools-contributors@lists.zend.com');
        $list->add('fw-announce@lists.zend.com', 'Laminas Announce List');
        $list->add('first@last.zend.com', 'Last, First');
    }

    public function getExpectedFieldValue()
    {
        // @codingStandardsIgnoreStart
        return "Laminas DevTeam <api-tools-devteam@zend.com>,\r\n api-tools-contributors@lists.zend.com,\r\n Laminas Announce List <fw-announce@lists.zend.com>,\r\n \"Last, First\" <first@last.zend.com>";
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider getHeaderInstances
     */
    public function testStringRepresentationIncludesHeaderAndFieldValue($header, $type)
    {
        $this->populateAddressList($header->getAddressList());
        $expected = sprintf('%s: %s', $type, $this->getExpectedFieldValue());
        $this->assertEquals($expected, $header->toString());
    }

    public function getStringHeaders()
    {
        $value = $this->getExpectedFieldValue();
        return [
            'cc'       => ['Cc: ' . $value, 'Laminas\Mail\Header\Cc'],
            'bcc'      => ['Bcc: ' . $value, 'Laminas\Mail\Header\Bcc'],
            'from'     => ['From: ' . $value, 'Laminas\Mail\Header\From'],
            'reply-to' => ['Reply-To: ' . $value, 'Laminas\Mail\Header\ReplyTo'],
            'to'       => ['To: ' . $value, 'Laminas\Mail\Header\To'],
        ];
    }

    /**
     * @dataProvider getStringHeaders
     */
    public function testDeserializationFromString($headerLine, $class)
    {
        $callback = sprintf('%s::fromString', $class);
        $header   = call_user_func($callback, $headerLine);
        $this->assertInstanceOf($class, $header);
        $list = $header->getAddressList();
        $this->assertEquals(4, count($list));
        $this->assertTrue($list->has('api-tools-devteam@zend.com'));
        $this->assertTrue($list->has('api-tools-contributors@lists.zend.com'));
        $this->assertTrue($list->has('fw-announce@lists.zend.com'));
        $this->assertTrue($list->has('first@last.zend.com'));
        $address = $list->get('api-tools-devteam@zend.com');
        $this->assertEquals('Laminas DevTeam', $address->getName());
        $address = $list->get('api-tools-contributors@lists.zend.com');
        $this->assertNull($address->getName());
        $address = $list->get('fw-announce@lists.zend.com');
        $this->assertEquals('Laminas Announce List', $address->getName());
        $address = $list->get('first@last.zend.com');
        $this->assertEquals('Last, First', $address->getName());
    }

    public function getStringHeadersWithNoWhitespaceSeparator()
    {
        $value = $this->getExpectedFieldValue();
        return [
            'cc'       => ['Cc:' . $value, 'Laminas\Mail\Header\Cc'],
            'bcc'      => ['Bcc:' . $value, 'Laminas\Mail\Header\Bcc'],
            'from'     => ['From:' . $value, 'Laminas\Mail\Header\From'],
            'reply-to' => ['Reply-To:' . $value, 'Laminas\Mail\Header\ReplyTo'],
            'to'       => ['To:' . $value, 'Laminas\Mail\Header\To'],
        ];
    }

    /**
     * @dataProvider getHeadersWithComments
     */
    public function testDeserializationFromStringWithComments($value)
    {
        $header = From::fromString($value);
        $list = $header->getAddressList();
        $this->assertEquals(1, count($list));
        $this->assertTrue($list->has('user@example.com'));
    }

    public function getHeadersWithComments()
    {
        return [
            ['From: user@example.com (Comment)'],
            ['From: user@example.com (Comm\\)ent)'],
            ['From: (Comment\\\\)user@example.com(Another)'],
        ];
    }

    /**
     * @group 3789
     * @dataProvider getStringHeadersWithNoWhitespaceSeparator
     */
    public function testAllowsNoWhitespaceBetweenHeaderAndValue($headerLine, $class)
    {
        $callback = sprintf('%s::fromString', $class);
        $header   = call_user_func($callback, $headerLine);
        $this->assertInstanceOf($class, $header);
        $list = $header->getAddressList();
        $this->assertEquals(4, count($list));
        $this->assertTrue($list->has('api-tools-devteam@zend.com'));
        $this->assertTrue($list->has('api-tools-contributors@lists.zend.com'));
        $this->assertTrue($list->has('fw-announce@lists.zend.com'));
        $this->assertTrue($list->has('first@last.zend.com'));
        $address = $list->get('api-tools-devteam@zend.com');
        $this->assertEquals('Laminas DevTeam', $address->getName());
        $address = $list->get('api-tools-contributors@lists.zend.com');
        $this->assertNull($address->getName());
        $address = $list->get('fw-announce@lists.zend.com');
        $this->assertEquals('Laminas Announce List', $address->getName());
        $address = $list->get('first@last.zend.com');
        $this->assertEquals('Last, First', $address->getName());
    }

    /**
     * @dataProvider getAddressListsWithGroup
     */
    public function testAddressListWithGroup($input, $count, $sample)
    {
        $header = To::fromString($input);
        $list = $header->getAddressList();
        $this->assertEquals($count, count($list));
        if ($count > 0) {
            $this->assertTrue($list->has($sample));
        }
    }

    public function getAddressListsWithGroup()
    {
        return [
            ['To: undisclosed-recipients:;', 0, null],
            ['To: friends: john@example.com; enemies: john@example.net, bart@example.net;', 3, 'john@example.net'],
        ];
    }

    public function specialCharHeaderProvider()
    {
        return [
            [
                "To: =?UTF-8?B?dGVzdCxsYWJlbA==?= <john@example.com>, john2@example.com",
                ['john@example.com' => 'test,label', 'john2@example.com' => null],
                'UTF-8'
            ],
            [
                'To: "TEST\",QUOTE" <john@example.com>, john2@example.com',
                ['john@example.com' => 'TEST",QUOTE', 'john2@example.com' => null],
                'ASCII'
            ]
        ];
    }

    /**
     * @dataProvider specialCharHeaderProvider
     */
    public function testDeserializationFromSpecialCharString($headerLine, $expected, $encoding)
    {
        $header = To::fromString($headerLine);

        $expectedTo = new To();
        $addressList = $expectedTo->getAddressList();
        $addressList->addMany($expected);
        $expectedTo->setEncoding($encoding);
        $this->assertEquals($expectedTo, $header);
        foreach ($expected as $k => $v) {
            $this->assertTrue($addressList->has($k));
            $this->assertEquals($addressList->get($k)->getName(), $v);
        }
    }
}
