<?php
namespace QuickDRY\Utilities\FineDiff;

class FineDiffDeleteOp extends FineDiffOp
{
    private int $fromLen;

    public function __construct($len)
    {
        $this->fromLen = $len;
    }

    public function getFromLen(): int
    {
        return $this->fromLen;
    }

    public function getToLen(): int
    {
        return 0;
    }

    public function getOpcode(): string
    {
        if ($this->fromLen === 1) {
            return 'd';
        }
        return 'd' . $this->fromLen;
    }
}