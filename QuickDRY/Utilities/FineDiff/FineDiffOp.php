<?php
namespace QuickDRY\Utilities\FineDiff;

/**
 *
 */
abstract class FineDiffOp
{
    /**
     * @return mixed
     */
    abstract public function getFromLen(): mixed;

    /**
     * @return mixed
     */
    abstract public function getToLen(): mixed;

    /**
     * @return mixed
     */
    abstract public function getOpcode(): mixed;
}