<?php

namespace Fmt;

abstract class BaseCodeFormatter
{
    protected $passes = [
        'StripSpaces' => false,

        'ReplaceBooleanAndOr' => false,
        'EliminateDuplicatedEmptyLines' => false,

        'RTrim' => false,
        'WordWrap' => false,

        'ConvertOpenTagWithEcho' => false,
        'RestoreComments' => false,
        'UpgradeToPreg' => false,
        'DocBlockToComment' => false,
        'LongArray' => false,

        'StripExtraCommaInArray' => false,
        'NoSpaceAfterPHPDocBlocks' => false,
        'RemoveUseLeadingSlash' => false,
        'ShortArray' => false,
        'MergeElseIf' => false,
        'SplitElseIf' => false,
        'AutoPreincrement' => false,
        'MildAutoPreincrement' => false,

        'CakePHPStyle' => false,

        'StripNewlineAfterClassOpen' => false,
        'StripNewlineAfterCurlyOpen' => false,

        'SortUseNameSpace' => false,
        'SpaceAroundExclamationMark' => false,
        'SpaceAroundParentheses' => false,

        'TightConcat' => false,

        'PSR2IndentWithSpace' => false,
        'AlignPHPCode' => false,
        'AlignPHPCode2' => false,
        'AllmanStyleBraces' => false,
        'NamespaceMergeWithOpenTag' => false,
        'MergeNamespaceWithOpenTag' => false,

        'LeftAlignComment' => false,

        'PSR2AlignObjOp' => false,
        'PSR2EmptyFunction' => false,
        'PSR2SingleEmptyLineAndStripClosingTag' => false,
        'PSR2ModifierVisibilityStaticOrder' => false,
        'PSR2CurlyOpenNextLine' => false,
        'PSR2LnAfterNamespace' => false,
        'PSR2KeywordsLowerCase' => false,

        'PSR1MethodNames' => false,
        'PSR1ClassNames' => false,

        'PSR1ClassConstants' => false,
        'PSR1BOMMark' => false,

        'EliminateDuplicatedEmptyLines' => false,
        'IndentTernaryConditions' => false,
        'ReindentComments' => false,
        'ReindentEqual' => false,
        'Reindent' => false,
        'ReindentAndAlignObjOps' => false,
        'ReindentObjOps' => false,

        'AlignDoubleSlashComments' => false,
        'AlignTypehint' => false,
        'AlignGroupDoubleArrow' => false,
        'AlignDoubleArrow' => false,
        'AlignEquals' => false,
        'AlignConstVisibilityEquals' => false,

        'ReindentSwitchBlocks' => false,
        'ReindentColonBlocks' => false,

        'SplitCurlyCloseAndTokens' => false,
        'ResizeSpaces' => false,

        'StripSpaceWithinControlStructures' => false,

        'StripExtraCommaInList' => false,
        'YodaComparisons' => false,

        'MergeDoubleArrowAndArray' => false,
        'MergeCurlyCloseAndDoWhile' => false,
        'MergeParenCloseWithCurlyOpen' => false,
        'NormalizeLnAndLtrimLines' => false,
        'ExtraCommaInArray' => false,
        'SmartLnAfterCurlyOpen' => false,
        'AddMissingCurlyBraces' => false,
        'OnlyOrderUseClauses' => false,
        'OrderAndRemoveUseClauses' => false,
        'AutoImportPass' => false,
        'ConstructorPass' => false,
        'SettersAndGettersPass' => false,
        'NormalizeIsNotEquals' => false,
        'RemoveIncludeParentheses' => false,
        'TwoCommandsInSameLine' => false,

        'SpaceBetweenMethods' => false,
        'GeneratePHPDoc' => false,
        'ReturnNull' => false,
        'AddMissingParentheses' => false,
        'WrongConstructorName' => false,
        'JoinToImplode' => false,
        'EncapsulateNamespaces' => false,
        'PrettyPrintDocBlocks' => false,
        'StrictBehavior' => false,
        'StrictComparison' => false,
        'ReplaceIsNull' => false,
        'DoubleToSingleQuote' => false,
        'LeftWordWrap' => false,
        'ClassToSelf' => false,
        'ClassToStatic' => false,
        'PSR2MultilineFunctionParams' => false,
        'SpaceAroundControlStructures' => false,

        'OrderMethodAndVisibility' => false,
        'OrderMethod' => false,
        'OrganizeClass' => false,
        'AutoSemicolon' => false,
        'PSR1OpenTags' => false,
        'PHPDocTypesToFunctionTypehint' => false,
        'RemoveSemicolonAfterCurly' => false,
        'NewLineBeforeReturn' => false,
        'EchoToPrint' => false,
        'TrimSpaceBeforeSemicolon' => false,
        'StripNewlineWithinClassBody' => false,
    ];

    private $hasAfterExecutedPass = false;

    private $hasAfterFormat = false;

    private $hasBeforeFormat = false;

    private $hasBeforePass = false;

