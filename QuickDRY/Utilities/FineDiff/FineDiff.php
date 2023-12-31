<?php
/**
 * FineDiff class
 *
 * TODO: Document
 *
 */


namespace QuickDRY\Utilities\FineDiff;

/**
 *
 */
class FineDiff
{

    /**------------------------------------------------------------------------
     *
     * Public section
     *
     */
    private int $from_offset;
    private int $stackpointer;
    private string $last_edit;
    /**
     * @var array|mixed|string[]
     */
    private array $granularityStack;
    private array $edits;
    /**
     * @var mixed|string
     */
    private string $from_text;

    /**
     * Constructor
     * ...
     * The $granularityStack allows FineDiff to be configurable so that
     * a particular stack tailored to the specific content of a document can
     * be passed.
     */
    public function __construct($from_text = '', $to_text = '', $granularityStack = null)
    {
        // setup stack for generic text documents by default
        $this->granularityStack = $granularityStack ?: FineDiff::$characterGranularity;
        $this->edits = [];
        $this->from_text = $from_text;
        $this->doDiff($from_text, $to_text);
    }

    /**
     * @return array
     */
    public function getOps(): array
    {
        return $this->edits;
    }

    /**
     * @return string
     */
    public function getOpcodes(): string
    {
        $opcodes = [];
        foreach ($this->edits as $edit) {
            $opcodes[] = $edit->getOpcode();
        }
        return implode('', $opcodes);
    }

    /**
     * @return bool|string
     */
    public function renderDiffToHTML(): bool|string
    {
        $in_offset = 0;
        ob_start();
        foreach ($this->edits as $edit) {
            $n = $edit->getFromLen();
            if ($edit instanceof FineDiffCopyOp) {
                FineDiff::renderDiffToHTMLFromOpcode('c', $this->from_text, $in_offset, $n);
            } elseif ($edit instanceof FineDiffDeleteOp) {
                FineDiff::renderDiffToHTMLFromOpcode('d', $this->from_text, $in_offset, $n);
            } elseif ($edit instanceof FineDiffInsertOp) {
                FineDiff::renderDiffToHTMLFromOpcode('i', $edit->getText(), 0, $edit->getToLen());
            } else /* if ( $edit instanceof FineDiffReplaceOp ) */ {
                FineDiff::renderDiffToHTMLFromOpcode('d', $this->from_text, $in_offset, $n);
                FineDiff::renderDiffToHTMLFromOpcode('i', $edit->getText(), 0, $edit->getToLen());
            }
            $in_offset += $n;
        }
        return ob_get_clean();
    }

    /**------------------------------------------------------------------------
     * Return an opcodes string describing the diff between a "From" and a
     * "To" string
     */
    public static function getDiffOpcodes($from, $to, $granularities = null): string
    {
        $diff = new FineDiff($from, $to, $granularities);
        return $diff->getOpcodes();
    }

    /**------------------------------------------------------------------------
     * Return an iterable collection of diff ops from an opcodes string
     */
    public static function getDiffOpsFromOpcodes($opcodes): array
    {
        $diffops = new FineDiffOps();
        FineDiff::renderFromOpcodes(null, $opcodes, [$diffops, 'appendOpcode']);
        return $diffops->edits;
    }

    /**------------------------------------------------------------------------
     * Re-create the "To" string from the "From" string and an "Opcodes" string
     */
    public static function renderToTextFromOpcodes($from, $opcodes): bool|string
    {
        ob_start();
        FineDiff::renderFromOpcodes($from, $opcodes, ['FineDiff', 'renderToTextFromOpcode']);
        return ob_get_clean();
    }

    /**------------------------------------------------------------------------
     * Render the diff to an HTML string -- UTF8 unsafe
     */
    public static function renderDiffToHTMLFromOpcodes($from, $opcodes): bool|string
    {
        ob_start();
        FineDiff::renderFromOpcodes($from, $opcodes, ['FineDiff', 'renderDiffToHTMLFromOpcode']);
        return ob_get_clean();
    }

    /**------------------------------------------------------------------------
     * Render the diff to an HTML string -- UTF8 safe
     */
    public static function renderUTF8DiffToHTMLFromOpcodes($from, $opcodes): bool|string
    {
        ob_start();
        FineDiff::renderUTF8FromOpcode($from, $opcodes, ['FineDiff', 'renderDiffToHTMLFromOpcode']);
        return ob_get_clean();
    }

