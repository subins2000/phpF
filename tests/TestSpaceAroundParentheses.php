<?php

class TestSpaceAroundParentheses extends PHPUnit_Framework_TestCase
{
    /**
     * @var string Code to test
     */
    private $code = <<<'CODE'
<?php
foo('a.php');
foo();
array(
	'a' => array(
		1, 2,
	),
);
?>
CODE;

    public function testEnabled()
    {
        $output = executeCommand(
            array(
                '--passes' => 'SpaceAroundParentheses',
            ),
            $this->code
        );

        $this->assertContains("foo( 'a.php' );", $output);
        $this->assertContains('foo();', $output);
        $this->assertContains(
            <<<'CODE'
array(
	'a' => array(
		1, 2,
	),
);
CODE
            , $output
        );
    }

    public function testDisabled()
    {
        $output = executeCommand(
            array(
                '--exclude' => 'SpaceAroundParentheses',
            ),
            $this->code
        );

        $this->assertContains("foo('a.php');", $output);
        $this->assertContains('foo();', $output);
    }
}
