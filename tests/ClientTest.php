<?php

use Ambassify\Track\Client as Track;

class ClientTest extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $client = Track::factory();
        $this->assertInstanceOf(Track::class, $client);
    }

    /**
     * @expectedException \Ambassify\Track\Exception\ConfigException
     */
    public function testStrictConstructor()
    {
        Track::factory([ 'strict' => true ]);
    }

    /**
     * @dataProvider overrideOkProvider
     */
    public function testOverrideOk($f_strict, $f_base, $f_short, $f_params, $f_result)
    {
        $client = Track::factory([ 'baseurl' => $f_base, 'strict' => $f_strict ]);
        $result = $client->override($f_short, $f_params);
        $this->assertEquals($f_result, $result);
    }

    /**
     * @dataProvider overrideFailProvider
     * @expectedException \Ambassify\Track\Exception\TrackException
     */
    public function testOverrideFail($f_strict, $f_base, $f_short, $f_params)
    {
        $client = Track::factory([ 'baseurl' => $f_base, 'strict' => $f_strict ]);
        $result = $client->override($f_short, $f_params);
        $this->assertEquals($f_result, $result);
    }

    public function testParameterConversion()
    {
        $client = Track::factory();
        $test = [ 'foo' => 'bar', 'baz' => true ];
        $result = $client->decode_parameters($client->encode_parameters($test));

        $this->assertEquals($test, $result);
    }

    public function overrideOkProvider()
    {
        return [
            [ false, 'baz.it', 'a', [ 'foo' => 'bar' ], 'baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ false, null, 'http://baz.it/r/a', [ 'foo' => 'bar' ], 'http://baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ false, 'https://bar.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ], 'https://bar.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ true, 'baz.it', 'a', [ 'foo' => 'bar' ], 'baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ true, 'http://baz.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ], 'http://baz.it/r/a/eyJmb28iOiJiYXIifQ' ]
        ];
    }

    public function overrideFailProvider()
    {
        return [
            [ false, null, 'a', [ 'foo' => 'bar' ] ],
            [ false, null, 'r-?a', [] ],
            [ false, null, 'http://grger.it/r/-?a', [] ],
            [ true, 'https://bar.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ] ]
        ];
    }
}
?>
