<?php
namespace QuickDRY\Utilities\FineDiff;

class FineDiffCopyOp extends FineDiffOp
{
    private int $len;

    public function __construct($len)
    {
        $this->len = $len;
    }

    public function getFromLen(): int
    {
        return $this->len;
    }

    public function getToLen(): int
    {
        return $this->len;
    }

    public function getOpcode(): string
    {
        if ($this->len === 1) {
            return 'c';
        }
        return 'c' . $this->len;
    }

    public function increase($size)
    {
        return $this->len += $size;
    }
}