    /**------------------------------------------------------------------------
     * Generic opcodes parser, user must supply callback for handling
     * single opcode
     */
    public static function renderFromOpcodes($from, $opcodes, $callback): void
    {
        if (!is_callable($callback)) {
            return;
        }
        $opcodes_len = strlen($opcodes);
        $from_offset = $opcodes_offset = 0;
        while ($opcodes_offset < $opcodes_len) {
            $opcode = substr($opcodes, $opcodes_offset, 1);
            $opcodes_offset++;
            $n = intval(substr($opcodes, $opcodes_offset));
            if ($n) {
                $opcodes_offset += strlen(strval($n));
            } else {
                $n = 1;
            }
            if ($opcode === 'c') { // copy n characters from source
                call_user_func($callback, 'c', $from, $from_offset, $n, '');
                $from_offset += $n;
            } elseif ($opcode === 'd') { // delete n characters from source
                call_user_func($callback, 'd', $from, $from_offset, $n, '');
                $from_offset += $n;
            } else /* if ( $opcode === 'i' ) */ { // insert n characters from opcodes
                call_user_func($callback, 'i', $opcodes, $opcodes_offset + 1, $n);
                $opcodes_offset += 1 + $n;
            }
        }
    }

    /**------------------------------------------------------------------------
     * Generic opcodes parser, user must supply callback for handling
     * single opcode
     */
    private static function renderUTF8FromOpcode($from, $opcodes, $callback = null): void
    {
        if (!is_callable($callback)) {
            return;
        }
        $from_len = strlen($from);
        $opcodes_len = strlen($opcodes);
        $from_offset = $opcodes_offset = 0;
        $last_to_chars = '';
        while ($opcodes_offset < $opcodes_len) {
            $opcode = substr($opcodes, $opcodes_offset, 1);
            $opcodes_offset++;
            $n = intval(substr($opcodes, $opcodes_offset));
            if ($n) {
                $opcodes_offset += strlen(strval($n));
            } else {
                $n = 1;
            }
            if ($opcode === 'c' || $opcode === 'd') {
                $beg = $from_offset;
                $end = $from_offset + $n;
                while ($beg > 0 && (ord($from[$beg]) & 0xC0) === 0x80) {
                    $beg--;
                }
                while ($end < $from_len && (ord($from[$end]) & 0xC0) === 0x80) {
                    $end++;
                }
                if ($opcode === 'c') { // copy n characters from source
                    call_user_func($callback, 'c', $from, $beg, $end - $beg, '');
                    $last_to_chars = substr($from, $beg, $end - $beg);
                } else /* if ( $opcode === 'd' ) */ { // delete n characters from source
                    call_user_func($callback, 'd', $from, $beg, $end - $beg, '');
                }
                $from_offset += $n;
            } else /* if ( $opcode === 'i' ) */ { // insert n characters from opcodes
                $opcodes_offset += 1;
                if (strlen($last_to_chars) > 0 && (ord($opcodes[$opcodes_offset]) & 0xC0) === 0x80) {
                    $beg = strlen($last_to_chars) - 1;
                    while ($beg > 0 && (ord($last_to_chars[$beg]) & 0xC0) === 0x80) {
                        $beg--;
                    }
                    $prefix = substr($last_to_chars, $beg);
                } else {
                    $prefix = '';
                }
                $end = $from_offset;
                while ($end < $from_len && (ord($from[$end]) & 0xC0) === 0x80) {
                    $end++;
                }
                $toInsert = $prefix . substr($opcodes, $opcodes_offset, $n) . substr($from, $end, $end - $from_offset);
                call_user_func($callback, 'i', $toInsert, 0, strlen($toInsert));
                $opcodes_offset += $n;
                $last_to_chars = $toInsert;
            }
        }
    }

    /**
     * Stock granularity stacks and delimiters
     */

    public const paragraphDelimiters = "\n\r";
    public static array $paragraphGranularity = [
        FineDiff::paragraphDelimiters
    ];
    public const sentenceDelimiters = ".\n\r";
    public static array $sentenceGranularity = [
        FineDiff::paragraphDelimiters,
        FineDiff::sentenceDelimiters
    ];
    public const wordDelimiters = " \t.\n\r";
    public static array $wordGranularity = [
        FineDiff::paragraphDelimiters,
        FineDiff::sentenceDelimiters,
        FineDiff::wordDelimiters
    ];
    public const characterDelimiters = '';
    public static array $characterGranularity = [
        FineDiff::paragraphDelimiters,
        FineDiff::sentenceDelimiters,
        FineDiff::wordDelimiters,
        FineDiff::characterDelimiters
    ];

