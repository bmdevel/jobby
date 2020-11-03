<?php

namespace Jobby\Tests;

use PHPUnit\Framework\TestCase;
use SuperClosure\Serializer;

class SerializerTraitTest extends TestCase
{
    public function testGetSerializer()
    {
        $mock = $this->getObjectForTrait('Jobby\SerializerTrait');
        $method = new \ReflectionMethod($mock, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mock);
        $this->assertInstanceOf(Serializer::class, $serializer);

        $serializer2 = $method->invoke($mock);
        $this->assertSame($serializer, $serializer2);
    }
}
