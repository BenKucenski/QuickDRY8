<?php

namespace QuickDRY\Web;

use QuickDRY\Connectors\mssql\MSSQL_Connection;
use QuickDRY\Connectors\mysql\MySQL_Connection;
use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\Navigation;
use QuickDRY\Utilities\strongType;

/**
 * Class Web
 *
 * @property string ControllerFile
 * @property string ViewFile
 * @property string PageClass
 * @property Request Request
 * @property Session Session
 * @property Cookie Cookie
 * @property Server Server
 * @property Navigation Navigation
 * @property bool AccessDenied
 * @property bool IsJSON
 * @property string[] SecureMasterPages
 * @property string MasterPage
 * @property string SettingsFile
 * @property bool RenderPDF;
 * @property bool RenderDOCX;
 * @property string HTML;
 * @property string Verb
 * @property string PDFPageOrientation
 * @property string PDFPageSize
 * @property string PDFFileName
 * @property PDFMargins PDFMargins
 * @property string PDFHeader
 * @property string PDFFooter
 * @property string PDFHash
 * @property string PDFPostRedirect
 * @property bool PDFShrinkToFit
 * @property string PDFPostFunction
 * @property string PDFRootDir
 * @property string DOCXPageOrientation
 * @property string DOCXFileName
 * @property string DefaultURL
 * @property string url_export_xls
 * @property string Namespace;
 */
class Web extends strongType
{
    public ?string $Namespace = null;
    public ?string $ControllerFile = null;
    public ?string $ViewFile = null;
    public ?string $PageClass = null;
    public ?bool $IsJSON = null;

    public Request $Request;
    public Session $Session;
    public Cookie $Cookie;
    public Server $Server;
    public ?Navigation $Navigation = null;
    public ?bool $AccessDenied = null;
    public ?string $MasterPage = null;
    public ?string $SettingsFile = null;
    public int $PageMode;
    public string $CurrentPage;
    public string $CurrentPageName;
    public ?string $DefaultURL = null;
    public ?string $IndexFile = null;

    private ?array $SecureMasterPages = null;

    public ?bool $RenderPDF = null;
    public ?string $PDFPageOrientation = null;
    public ?string $PDFPageSize = null;
    public ?string $PDFFileName = null;
    public ?string $PDFPostRedirect = null;
    public ?string $PDFHeader = null;
    public ?string $PDFFooter = null;
    public ?string $PDFSimplePageNumbers = null;
    public ?PDFMargins $PDFMargins = null;

    /* @var callable $PDFPostFunction */
    public $PDFPostFunction;

    public ?string $PDFHash = null;
    public ?string $PDFRootDir = null;
    public ?string $PDFShrinkToFit = null;

    public ?string $HTML = null;
    public ?string $js = null;
    public ?string $css = null;

    public ?bool $RenderDOCX = null;
    public ?string $DOCXPageOrientation = null;
    public ?string $DOCXFileName = null;

    public ?string $StaticModel = null;
    public ?string $InstanceModel = null;

    public string $Verb;
    public int $StartTime;
    public int $InitTime;

    public ?string $DefaultPage = null;
    public string $DefaultUserPage;

    public ?string $MetaTitle = null;
    public ?string $MetaDescription = null;
    public ?string $MetaKeywords = null;

    /**
     * @param string[] $MasterPages
     */
    public function SetSecureMasterPages(array $MasterPages)
    {
        $this->SecureMasterPages = $MasterPages;
    }

    /**
     * @return bool
     */
    public function IsSecureMasterPage(): bool
    {
        if (!is_array($this->SecureMasterPages)) {
            return false;
        }

        return in_array($this->MasterPage, $this->SecureMasterPages);
    }

