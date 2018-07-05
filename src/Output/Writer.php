<?php

namespace Ahc\Cli\Output;

/**
 * Cli Writer.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/cli
 */
class Writer
{
    /** @var resource Output file handle */
    protected $stream;

    /** @var resource Error output file handle */
    protected $eStream;

    /** @var string Write method to be relayed to Colorizer */
    protected $method;

    /** @var Color */
    protected $colorizer;

    public function __construct(string $path = null, Color $colorizer = null)
    {
        if ($path) {
            $path = \fopen($path, 'w');
        }

        $this->stream  = $path ?: \STDOUT;
        $this->eStream = $path ?: \STDERR;

        $this->colorizer = $colorizer ?? new Color;
    }

    /**
     * Magically set methods.
     *
     * @param string $name Like `red`, `bgRed`, 'bold', `error` etc
     *
     * @return self
     */
    public function __get(string $name): self
    {
        if (\strpos($this->method, $name) === false) {
            $this->method .= $this->method ? \ucfirst($name) : $name;
        }

        return $this;
    }

    /**
     * Write the formatted text to stdout or stderr.
     *
     * @param string $text
     * @param bool   $eol
     *
     * @return self
     */
    public function write(string $text, bool $eol = false): self
    {
        list($method, $this->method) = [$this->method ?: 'line', ''];

        $text  = $this->colorizer->{$method}($text, []);
        $error = \stripos($method, 'error') !== false;

        if ($eol) {
            $text .= \PHP_EOL;
        }

        return $this->doWrite($text, $error);
    }

    protected function doWrite(string $text, bool $error = false): self
    {
        $stream = $error ? $this->eStream : $this->stream;

        \fwrite($stream, $text);

        return $this;
    }

    public function up(int $n = 1)
    {
        return $this->doWrite(\str_repeat("\e[A", \max($n, 1)));
    }

    public function down(int $n = 1)
    {
        return $this->doWrite(\str_repeat("\e[B", \max($n, 1)));
    }

    public function right(int $n = 1)
    {
        return $this->doWrite(\str_repeat("\e[C", \max($n, 1)));
    }

    public function left(int $n = 1)
    {
        return $this->doWrite(\str_repeat("\e[D", \max($n, 1)));
    }

    public function eol(int $n = 1)
    {
        return $this->doWrite(\str_repeat(PHP_EOL, \max($n, 1)));
    }

    public function raw($text, bool $error = false)
    {
        return $this->doWrite((string) $text, $error);
    }

    /**
     * Write to stdout or stderr magically.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return self
     */
    public function __call(string $method, array $arguments): self
    {
        $this->method = $method;

        return $this->write($arguments[0] ?? '', $arguments[1] ?? false);
    }
}