    public static array $textStack = [
        '.',
        " \t.\n\r",
        ''
    ];

    /**------------------------------------------------------------------------
     *
     * Private section
     *
     */

    /**
     * Entry point to compute the diff.
     */
    private function doDiff($from_text, $to_text): void
    {
        $this->last_edit = false;
        $this->stackpointer = 0;
        $this->from_text = $from_text;
        $this->from_offset = 0;
        // can't diff without at least one granularity specifier
        if (empty($this->granularityStack)) {
            return;
        }
        $this->_processGranularity($from_text, $to_text);
    }

    /**
     * This is the recursive function which is responsible for
     * handling/increasing granularity.
     *
     * Incrementally increasing the granularity is key to compute the
     * overall diff in a very efficient way.
     */
    private function _processGranularity($from_segment, $to_segment): void
    {
        $delimiters = $this->granularityStack[$this->stackpointer++];
        $has_next_stage = $this->stackpointer < count($this->granularityStack);
        foreach (FineDiff::doFragmentDiff($from_segment, $to_segment, $delimiters) as $fragment_edit) {
            // increase granularity
            if ($fragment_edit instanceof FineDiffReplaceOp && $has_next_stage) {
                $this->_processGranularity(
                    substr($this->from_text, $this->from_offset, $fragment_edit->getFromLen()),
                    $fragment_edit->getText()
                );
            } // fuse copy ops whenever possible
            elseif ($fragment_edit instanceof FineDiffCopyOp && $this->last_edit instanceof FineDiffCopyOp) {
                $this->edits[count($this->edits) - 1]->increase($fragment_edit->getFromLen());
                $this->from_offset += $fragment_edit->getFromLen();
            } else {
                /* $fragment_edit instanceof FineDiffCopyOp */
                /* $fragment_edit instanceof FineDiffDeleteOp */
                /* $fragment_edit instanceof FineDiffInsertOp */
                $this->edits[] = $this->last_edit = $fragment_edit;
                $this->from_offset += $fragment_edit->getFromLen();
            }
        }
        $this->stackpointer--;
    }

