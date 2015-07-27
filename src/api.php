<?php

namespace Zikarsky\React\ReadBuffer;

use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface as Stream;

/**
 * Reads data into an internal buffer until $condition returns true
 *
 * Every time the stream emits the `data`-event, $condition is executed with
 * $buffer (all bytes read until this point) and $new (the new bytes added to
 * the buffer this event) as arguments.
 * If the condition returns true, all event-listener are removed from the
 * stream and the returned promise is resolved.
 * If the condition returns false, the reader continues to read bytes into the
 * buffer.
 * In case the stream closes or ends the promise is rejected. An error on the
 * stream leads to a runtime exception
 *
 * @param  Stream   $stream An instance of ReadableStreamInterface
 * @param  callable $condition signature: function(string $buffer, string $new): bool
 * @return Promise  Resolves on successful read, rejects on end/close
 */
function read(Stream $stream, callable $condition)
{
    $deferred = new Deferred();
    $listeners = [];
    $buffer = "";

    _install_read_handler($stream, $deferred, $condition, $buffer, $listeners);
    _install_incomplete_read_handler($stream, $deferred, $buffer, $listeners);
    _install_error_handler($stream, $listeners);

    return $deferred->promise();
}

/**
 * Reads until AT LEAST a number of bytes got fed into the buffer
 *
 * Waits until at least $n bytes were read -- If more are returned with one
 * `data` chunk, the additional bytes are also included in the resolved
 * promise.
 * End/close on the stream before the required amount of bytes has been read
 * will lead to a rejected promise.
 * Errors on the stream will trigger exceptions.
 *
 * @param  Stream   $stream
 * @param  int      $n the minimum number of bytes to be read
 * @return Promise  Resolves when at least $n bytes have beed read
 */
function read_bytes(Stream $stream, $n)
{
    return read($stream, function ($data, $new) use ($n) {
        return strlen($data) >= $n;
    });
}

/**
 * Reads until a newline marker is encountered
 *
 * Reads until a newline marker (defaults to PHP_EOL) is encountered;
 * additional bytes after the marker will be included in `resolve` if
 * they are passed in the same `data` event.
 * End/close on the stream before the required amount of bytes has been read
 * will lead to a rejected promise.
 * Errors on the stream will trigger exceptions.
 *
 * @param  Stream $stream
 * @param  string $newline Newline marker - defaults to PHP_EOL
 * @return Promise Resolves when the newline marker is encountered
 */
function read_line(Stream $stream, $newline = PHP_EOL)
{
    return read($stream, function($data, $new) use ($newline) {
        return strpos($new, $newline) !== false;
    });
}
