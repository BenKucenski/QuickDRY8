<?php
namespace QuickDRY\Utilities\FineDiff;

class FineDiffInsertOp extends FineDiffOp
{
    private string $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function getFromLen(): int
    {
        return 0;
    }

    public function getToLen(): int
    {
        return strlen($this->text);
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getOpcode(): string
    {
        $to_len = strlen($this->text);
        if ($to_len === 1) {
            return 'i:' . $this->text;
        }
        return 'i' . $to_len . ':' . $this->text;
    }
}