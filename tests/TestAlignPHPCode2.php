<?php

class TestAlignPHPCode2 extends PHPUnit_Framework_TestCase
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
if ('foo' === 'bar'){

}
	?>
</div>
CODE;

    public function testEnabled()
    {
        $output = executeCommand(
            array(
                '--passes' => 'AlignPHPCode2',
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
	if ('foo' === 'bar') {

	}
	?>
</div>
CODE;

        $this->assertContains($expected_result, $output);
    }

    public function testDisabled()
    {
        $output = executeCommand(
            array(
                '--exclude' => 'AlignPHPCode2',
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
if ('foo' === 'bar') {

}
?>
</div>
CODE;

        $this->assertContains($expected_result, $output);
    }
}
