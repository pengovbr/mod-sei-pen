<?php

use PHPUnit\Framework\TestCase;

final class CompararVersoesTest extends TestCase
{
    public function testCompararVersoes()
    {
        $this->assertTrue(InfraUtil::compararVersoes("0.0.1", "<", "0.0.2"));
        $this->assertTrue(InfraUtil::compararVersoes("0.1.0", "<", "0.2.0"));
        $this->assertTrue(InfraUtil::compararVersoes("1.0.0", "<", "2.0.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3", "==", "4.0.3.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3", "<", "4.0.3.1"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.4", ">", "4.0.3.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3.0", "==", "4.0.3.5", 3, true));
    }
}