<?php
namespace QuickDRY\Utilities\FineDiff;

/**
 *
 */
class FineDiffCopyOp extends FineDiffOp
{
    private int $len;

    /**
     * @param $len
     */
    public function __construct($len)
    {
        $this->len = $len;
    }

    /**
     * @return int
     */
    public function getFromLen(): int
    {
        return $this->len;
    }

    /**
     * @return int
     */
    public function getToLen(): int
    {
        return $this->len;
    }

    /**
     * @return string
     */
    public function getOpcode(): string
    {
        if ($this->len === 1) {
            return 'c';
        }
        return 'c' . $this->len;
    }

    /**
     * @param $size
     * @return mixed
     */
    public function increase($size): mixed
    {
        return $this->len += $size;
    }
}