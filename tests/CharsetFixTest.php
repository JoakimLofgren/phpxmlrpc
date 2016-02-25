<?php

use PhpXmlRpc\Helper\Charset;

require_once('CharsetTest.php');

/**
 * @TODO Remove me
 * Just an easier way to test the proposed solution...
 */
class CharsetFixTest extends CharsetTest
{
    protected function latinToUtf($data)
    {
        $escapedData = str_replace(
            array('&', '"', "'", '<', '>'),
            array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'),
            $data
        );

        $escapedData = utf8_encode($escapedData);

        return $escapedData;
    }

    protected function utfToLatin($data)
    {
        $hasMultiByte = !mb_check_encoding($data, 'ASCII')
            && mb_check_encoding($data, 'UTF-8');
        //$hasMultiByte = strlen($data) !== mb_strlen($data);

        if (!$hasMultiByte) {
            return utf8_decode($data);
        }

        //return mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');

        // start U+07FF
        // end U+7FFFFFFF
        $convmap = array(0x07FF, 0x7FFFFFFF, 0, 0x7FFFFFFF);
        return utf8_decode(mb_encode_numericentity($data, $convmap, "UTF-8"));
    }
}
