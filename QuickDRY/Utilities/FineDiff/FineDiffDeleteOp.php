<?php
namespace QuickDRY\Utilities\FineDiff;

/**
 *
 */
class FineDiffDeleteOp extends FineDiffOp
{
    private int $fromLen;

    /**
     * @param $len
     */
    public function __construct($len)
    {
        $this->fromLen = $len;
    }

    /**
     * @return int
     */
    public function getFromLen(): int
    {
        return $this->fromLen;
    }

    /**
     * @return int
     */
    public function getToLen(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getOpcode(): string
    {
        if ($this->fromLen === 1) {
            return 'd';
        }
        return 'd' . $this->fromLen;
    }
}