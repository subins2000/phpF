<?php

namespace Fmt;

final class PsrDecorator
{
    public static function PSR1(CodeFormatter $fmt)
    {
        $fmt->enablePass('PSR1OpenTags');
        $fmt->enablePass('PSR1BOMMark');
        $fmt->enablePass('PSR1ClassConstants');
        $fmt->disablePass('ReindentComments');
    }

    public static function PSR1Naming(CodeFormatter $fmt)
    {
        $fmt->enablePass('PSR1ClassNames');
        $fmt->enablePass('PSR1MethodNames');
        $fmt->disablePass('ReindentComments');
    }

    public static function PSR2(CodeFormatter $fmt)
    {
        $fmt->enablePass('PSR2KeywordsLowerCase');
        $fmt->enablePass('PSR2IndentWithSpace');
        $fmt->enablePass('PSR2LnAfterNamespace');
        $fmt->enablePass('PSR2CurlyOpenNextLine');
        $fmt->enablePass('PSR2ModifierVisibilityStaticOrder');
        $fmt->enablePass('PSR2SingleEmptyLineAndStripClosingTag');
        $fmt->enablePass('ReindentSwitchBlocks');
        $fmt->disablePass('ReindentComments');
        $fmt->disablePass('StripNewlineWithinClassBody');
    }

    public static function decorate(CodeFormatter $fmt)
    {
        self::PSR1($fmt);
        self::PSR1Naming($fmt);
        self::PSR2($fmt);
    }
}
