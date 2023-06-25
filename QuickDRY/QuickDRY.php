<?php
// SIMPLE_EXCEL
use QuickDRY\Utilities\FineDiff\FineDiff;
use QuickDRY\Utilities\Metrics;
use QuickDRY\Web\BrowserOS;



// BasePage
const PDF_PAGE_ORIENTATION_LANDSCAPE = 'landscape';
const PDF_PAGE_ORIENTATION_PORTRAIT = 'portrait';

// http://doc.qt.io/archives/qt-4.8/qprinter.html#PaperSize-enum
const PDF_PAGE_SIZE_A0 = 'A0';
const PDF_PAGE_SIZE_A1 = 'A1';
const PDF_PAGE_SIZE_A2 = 'A2';
const PDF_PAGE_SIZE_A3 = 'A3';
const PDF_PAGE_SIZE_A4 = 'A4';
const PDF_PAGE_SIZE_A5 = 'A5';
const PDF_PAGE_SIZE_A6 = 'A6';
const PDF_PAGE_SIZE_A7 = 'A7';
const PDF_PAGE_SIZE_A8 = 'A8';
const PDF_PAGE_SIZE_A9 = 'A9';

const PDF_PAGE_SIZE_B0 = 'B0';
const PDF_PAGE_SIZE_B1 = 'B1';
const PDF_PAGE_SIZE_B2 = 'B2';
const PDF_PAGE_SIZE_B3 = 'B3';
const PDF_PAGE_SIZE_B4 = 'B4';
const PDF_PAGE_SIZE_B5 = 'B5';
const PDF_PAGE_SIZE_B6 = 'B6';
const PDF_PAGE_SIZE_B7 = 'B7';
const PDF_PAGE_SIZE_B8 = 'B8';
const PDF_PAGE_SIZE_B9 = 'B9';
const PDF_PAGE_SIZE_B10 = 'B10';

const PDF_PAGE_SIZE_C5E = 'C5E';
const PDF_PAGE_SIZE_COMM10E = 'Comm10E';
const PDF_PAGE_SIZE_DLE = 'DLE';
const PDF_PAGE_SIZE_EXECUTIVE = 'Executive';
const PDF_PAGE_SIZE_FOLIO = 'Folio';
const PDF_PAGE_SIZE_LEDGER = 'Ledger';
const PDF_PAGE_SIZE_LEGAL = 'Legal';
const PDF_PAGE_SIZE_LETTER = 'Letter';
const PDF_PAGE_SIZE_TABLOID = 'Tabloid';

// Web
const QUICKDRY_MODE_STATIC = 1;
const QUICKDRY_MODE_INSTANCE = 2;
const QUICKDRY_MODE_BASIC = 3;

const REQUEST_VERB_GET = 'GET';
const REQUEST_VERB_POST = 'POST';
const REQUEST_VERB_PUT = 'PUT';
const REQUEST_VERB_DELETE = 'DELETE';
const REQUEST_VERB_HISTORY = 'HISTORY';
const REQUEST_VERB_FIND = 'FIND';

const REQUEST_EXPORT_CSV = 'CSV';
const REQUEST_EXPORT_PDF = 'PDF';
const REQUEST_EXPORT_JSON = 'JSON';
const REQUEST_EXPORT_DOCX = 'DOCX';
const REQUEST_EXPORT_XLS = 'XLS';

// YesNo
const SELECT_NO = 1;
const SELECT_YES = 2;

Metrics::StartGlobal();
BrowserOS::Configure();

// FineDiff
define('FINE_DIFF_GRANULARITY_WORD', json_encode(FineDiff::$wordGranularity));
const FINE_DIFF_GRANULARITY_PARAGRAPH = 0;