    /**
     * This is the core algorithm which actually perform the diff itself,
     * fragmenting the strings as per specified delimiters.
     *
     * This function is naturally recursive, however for performance purpose
     * a local job queue is used instead of outright recursively.
     */
    private static function doFragmentDiff($from_text, $to_text, $delimiters): array
    {
        // Empty delimiter means character-level diffing.
        // In such case, use code path optimized for character-level
        // diffing.
        if (empty($delimiters)) {
            return FineDiff::doCharDiff($from_text, $to_text);
        }

        $result = [];
        $best_from_start = null;
        $best_to_start = null;

        // fragment-level diffing
        $from_text_len = strlen($from_text);
        $to_text_len = strlen($to_text);
        $from_fragments = FineDiff::extractFragments($from_text, $delimiters);
        $to_fragments = FineDiff::extractFragments($to_text, $delimiters);

        $jobs = [[0, $from_text_len, 0, $to_text_len]];

        $cached_array_keys = [];

        while ($job = array_pop($jobs)) {

            // get the segments which must be diff'ed
            list($from_segment_start, $from_segment_end, $to_segment_start, $to_segment_end) = $job;

            // catch easy cases first
            $from_segment_length = $from_segment_end - $from_segment_start;
            $to_segment_length = $to_segment_end - $to_segment_start;
            if (!$from_segment_length || !$to_segment_length) {
                if ($from_segment_length) {
                    $result[$from_segment_start * 4] = new FineDiffDeleteOp($from_segment_length);
                } elseif ($to_segment_length) {
                    $result[$from_segment_start * 4 + 1] = new FineDiffInsertOp(substr($to_text, $to_segment_start, $to_segment_length));
                }
                continue;
            }

            // find longest copy operation for the current segments
            $best_copy_length = 0;

            $from_base_fragment_index = $from_segment_start;

            $cached_array_keys_for_current_segment = [];

            while ($from_base_fragment_index < $from_segment_end) {
                $from_base_fragment = $from_fragments[$from_base_fragment_index];
                $from_base_fragment_length = strlen($from_base_fragment);
                // performance boost: cache array keys
                if (!isset($cached_array_keys_for_current_segment[$from_base_fragment])) {
                    if (!isset($cached_array_keys[$from_base_fragment])) {
                        $to_all_fragment_indices = $cached_array_keys[$from_base_fragment] = array_keys($to_fragments, $from_base_fragment, true);
                    } else {
                        $to_all_fragment_indices = $cached_array_keys[$from_base_fragment];
                    }
                    // get only indices which falls within current segment
                    if ($to_segment_start > 0 || $to_segment_end < $to_text_len) {
                        $to_fragment_indices = [];
                        foreach ($to_all_fragment_indices as $to_fragment_index) {
                            if ($to_fragment_index < $to_segment_start) {
                                continue;
                            }
                            if ($to_fragment_index >= $to_segment_end) {
                                break;
                            }
                            $to_fragment_indices[] = $to_fragment_index;
                        }
                        $cached_array_keys_for_current_segment[$from_base_fragment] = $to_fragment_indices;
                    } else {
                        $to_fragment_indices = $to_all_fragment_indices;
                    }
                } else {
                    $to_fragment_indices = $cached_array_keys_for_current_segment[$from_base_fragment];
                }
                // iterate through collected indices
                foreach ($to_fragment_indices as $to_base_fragment_index) {
                    $fragment_index_offset = $from_base_fragment_length;
                    // iterate until no more match
                    for (; ;) {
                        $fragment_from_index = $from_base_fragment_index + $fragment_index_offset;
                        if ($fragment_from_index >= $from_segment_end) {
                            break;
                        }
                        $fragment_to_index = $to_base_fragment_index + $fragment_index_offset;
                        if ($fragment_to_index >= $to_segment_end) {
                            break;
                        }
                        if ($from_fragments[$fragment_from_index] !== $to_fragments[$fragment_to_index]) {
                            break;
                        }
                        $fragment_length = strlen($from_fragments[$fragment_from_index]);
                        $fragment_index_offset += $fragment_length;
                    }
                    if ($fragment_index_offset > $best_copy_length) {
                        $best_copy_length = $fragment_index_offset;
                        $best_from_start = $from_base_fragment_index;
                        $best_to_start = $to_base_fragment_index;
                    }
                }
                $from_base_fragment_index += strlen($from_base_fragment);
                // If match is larger than half segment size, no point trying to find better
                // TODO: Really?
                if ($best_copy_length >= $from_segment_length / 2) {
                    break;
                }
                // no point to keep looking if what is left is less than
                // current best match
                if ($from_base_fragment_index + $best_copy_length >= $from_segment_end) {
                    break;
                }
            }

            if ($best_copy_length) {
                $jobs[] = [$from_segment_start, $best_from_start, $to_segment_start, $best_to_start];
                $result[$best_from_start * 4 + 2] = new FineDiffCopyOp($best_copy_length);
                $jobs[] = [$best_from_start + $best_copy_length, $from_segment_end, $best_to_start + $best_copy_length, $to_segment_end];
            } else {
                $result[$from_segment_start * 4] = new FineDiffReplaceOp($from_segment_length, substr($to_text, $to_segment_start, $to_segment_length));
            }
        }

        ksort($result, SORT_NUMERIC);
        return array_values($result);
    }

