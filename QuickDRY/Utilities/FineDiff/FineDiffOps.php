<?php
namespace QuickDRY\Utilities\FineDiff;

/**
 * FineDiff ops
 *
 * Collection of ops
 */
class FineDiffOps
{
    public array $edits = [];

    /**
     * @param $opcode
     * @param $from
     * @param $from_offset
     * @param $from_len
     * @return void
     */
    public function appendOpcode($opcode, $from, $from_offset, $from_len): void
    {
        if ($opcode === 'c') {
            $this->edits[] = new FineDiffCopyOp($from_len);
        } elseif ($opcode === 'd') {
            $this->edits[] = new FineDiffDeleteOp($from_len);
        } else /* if ( $opcode === 'i' ) */ {
            $this->edits[] = new FineDiffInsertOp(substr($from, $from_offset, $from_len));
        }
    }
}