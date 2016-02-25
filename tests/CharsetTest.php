<?php

use PhpXmlRpc\Helper\Charset;

/**
 * Test conversion between encodings
 *
 * For Windows if you want to test the output use Consolas font
 * and run the following in cmd:
 *     chcp 28591 (latin1)
 *     chcp 65001 (utf8)
 */
class CharsetTest extends PHPUnit_Framework_TestCase
{
    // Consolas font should render these properly
    protected $runes = "ᚠᛇᚻ᛫ᛒᛦᚦ᛫ᚠᚱᚩᚠᚢᚱ᛫ᚠᛁᚱᚪ᛫ᚷᛖᚻᚹᛦᛚᚳᚢᛗ";
    protected $greek = "Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ";
    protected $russian = "Река неслася; бедный чёлн";
    protected $chinese = "我能吞下玻璃而不伤身体。";
    protected $polish = "Mogę jeść szkło i mi nie szkodzi";

    protected $latinString;

    protected function setUp()
    {
        // construct the latin string
        // http://cs.stanford.edu/people/miles/iso8859.html
        $this->latinString = "\n\r\t ";
        for($i = 32; $i < 127; $i++) {
            $this->latinString .= chr($i);
        }
        for($i = 160; $i < 256; $i++) {
            $this->latinString .= chr($i);
        }
    }

    protected function tearDown()
    {
    }

    protected function latinToUtf($data)
    {
        return Charset::instance()->encodeEntities(
            $data,
            'ISO-8859-1',
            'UTF-8'
        );
    }

    protected function utfToLatin($data)
    {
        return Charset::instance()->encodeEntities(
            $data,
            'UTF-8',
            'ISO-8859-1'
        );
    }

    public function testUtf8ToLatin1()
    {
        $string = 'a.b.c.å.ä.ö.';

        $encoded = $this->utfToLatin($string);

        $this->assertEquals(utf8_decode('a.b.c.å.ä.ö.'), $encoded);
    }

    public function testUtf8ToLatin1All()
    {
        $this->assertEquals(
            'ISO-8859-1',
            mb_detect_encoding($this->latinString, 'ISO-8859-1, UTF-8, WINDOWS-1251, ASCII', true),
            'Setup latinString is not ISO-8859-1 encoded...'
        );

        $string = utf8_encode($this->latinString);

        $encoded = $this->utfToLatin($string);

        $this->assertEquals($this->latinString, $encoded);
    }

    public function testUtf8ToLatin1Ascii()
    {
        $string = 'a.b.c.z.';

        $encoded = $this->utfToLatin($string);

        $this->assertEquals(utf8_decode('a.b.c.z.'), $encoded);
    }

    public function testUtf8ToLatin1EuroSymbol()
    {
        $string = 'a.b.c.å.ä.ö.€.';

        $encoded = $this->utfToLatin($string);

        $this->assertEquals(utf8_decode('a.b.c.å.ä.ö.&#8364;.'), $encoded);
    }

    public function testLatin1ToUtf8EuroSymbol()
    {
        $string = utf8_decode('a.b.c.å.ä.ö.&#8364;.');

        $encoded = $this->latinToUtf($string);

        $this->assertEquals('a.b.c.å.ä.ö.€.', $encoded);
    }

    public function testUtf8ToLatin1Runes()
    {
        $string = $this->runes;

        $encoded = $this->utfToLatin($string);

        $this->assertEquals('', $encoded); // @FIXME
    }

    public function testUtf8ToLatin1Greek()
    {
        $string = $this->greek;

        $encoded = $this->utfToLatin($string);

        $this->assertEquals('', $encoded); // @FIXME
    }

    public function testUtf8ToLatin1Russian()
    {
        $string = $this->russian;

        $encoded = $this->utfToLatin($string);

        $this->assertEquals('', $encoded); // @FIXME
    }

    public function testUtf8ToLatin1Chinese()
    {
        $string = $this->chinese;

        $encoded = $this->utfToLatin($string);

        $this->assertEquals('', $encoded); // @FIXME
    }

    public function testUtf8ToLatin1Polish()
    {
        $string = $this->polish;

        $encoded = $this->utfToLatin($string);

        $this->assertEquals('', $encoded); // @FIXME
    }
}