    /**
     * Perform a character-level diff.
     *
     * The algorithm is quite similar to doFragmentDiff(), except that
     * the code path is optimized for character-level diff -- strpos() is
     * used to find out the longest common subequence of characters.
     *
     * We try to find a match using the longest possible subsequence, which
     * is at most the length of the shortest of the two strings, then incrementally
     * reduce the size until a match is found.
     *
     * I still need to study more the performance of this function. It
     * appears that for long strings, the generic doFragmentDiff() is more
     * performant. For word-sized strings, doCharDiff() is somewhat more
     * performant.
     */
    private static function doCharDiff($from_text, $to_text): array
    {
        $result = [];
        $jobs = [[0, strlen($from_text), 0, strlen($to_text)]];
        while ($job = array_pop($jobs)) {
            // get the segments which must be diff'ed
            list($from_segment_start, $from_segment_end, $to_segment_start, $to_segment_end) = $job;
            $from_segment_len = $from_segment_end - $from_segment_start;
            $to_segment_len = $to_segment_end - $to_segment_start;

            // catch easy cases first
            if (!$from_segment_len || !$to_segment_len) {
                if ($from_segment_len) {
                    $result[$from_segment_start * 4] = new FineDiffDeleteOp($from_segment_len);
                } elseif ($to_segment_len) {
                    $result[$from_segment_start * 4 + 1] = new FineDiffInsertOp(substr($to_text, $to_segment_start, $to_segment_len));
                }
                continue;
            }
            if ($from_segment_len >= $to_segment_len) {
                $copy_len = $to_segment_len;
                while ($copy_len) {
                    $to_copy_start = $to_segment_start;
                    $to_copy_start_max = $to_segment_end - $copy_len;
                    while ($to_copy_start <= $to_copy_start_max) {
                        $from_copy_start = strpos(substr($from_text, $from_segment_start, $from_segment_len), substr($to_text, $to_copy_start, $copy_len));
                        if ($from_copy_start !== false) {
                            $from_copy_start += $from_segment_start;
                            break 2;
                        }
                        $to_copy_start++;
                    }
                    $copy_len--;
                }
            } else {
                $copy_len = $from_segment_len;
                while ($copy_len) {
                    $from_copy_start = $from_segment_start;
                    $from_copy_start_max = $from_segment_end - $copy_len;
                    while ($from_copy_start <= $from_copy_start_max) {
                        $to_copy_start = strpos(substr($to_text, $to_segment_start, $to_segment_len), substr($from_text, $from_copy_start, $copy_len));
                        if ($to_copy_start !== false) {
                            $to_copy_start += $to_segment_start;
                            break 2;
                        }
                        $from_copy_start++;
                    }
                    $copy_len--;
                }
            }
            // match found
            if ($copy_len) {
                $jobs[] = [$from_segment_start, $from_copy_start, $to_segment_start, $to_copy_start];
                $result[$from_copy_start * 4 + 2] = new FineDiffCopyOp($copy_len);
                $jobs[] = [$from_copy_start + $copy_len, $from_segment_end, $to_copy_start + $copy_len, $to_segment_end];
            } // no match,  so delete all, insert all
            else {
                $result[$from_segment_start * 4] = new FineDiffReplaceOp($from_segment_len, substr($to_text, $to_segment_start, $to_segment_len));
            }
        }
        ksort($result, SORT_NUMERIC);
        return array_values($result);
    }

    /**
     * Efficiently fragment the text into an array according to
     * specified delimiters.
     * No delimiters means fragment into single character.
     * The array indices are the offset of the fragments into
     * the input string.
     * A sentinel empty fragment is always added at the end.
     * Careful: No check is performed as to the validity of the
     * delimiters.
     */
    private static function extractFragments($text, $delimiters): array
    {
        // special case: split into characters
        if (empty($delimiters)) {
            $chars = str_split($text);
            $chars[strlen($text)] = '';
            return $chars;
        }
        $fragments = [];
        $start = $end = 0;
        for (; ;) {
            $end += strcspn($text, $delimiters, $end);
            $end += strspn($text, $delimiters, $end);
            if ($end === $start) {
                break;
            }
            $fragments[$start] = substr($text, $start, $end - $start);
            $start = $end;
        }
        $fragments[$start] = '';
        return $fragments;
    }

    /**
     * Stock opcode renderers
     */
    private static function renderToTextFromOpcode($opcode, $from, $from_offset, $from_len): void
    {
        if ($opcode === 'c' || $opcode === 'i') {
            echo substr($from, $from_offset, $from_len);
        }
    }

    /**
     * @param $opcode
     * @param $from
     * @param $from_offset
     * @param $from_len
     * @return void
     */
    private static function renderDiffToHTMLFromOpcode($opcode, $from, $from_offset, $from_len): void
    {
        if ($opcode === 'c') {
            echo htmlspecialchars(substr($from, $from_offset, $from_len));
        } elseif ($opcode === 'd') {
            $deletion = substr($from, $from_offset, $from_len);
            if (strcspn($deletion, " \n\r") === 0) {
                $deletion = str_replace(["\n", "\r"], ['\n', '\r'], $deletion);
            }
            echo '<del>', htmlspecialchars($deletion), '</del>';
        } else /* if ( $opcode === 'i' ) */ {
            echo '<ins>', htmlspecialchars(substr($from, $from_offset, $from_len)), '</ins>';
        }
    }

    /**
     * @param $from_text
     * @param $to_text
     * @return bool|string
     */
    public static function String($from_text, $to_text): bool|string
    {
        $opcodes = FineDiff::getDiffOpcodes($from_text, $to_text, json_decode(FINE_DIFF_GRANULARITY_WORD));
        return FineDiff::renderDiffToHTMLFromOpcodes($from_text, $opcodes);
    }
}

