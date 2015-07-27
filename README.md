

# Utility function for buffered reads with ReactPHP's stream component

## Overview

This micro library provides functions for buffered reads with `react/stream`. 
The following functions are provided:

    use React\Stream\ReadableStreamInterface;
    use React\Promise\Promise;

    function read(ReadableStreamInterface $stream, callable $condition): Promise;
    function read_bytes(ReadableStreamInterface $stream, int $n): Promise;
    function read_line(ReadableStreamInterface $stream, string $eol = PHP_EOL): Promise;

*Attention*: The functions will validate their condition with the full amount of bytes 
passed as one `data`-event-chunk. This means `read($stream, 8)` will resolve with at least 
8 bytes. Tt's also possible that 1024 bytes are "returned" if the underlying stream chunks
with that size and 1024 bytes are available.

## Installation

Install with composer: `composer require bzikarsky/react-readbuffer`

