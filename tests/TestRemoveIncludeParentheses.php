<?php

class TestRemoveIncludeParentheses extends PHPUnit_Framework_TestCase
{
    /**
     * @var string Code to test
     */
    private $code = <<<'CODE'
<?php
include ('a.php');
require ('a.php');
?>
CODE;

    public function testEnabled()
    {
        $output = executeCommand(
            array(
                '--passes' => 'RemoveIncludeParentheses',
            ),
            $this->code
        );

        $this->assertContains("include 'a.php';", $output);
        $this->assertContains("require 'a.php';", $output);
    }

    public function testDisabled()
    {
        $output = executeCommand(
            array(
                '--exclude' => 'RemoveIncludeParentheses',
            ),
            $this->code
        );

        $this->assertContains("include ('a.php');", $output);
        $this->assertContains("require ('a.php');", $output);
    }
}
