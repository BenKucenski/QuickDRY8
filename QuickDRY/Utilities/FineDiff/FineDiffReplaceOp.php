<?php
namespace QuickDRY\Utilities\FineDiff;

class FineDiffReplaceOp extends FineDiffOp
{
    private int $fromLen;
    private string $text;

    public function __construct($fromLen, $text)
    {
        $this->fromLen = $fromLen;
        $this->text = $text;
    }

    public function getFromLen(): int
    {
        return $this->fromLen;
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
        if ($this->fromLen === 1) {
            $del_opcode = 'd';
        } else {
            $del_opcode = 'd' . $this->fromLen;
        }
        $to_len = strlen($this->text);
        if ($to_len === 1) {
            return $del_opcode . 'i:' . $this->text;
        }
        return $del_opcode . 'i' . $to_len . ':' . $this->text;
    }
}