    public function __construct()
    {
        $this->StartTime = time();
        $this->RenderPDF = false;

        $this->Request = new Request();
        $this->Session = new Session();
        $this->Cookie = new Cookie();
        $this->Server = new Server();

        if (isset($this->Server->REQUEST_URI)) {
            if (!defined('HTTP_HOST')) {
                if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                    $host = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
                    $host = trim($host[sizeof($host) - 1]);
                } else {
                    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_HOST ?? '');
                }

                define('HTTP_HOST', strtolower($host)); // the domain that the site needs to behave as (for proxies)
            }
        }

        if (defined('HTTP_HOST')) {
            $this->SettingsFile = 'settings.' . HTTP_HOST . '.php';
        }

        if (defined('MYSQL_LOG') && MYSQL_LOG) {
            MySQL_Connection::$use_log = true;
        }

        if (defined('MSSQL_LOG') && MSSQL_LOG) {
            MSSQL_Connection::$use_log = true;
        }
    }

    /**
     * @param string $default_page
     * @param string $default_user_page
     * @param string $script_dir
     */
    public function Init(
        string $default_page,
        string $default_user_page,
        string $script_dir
    )
    {
        $this->DefaultPage = $default_page;
        $this->DefaultUserPage = $default_user_page;

        $t = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : $script_dir;
        if ($t[strlen($t) - 1] == '/') {
            $t = substr($t, 0, strlen($t) - 1);
        }
        define('DOC_ROOT_PATH', $t);

        define('SORT_BY', (string)$this->Request->Get('sort_by') ?? null);
        define('SORT_DIR', (string)$this->Request->Get('sort_dir') ?? 'asc');

        define('PAGE', (int)$this->Request->Get('page') ?: 0);
        define('PER_PAGE', (int)$this->Request->Get('per_page') ?: 20);

        $url = strtok($this->Server->REQUEST_URI, '?');

        $this->Session->Set('last_url', $url);

        $qs = $this->Server->QUERY_STRING;
        $ru = $this->Server->REQUEST_URI;

        define('JSON_REQUEST', stristr($ru, '.json') !== false);

        $page = str_replace('?' . $qs, '', $ru);
        $page = str_replace('/' . $qs, '/', $page);

        if ($page[strlen($page) - 1] == '/') {
            $page = substr($page, 0, strlen($page) - 1);
        }

        $full_path = $page != '/' ? $page : '/';
        $t = explode('/', $full_path);
        $cur_page = $t[sizeof($t) - 1];

        if (!$cur_page) {
            $cur_page = $this->DefaultPage;
            $full_path = $cur_page;
            $cur_page = explode('/', $cur_page);
            $cur_page = $cur_page[sizeof($cur_page) - 1];
        }

        $host = explode('.', HTTP_HOST);
        $m = sizeof($host);

        if (sizeof($host) >= 2) {
            define('URL_DOMAIN', $host[$m - 2] . '.' . $host[$m - 1]);
        } else {
            define('URL_DOMAIN', $host[0]);
        }

        define('COOKIE_DOMAIN', '.' . URL_DOMAIN);

        define('CURRENT_PAGE', $full_path);
        define('CURRENT_PAGE_NAME', $cur_page);

        $this->CurrentPage = $full_path;
        $this->CurrentPageName = $cur_page;

        $page_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.html.php';
        $code_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.php';
        $js_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.js';
        $css_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.css';

        $this->Namespace = str_replace('/', '\\', 'pages' . $this->CurrentPage);


        $this->ControllerFile = (file_exists($code_alt) ? $code_alt : null);
        $this->ViewFile = (file_exists($page_alt) ? $page_alt : null);
        $this->css = (file_exists($css_alt) ? $css_alt : null);
        $this->js = (file_exists($js_alt) ? $js_alt : null);


        // Accept page.json.php and json.page.php
        $this->IsJSON = false;
        if (stristr($this->CurrentPageName, '.html') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = false;
        } elseif (stristr($this->CurrentPageName, '.json') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, 'json.') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, '.xlsx') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, '.pdf') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        }


        $temp = explode('.', $this->CurrentPageName);
        $this->PageClass = $this->Namespace . '\\' . $temp[0];

        $this->Verb = strtoupper($this->Request->Get('verb') ?: $this->Server->REQUEST_METHOD);
    }

    /**
     * @return void
     */
    public function SetURLs(): void
    {
        // this must be done after the settings file is loaded to support proxy situations
        define('FULL_URL', (HTTP::IsSecure() ? 'https://' : 'http://') . HTTP_HOST . $this->Server->REQUEST_URI);

        if (isset($_SERVER['HTTPS'])) { // check if page being accessed by browser
            $protocol = HTTP::IsSecure() ? 'https://' : 'http://';

            if (!HTTP::IsSecure() && defined('FORCE_SSL') && FORCE_SSL) {
                HTTP::Redirect('https://' . HTTP_HOST);
            }

            define('BASE_URL', $protocol . HTTP_HOST);
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                if (defined('FORCE_SSL') && FORCE_SSL) {
                    HTTP::Redirect('https://' . HTTP_HOST);
                }
            }
            if (!defined('BASE_URL')) { // allows the secure URL to be set in scheduled tasks
                define('BASE_URL', (defined('HTTP_HOST_IS_SECURE') && HTTP_HOST_IS_SECURE ? 'https://' : 'http://') . HTTP_HOST);
            }
        }
    }
}