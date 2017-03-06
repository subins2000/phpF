<?php

class TestAlignPHPCode extends PHPUnit_Framework_TestCase
{
    /**
     * @var string Code to test
     */
    private $code = <<<'CODE'
<?php
require 'foo.php';
?>
<div>
<?php
echo 'foo';
?>
</div>
CODE;

    public function testEnabled()
    {
        $output = executeCommand(
            array(
                '--passes' => 'AlignPHPCode',
            ),
            $this->code
        );

        $expected_result = <<<'CODE'
<?php
	require 'foo.php';
?>
<div>
<?php
	echo 'foo';
?>
</div>
CODE;

        $this->assertContains($expected_result, $output);
    }

    public function testDisabled()
    {
        $output = executeCommand(
            array(
                '--exclude' => 'AlignPHPCode',
            ),
            $this->code
        );

        $expected_result = <<<'CODE'
<?php
require 'foo.php';
?>
<div>
<?php
echo 'foo';
?>
</div>
CODE;

        $this->assertContains($expected_result, $output);
    }
}
