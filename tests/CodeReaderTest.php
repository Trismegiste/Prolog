<?php

use Trismegiste\Prolog\CodeReader;

/**
 * Test for CodeReader and writer
 *
 * @author flo
 */
class CodeReaderTest extends \PHPUnit\Framework\TestCase
{

    public function testCompile()
    {
        $tempFile = tempnam('.', 'wam');
        CodeReader::prologToWamCode(__DIR__ . '/fixtures/basket.pro', $tempFile);
        $this->assertStringStartsWith('total', file_get_contents($tempFile));
        
        return $tempFile;
    }

    /**
     * @depends testCompile
     */
    public function testReading($wamFile)
    {
        $p = CodeReader::readProgram($wamFile);
        $tempFile = tempnam('.', 'wam');
        CodeReader::writeProgram($p, $tempFile);
        $this->assertFileEquals($wamFile, $tempFile);
        unlink($wamFile);
        unlink($tempFile);
    }

}
