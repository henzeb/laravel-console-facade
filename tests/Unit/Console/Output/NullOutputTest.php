<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;

use Exception;
use Henzeb\Console\Output\NullOutput;
use PHPUnit\Framework\TestCase;

class NullOutputTest extends TestCase
{
    protected function isWindows(): bool
    {
        return is_readable('NUL');
    }

    public function testGetStream()
    {
        $output = new NullOutput();
        $expected = $output->getStream();
        $details = stream_get_meta_data($expected);

        $this->assertEquals(
            $this->isWindows() ? 'NUL' : '/dev/null',
            $details['uri']
        );

        $this->assertEquals('rw+', $details['mode']);

        $this->assertSame($expected, $output->getStream());
    }

    public function testIsWindows()
    {
        $output = new class extends NullOutput {
            public function isWindows(): bool
            {
                return parent::isWindows();
            }
        };

        $this->assertEquals($output->isWindows(), $this->isWindows());
    }

    public function testGetStreamOnWindows()
    {
        set_error_handler(function () {
            throw new Exception();
        });
        $output = new class extends NullOutput {
            public function isWindows(): bool
            {
                return true;
            }
        };

        if ($this->isWindows()) {
            $this->assertEquals('NUL', stream_get_meta_data($output->getStream())['uri']);
        } else {
            $this->expectException(Exception::class);

            $this->assertFalse($output->getStream());
        }
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }
}
