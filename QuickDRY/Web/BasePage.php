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
    public static ?Request $Request = null;
    public static ?Session $Session = null;
    public static ?Cookie $Cookie = null;
    public static ?Server $Server = null;
    public static ?bool $IncludeMenu = null;
    public static ?array $PostData = null;
    protected static array $Errors = [];
    public static ?string $MasterPage = null;
    private static ?string $MetaTitle = null;


    /**
     * BasePage constructor.
     * @param Request $Request
     * @param Session $Session
     * @param Cookie $Cookie
     * @param Server|null $Server
     */
    public function __construct(Request $Request, Session $Session, Cookie $Cookie, Server $Server = null)
    {
        static::Construct($Request, $Session, $Cookie, $Server);
    }

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
     * @param Request $Request
     * @param Session $Session
     * @param Cookie $Cookie
     * @param Server|null $Server
     */
    public static function Construct(Request $Request, Session $Session, Cookie $Cookie, Server $Server = null)
    {
        static::$Request = $Request;
        static::$Cookie = $Cookie;
        static::$Session = $Session;
        static::$Server = $Server;
        static::$PostData = json_decode(file_get_contents('php://input')); // return a standard object
    }

    /**
     * @return string
     */
    public static function GetClassName(): string
    {
        return get_called_class();
    }



    /**
     * @param $error
     */
    protected function LogError($error)
    {
        static::$Errors[] = $error;
    }

    /**
     * @return bool
     */
    public function HasErrors(): bool
    {
        return (bool)sizeof(static::$Errors);
    }

    /**
     * @return string
     */
    public function RenderErrors(): string
    {
        $res = '<div class="PageModelErrors"><ul>';
        foreach (static::$Errors as $error) {
            $res .= '<li>' . $error . '</li>';
        }
        $res .= '</ul></div>';
        return $res;
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