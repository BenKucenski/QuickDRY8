<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 * Class BasePage
 *
 */
class BasePage extends strongType
{
    public static ?string $PDFPageOrientation = null;
    public static ?string $PDFPageSize = null;
    public static ?bool $PDFShrinkToFit = null;
    public static ?string $PDFFileName = null;
    public static ?PDFMargins $PDFMargins = null;
    public static ?string $PDFHeader = null;
    public static ?string $PDFFooter = null;
    public static ?string $DOCXPageOrientation = null;
    public static ?string $DOCXFileName = null;
    public static ?string $PDFPostRedirect = null;
    public static ?bool $IncludeMenu = null;
    public static ?array $PostData = null;
    protected static array $Errors = [];
    public static ?string $MasterPage = null;
    private static ?string $MetaTitle = null;
    public static ?string $CurrentPage = null;


    public static function Get()
    {

    }

    public static function getMasterPage(): ?string
    {
        return self::$MasterPage;
    }

    public static function getMetaTitle(): ?string
    {
        return self::$MetaTitle;
    }

    public static function Post()
    {

    }


    public static function Init()
    {
        $temp = parse_url($_SERVER['REQUEST_URI']);
        self::$CurrentPage = $temp['path'] ?? '/';
        self::$PostData = json_decode(file_get_contents('php://input')); // return a standard object
    }

    public static function Put()
    {

    }

    public static function Patch()
    {

    }

    public static function Options()
    {

    }

    public static function Delete()
    {

    }

    public static function Find()
    {

    }

    public static function History()
    {

    }

    /**
     * @return string
     */
    public static function GetClassName(): string
    {
        return get_called_class();
    }

    public static function ExportToXLS()
    {
        Debug('QuickDRY Error: DoExportToXLS is not implemented');
    }

    public static function ExportToPDF()
    {
        Debug('QuickDRY Error: DoExportToPDF is not implemented');
    }

    public static function ExportToDOCX()
    {
        Debug('QuickDRY Error: DoExportToDOCX is not implemented');
    }

    public static function ExportToCSV()
    {
        Debug('QuickDRY Error: DoExportToCSV is not implemented');
    }

    public static function ExportToJSON()
    {
        Debug('QuickDRY Error: DoExportToJSON is not implemented');
    }
}