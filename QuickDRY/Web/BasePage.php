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

    /**
     * @return void
     */
    public static function Get()
    {

    }

    /**
     * @return string|null
     */
    public static function getMasterPage(): ?string
    {
        return self::$MasterPage;
    }

    public static function setMasterPage(string $masterpage): void
    {
        self::$MasterPage = $masterpage;
    }

    public static function setPDFOrientation(string $value): void
    {
        self::$PDFPageOrientation = $value;
    }

    public static function setPDFFilename(string $value): void
    {
        self::$PDFFileName = $value;
    }

    /**
     * @return string|null
     */
    public static function getMetaTitle(): ?string
    {
        return self::$MetaTitle;
    }

    public static function setMetaTitle(string $metaTitle): void
    {
        self::$MetaTitle = $metaTitle;
    }

    /**
     * @return void
     */
    public static function Post()
    {
        Debug('Post Undefined');
    }


    /**
     * @return void
     */
    public static function Init(): void
    {
        $temp = parse_url($_SERVER['REQUEST_URI']);
        self::$CurrentPage = $temp['path'] ?? '/';
        self::$PostData = json_decode(file_get_contents('php://input'), true); // return an array
    }

    /**
     * @return void
     */
    public static function Put()
    {

    }

    /**
     * @return void
     */
    public static function Patch()
    {

    }

    /**
     * @return void
     */
    public static function Options()
    {

    }

    /**
     * @return void
     */
    public static function Delete()
    {

    }

    /**
     * @return void
     */
    public static function Find()
    {

    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public static function ExportToXLS(): void
    {
        Debug('QuickDRY Error: ExportToXLS is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToPDF(): void
    {
        Debug('QuickDRY Error: ExportToPDF is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToDOCX(): void
    {
        Debug('QuickDRY Error: ExportToDOCX is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToCSV(): void
    {
        Debug('QuickDRY Error: ExportToCSV is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToJSON(): void
    {
        Debug('QuickDRY Error: ExportToJSON is not implemented');
    }
}