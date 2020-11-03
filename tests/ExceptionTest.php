<?php

namespace Jobby\Tests;

use Jobby\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers Jobby\Exception
 */
class ExceptionTest extends TestCase
{
    public function testInheritsBaseException()
    {
        $e = new Exception();
        $this->assertInstanceOf(\Exception::class, $e);
    }
}
