<?php

namespace Zikarsky\React\ReadBuffer;

use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface as Stream;
use RuntimeException;

/**
 * Installs a handler which rejects the given deferred on stream close/end
 *
 * All bytes read until the close/end event will be passed in to the rejection handler
 * It will also remove all listeners defined in $listenrs from the stream.
 *
 * @param  Stream   $stream
 * @param  Deferred $deferred
 * @param  string   &$buffer
 * @param  array    &$listeners
 */
function _install_incomplete_read_handler(Stream $stream, Deferred $deferred, &$buffer, array &$listeners)
{
    $handler = function (Stream $stream) use ($deferred, &$buffer, &$listeners) {
        _remove_listeners($stream, $listeners);
        $deferred->reject($buffer);
    };

    $listeners['close'] = $handler;
    $listeners['end'] = $handler;

    $stream->on('close', $handler);
    $stream->on('end', $handler);
}

/**
 * Installs an error handler which will remove all listeners and trigger and exception
 *
 * @param Stream $stream
 * @param array  &$listeners
 */
function _install_error_handler(Stream $stream, array &$listeners)
{
    $handler = function ($error) use ($stream, &$listeners) {
        _remove_listeners($stream, $listeners);
        throw new RuntimeException($error);
    };

    $listeners['error'] = $handler;
    $stream->on('error', $handler);
}

/**
 * Installs a read handler which reads until the given condition is true
 *
 * If the read-condition is fulfilled all listeners are removed the the
 * deferred return is resolved with the complete buffer.
 *
 * @param Stream    $stream
 * @param Deferred  $deferred
 * @param callable  $condition
 * @param string    &$buffer
 * @param array     &$listeners
 */
function _install_read_handler(Stream $stream, Deferred $deferred, callable $condition, &$buffer, array &$listeners)
{
    $handler = function($data) use ($stream, $deferred, $condition, &$buffer, &$listeners) {
        $buffer .= $data;
        
        if (!$condition($buffer, $data)) {
            return;
        }

        _remove_listeners($stream, $listeners);
        $deferred->resolve($buffer);
    };

    $listeners['data'] = $handler;
    $stream->on('data', $handler);
}

/**
 * Removes all given listeners from the stream
 *
 * @param Stream $stream
 * @param array  &$listeners
 */
function _remove_listeners(Stream $stream, array $listeners)
{
    foreach ($listeners as $event => $callable) {
        $stream->removeListener($event, $callable);
    }
}
