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
     * @dataProvider overrideProvider
     */
    public function testOverride($f_base, $f_short, $f_params, $ok, $f_result)
    {
        $client = Track::factory([ 'baseurl' => $f_base ]);

        if (!$ok) {
            $this->expectException(\Ambassify\Track\Exception\TrackException::class);
        }

        $result = $client->override($f_short, $f_params);
        $this->assertEquals($f_result, $result);
    }

    /**
     * @dataProvider strictOverrideProvider
     */
    public function testStrictOverride($f_base, $f_short, $f_params, $ok, $f_result)
    {
        $client = Track::factory([ 'baseurl' => $f_base, 'strict' => true ]);

        if (!$ok) {
            $this->expectException(\Ambassify\Track\Exception\TrackException::class);
        }

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

    public function overrideProvider()
    {
        return [
            [ null, 'a', [ 'foo' => 'bar' ], false, null ],
            [ 'baz.it', 'a', [ 'foo' => 'bar' ], true, 'baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ null, 'http://baz.it/r/a', [ 'foo' => 'bar' ], true, 'http://baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ 'https://bar.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ], true, 'https://bar.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ null, 'r-?a', [], false, null ],
            [ null, 'http://grger.it/r/-?a', [], false, null ]
        ];
    }

    public function strictOverrideProvider()
    {
        return [
            [ 'baz.it', 'a', [ 'foo' => 'bar' ], true, 'baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ 'http://baz.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ], true, 'http://baz.it/r/a/eyJmb28iOiJiYXIifQ' ],
            [ 'https://bar.it', 'http://baz.it/r/a', [ 'foo' => 'bar' ], false, null ]
        ];
    }
}
?>
