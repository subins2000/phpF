<?php

class TestSpaceAroundExclamationMark extends PHPUnit_Framework_TestCase
{
    /**
     * @var string Code to test
     */
    private $code = <<<'CODE'
<?php
if (!true) foo();
if ( ! $foo)
?>
CODE;

    public function testEnabled()
    {
        $output = executeCommand(
            array(
                '--passes' => 'SpaceAroundExclamationMark',
            ),
            $this->code
        );

        $this->assertContains('if ( ! true)', $output);
        $this->assertContains('if ( ! $foo)', $output);
    }

    public function testDisabled()
    {
        $output = executeCommand(
            array(
                '--exclude' => 'SpaceAroundExclamationMark',
            ),
            $this->code
        );

        $this->assertContains('if (!true)', $output);
        $this->assertContains('if (!$foo)', $output);
    }
}
