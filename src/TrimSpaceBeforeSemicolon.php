<?php

namespace Fmt;

final class TrimSpaceBeforeSemicolon extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $isComment = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_SEMI_COLON:
                    if (!$this->leftTokenIs([T_COMMENT, T_DOC_COMMENT])) {
                        $this->rtrimAndAppendCode($text);
                        break;
                    }

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Remove empty lines before semi-colon.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
echo 1
;

// To
echo 1;
?>
EOT;
    }
}
