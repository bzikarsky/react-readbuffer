<?php

use React\Stream\Stream;
use React\Promise\Promise;
use function Zikarsky\React\ReadBuffer\read;
use function Zikarsky\React\ReadBuffer\read_bytes;
use function Zikarsky\React\ReadBuffer\read_line;

class ReadTest extends PHPUnit_Framework_TestCase
{

    private $stream;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
        ;
    }

    public function testReadGetsData()
    {
        $this->assertResolved("Hello world!", $this->readWithAssertingCondition(['Hel', 'lo w', 'orld!'], true));
        $this->assertNoListeners();

        $this->assertResolved("Hello", $this->readWithAssertingCondition(['Hello'], true));
        $this->assertNoListeners();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Test
     */
    public function testErrorThrowsExceptionWithoutData()
    {
        read($this->stream, function () {});
        $this->stream->emit('error', ['Test']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Test
     */
    public function testErrorThrowsExceptionWithPartialData()
    {
        $counter = 0;
        read($this->stream, function () use (&$counter) { $counter++; });
        $this->stream->emit('data', ['Hello']);
        $this->assertEquals(1, $counter);
        $this->stream->emit('error', ['Test']);
    }

    public function testCloseRejects()
    {
        $promise = $this->readWithAssertingCondition(['Fo', 'o', 'Ba'], false);
        $this->stream->emit('close', [$this->stream]);

        $this->assertRejected("FooBa", $promise);
        $this->assertNoListeners();
    }

    public function testEndRejects()
    {
        $promise = $this->readWithAssertingCondition(['Fo', 'o', 'Ba'], false);
        $this->stream->emit('end', [$this->stream]);

        $this->assertRejected("FooBa", $promise);
        $this->assertNoListeners();
    }

    public function testReadBytes()
    {
        $promise = read_bytes($this->stream, 4);
        $this->stream->emit('data', ['He']);
        $this->stream->emit('data', ['llo']);

        $this->assertResolved("Hello", $promise);
        $this->assertNoListeners();

        $promise = read_bytes($this->stream, 4);
        $this->stream->emit('data', ['Hell']);

        $this->assertResolved("Hell", $promise);
        $this->assertNoListeners();
    }

    public function testReadLine()
    {
        $promise = read_line($this->stream);
        $this->stream->emit('data', ['He']);
        $this->stream->emit('data', ['llo' . PHP_EOL]);

        $this->assertResolved("Hello" . PHP_EOL, $promise);
        $this->assertNoListeners();

        $promise = read_line($this->stream, "\r\n");
        $this->stream->emit('data', ["Hell\r\n"]);

        $this->assertResolved("Hell\r\n", $promise);
        $this->assertNoListeners();

        $promise = read_line($this->stream);
        $this->stream->emit('data', ['Hel']);
        $this->stream->emit('data', ['o' . PHP_EOL . 'overcommit']);
        
        $this->assertResolved('Helo' . PHP_EOL . 'overcommit', $promise);
        $this->assertNoListeners();
    }

    private function readWithAssertingCondition(array $sends, $validates = true)
    {
        $counter = 0;
        $promise = read($this->stream, function($data, $new) use ($sends, $validates, &$counter) {
            $this->assertEquals($sends[$counter], $new);            

            if (++$counter == count($sends)) {
                return $validates;
            }

            return false;
        });

        foreach ($sends as $send) {
            $this->assertListeners();
            $this->stream->emit('data', [$send]);
        }

        $this->assertEquals(count($sends), $counter);

        return $promise;
    }

    private function assertResolved($expectedBuffer, Promise $promise)
    {
        $resolvedData = null;
        $promise->then(function ($data) use (&$resolvedData) {
            $resolvedData = $data;
        });

        $this->assertEquals($expectedBuffer, $resolvedData);
    }

    private function assertRejected($expected, Promise $promise)
    {
        $rejected = null;
        $promise->then(null, function($data) use (&$rejected) {
            $rejected = $data;
        });

        $this->assertEquals($expected, $rejected);
    }

    private function assertNoListeners()
    {
        $this->assertCount(0, $this->stream->listeners('data'));
        $this->assertCount(0, $this->stream->listeners('end'));
        $this->assertCount(0, $this->stream->listeners('error'));
        $this->assertCount(0, $this->stream->listeners('close'));
    }

    private function assertListeners()
    {
        $this->assertCount(1, $this->stream->listeners('data'));
        $this->assertCount(1, $this->stream->listeners('end'));
        $this->assertCount(1, $this->stream->listeners('error'));
        $this->assertCount(1, $this->stream->listeners('close'));
    }
}
