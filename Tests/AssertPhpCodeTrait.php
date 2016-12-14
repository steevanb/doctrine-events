<?php

namespace steevanb\DoctrineEvents\Tests;

trait AssertPhpCodeTrait
{
    /**
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     * @param float $delta
     * @param int $maxDepth
     * @param bool $canonicalize
     * @param bool $ignoreCase
     */
    abstract protected function assertEquals(
        $expected,
        $actual,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    );

    /**
     * @param string $class
     * @param string $method
     * @param string $originalPhpCodeFile
     */
    protected function assertPhpCode($class, $method, $originalPhpCodeFile)
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);
        $lines = file($reflectionMethod->getFileName());
        $phpCode = implode(
            null,
            array_slice(
                $lines,
                $reflectionMethod->getStartLine(),
                $reflectionMethod->getEndLine() - $reflectionMethod->getStartLine()
            )
        );

        $this->assertEquals(
            $phpCode,
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $originalPhpCodeFile),
            'Php code has changed for ' . $class . '::' . $method . '().'
        );
    }
}
