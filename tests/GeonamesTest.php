<?php
use PHPUnit\Framework\TestCase;
use MichaelDrennen\Geonames;

class GeonamesTest extends TestCase {
    public function testEmptyInputString() {
        $isCusip = CUSIP::isCUSIP('');
        $this->assertFalse($isCusip);
    }

    public function testInvalidInputString() {
        $isCusip = CUSIP::isCUSIP('notValidCusip');
        $this->assertFalse($isCusip);
    }

    public function testValidInputString() {
        $isCusip = CUSIP::isCUSIP('222386AA2');
        $this->assertTrue($isCusip);
    }

    public function testValidInputStringWithWhitespace() {
        $isCusip = CUSIP::isCUSIP(' 222386AA2 ');
        $this->assertTrue($isCusip);
    }
}