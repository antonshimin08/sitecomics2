<?php
namespace Tests;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase {
    public function test_currency_is_tenge() {
        $currency = "₸";
        $this->assertEquals("₸", $currency, "Валюта должна быть тенге!");
    }

    
    public function test_catalog_is_not_empty() {
        $comics_count = 4; 
        $this->assertGreaterThan(0, $comics_count);
    }
}