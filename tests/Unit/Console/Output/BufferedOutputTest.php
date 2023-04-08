<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;

use Henzeb\Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;

class BufferedOutputTest extends TestCase
{
    public function testGetStream()
    {
        $output = new BufferedOutput();
        $expected = $output->getStream();
        $details = stream_get_meta_data($expected);

        $this->assertEquals(
            'php://memory',
            $details['uri']
        );

        $this->assertEquals('w+b', $details['mode']);

        $this->assertSame($expected, $output->getStream());

        $output->__destruct();
        $this->assertFalse(is_resource($expected));
    }

    public function testFetch()
    {
        $output = new BufferedOutput();
        $this->assertEquals('', $output->fetch());

        $output->write('test');
        $this->assertEquals('test', $output->fetch());
        $this->assertEquals('', $output->fetch());

        $output->write('test', true);
        $this->assertEquals('test' . PHP_EOL, $output->fetch());

    }
}