    private $shortcircuit = [
        'AlignDoubleArrow' => ['AlignGroupDoubleArrow'],
        'AlignGroupDoubleArrow' => ['AlignDoubleArrow'],
        'AllmanStyleBraces' => ['PSR2CurlyOpenNextLine'],
        'OnlyOrderUseClauses' => ['OrderAndRemoveUseClauses'],
        'OrderAndRemoveUseClauses' => ['OnlyOrderUseClauses'],
        'OrganizeClass' => ['ReindentComments', 'RestoreComments'],
        'ReindentAndAlignObjOps' => ['ReindentObjOps'],
        'ReindentComments' => ['OrganizeClass', 'RestoreComments'],
        'ReindentObjOps' => ['ReindentAndAlignObjOps'],
        'RestoreComments' => ['OrganizeClass', 'ReindentComments'],

        'PSR1OpenTags' => ['ReindentComments'],
        'PSR1BOMMark' => ['ReindentComments'],
        'PSR1ClassConstants' => ['ReindentComments'],
        'PSR1ClassNames' => ['ReindentComments'],
        'PSR1MethodNames' => ['ReindentComments'],
        'PSR2KeywordsLowerCase' => ['ReindentComments'],
        'PSR2IndentWithSpace' => ['ReindentComments'],
        'PSR2LnAfterNamespace' => ['ReindentComments'],
        'PSR2CurlyOpenNextLine' => ['ReindentComments'],
        'PSR2ModifierVisibilityStaticOrder' => ['ReindentComments'],
        'PSR2SingleEmptyLineAndStripClosingTag' => ['ReindentComments'],
    ];

    private $shortcircuits = [];

    public function __construct()
    {
        $this->passes['AddMissingCurlyBraces'] = new AddMissingCurlyBraces();
        $this->passes['EliminateDuplicatedEmptyLines'] = new EliminateDuplicatedEmptyLines();
        $this->passes['ExtraCommaInArray'] = new ExtraCommaInArray();
        $this->passes['LeftAlignComment'] = new LeftAlignComment();
        $this->passes['MergeCurlyCloseAndDoWhile'] = new MergeCurlyCloseAndDoWhile();
        $this->passes['MergeDoubleArrowAndArray'] = new MergeDoubleArrowAndArray();
        $this->passes['MergeParenCloseWithCurlyOpen'] = new MergeParenCloseWithCurlyOpen();
        $this->passes['NormalizeIsNotEquals'] = new NormalizeIsNotEquals();
        $this->passes['NormalizeLnAndLtrimLines'] = new NormalizeLnAndLtrimLines();
        $this->passes['OrderAndRemoveUseClauses'] = new OrderAndRemoveUseClauses();
        $this->passes['Reindent'] = new Reindent();
        $this->passes['ReindentColonBlocks'] = new ReindentColonBlocks();
        $this->passes['ReindentComments'] = new ReindentComments();
        $this->passes['ReindentEqual'] = new ReindentEqual();
        $this->passes['ReindentObjOps'] = new ReindentObjOps();
        $this->passes['RemoveIncludeParentheses'] = new RemoveIncludeParentheses();
        $this->passes['ResizeSpaces'] = new ResizeSpaces();
        $this->passes['RTrim'] = new RTrim();
        $this->passes['SplitCurlyCloseAndTokens'] = new SplitCurlyCloseAndTokens();
        $this->passes['StripExtraCommaInList'] = new StripExtraCommaInList();
        $this->passes['TwoCommandsInSameLine'] = new TwoCommandsInSameLine();

        $this->hasAfterExecutedPass = method_exists($this, 'afterExecutedPass');
        $this->hasAfterFormat = method_exists($this, 'afterFormat');
        $this->hasBeforePass = method_exists($this, 'beforePass');
        $this->hasBeforeFormat = method_exists($this, 'beforeFormat');
    }

    public function disablePass($pass)
    {
        $this->passes[$pass] = null;
    }

    public function enablePass($pass)
    {
        $args = func_get_args();
        if (!isset($args[1])) {
            $args[1] = null;
        }

        if (!class_exists($pass)) {
            $passName = sprintf('ExternalPass%s', $pass);
            $passes = array_reverse($this->passes, true);
            $passes[$passName] = new ExternalPass($pass);
            $this->passes = array_reverse($passes, true);

            return;
        }

        if (isset($this->shortcircuits[$pass])) {
            return;
        }

        $this->passes[$pass] = new $pass($args[1]);

        $scPasses = &$this->shortcircuit[$pass];
        if (isset($scPasses)) {
            foreach ($scPasses as $scPass) {
                $this->disablePass($scPass);
                $this->shortcircuits[$scPass] = $pass;
            }
        }
    }

    public function forcePass($pass)
    {
        $this->shortcircuits = [];
        $args = func_get_args();

        return call_user_func_array([$this, 'enablePass'], $args);
    }

    public function formatCode($source = '')
    {
        $passes = array_map(
            function ($pass) {
                return clone $pass;
            },
            array_filter($this->passes)
        );
        list($foundTokens, $commentStack) = $this->getFoundTokens($source);
        $this->hasBeforeFormat && $this->beforeFormat($source);
        while (($pass = array_pop($passes))) {
            $this->hasBeforePass && $this->beforePass($source, $pass);
            if ($pass->candidate($source, $foundTokens)) {
                if (isset($pass->commentStack)) {
                    $pass->commentStack = $commentStack;
                }
                $source = $pass->format($source);
                $this->hasAfterExecutedPass && $this->afterExecutedPass($source, $pass);
            }
        }
        $this->hasAfterFormat && $this->afterFormat($source);

        return $source;
    }

    public function getPassesNames()
    {
        return array_keys(array_filter($this->passes));
    }

    protected function getToken($token)
    {
        $ret = [$token, $token];
        if (isset($token[1])) {
            $ret = $token;
        }

        return $ret;
    }

    private function getFoundTokens($source)
    {
        $foundTokens = [];
        $commentStack = [];
        $tkns = token_get_all($source);
        foreach ($tkns as $token) {
            list($id, $text) = $this->getToken($token);
            $foundTokens[$id] = $id;
            if (T_COMMENT === $id) {
                $commentStack[] = [$id, $text];
            }
        }

        return [$foundTokens, $commentStack];
    }
}
