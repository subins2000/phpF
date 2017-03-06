<?php

namespace Fmt;

final class ConvertOpenTagWithEcho extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_OPEN_TAG_WITH_ECHO])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (T_OPEN_TAG_WITH_ECHO == $id) {
                $text = '<?php echo ';
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Convert from "<?=" to "<?php echo ".';
    }

    public function getExample()
    {
        return <<<'EOT'
<?="Hello World"?>

<?php echo "Hello World"?>
EOT;
    }
}
