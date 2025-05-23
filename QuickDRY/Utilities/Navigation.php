<?php

namespace QuickDRY\Utilities;

use QuickDRY\API\Security;

/**
 * Class Navigation
 */
class Navigation
{
    protected array $_PERMISSIONS = [];
    protected array $_MENU = [];

    public ?array $Legend = null;

    /**
     * @param array $_ADD
     */
    public function Combine(array $_ADD): void
    {
        foreach ($_ADD as $link) {
            if (!in_array($link, $this->_PERMISSIONS)) {
                $this->_PERMISSIONS[] = $link;
            }
        }
    }

    /**
     * @param array $menu
     */
    public function SetMenu(array $menu): void
    {
        $this->_MENU = $menu;
    }

    /**
     * @param string $_CUR_PAGE
     * @param bool $test
     * @return bool
     */
    public function CheckPermissions(string $_CUR_PAGE, bool $test = false): bool
    {
        if ($_CUR_PAGE == '/' || $_CUR_PAGE == '') {
            return true;
        }

        if (in_array($_CUR_PAGE, $this->_PERMISSIONS)) {
            return true;
        }

        if (!$test) {
            HTTP::RedirectError('You do not have permission to view that page: ' . $_CUR_PAGE);
        }
        return false;
    }

    /**
     * @param array $links
     * @return bool
     */
    private function _hasVisible(array $links): bool
    {
        $has_visible = false;
        if (sizeof($links)) {
            foreach ($links as $name => $url) {
                if(is_array($url)) {
                    if($this->_hasVisible($url)) {
                        $has_visible = true;
                        break;
                    }
                }
                if ($this->CheckPermissions($url, true)) {
                    $has_visible = true;
                    break;
                }
            }
        }

        return $has_visible;
    }


    /**
     * @param string $name
     * @param mixed $values
     * @param string $_MENU_HTML
     * @param bool $is_submenu
     * @return void
     */
    private function _RenderValues(
        string $name,
        mixed  $values,
        string &$_MENU_HTML,
        bool   $is_submenu = false
    ): void
    {

        if (is_string($values)) {
            if ($this->CheckPermissions($values, true)) {
                $_MENU_HTML .= '<a class="btn btn-primary" href="' . $values . '">' . $name . '</a>' . PHP_EOL;
            }
            return;
        }

        if (!is_array($values)) {
            return;
        }

        $has_visible = $this->_hasVisible($values);

        if (!$has_visible) {
            return;
        }

        $hash = Security::MD5(json_encode($values));

        if (!$is_submenu) {
            $_MENU_HTML .= '<button 
class="btn btn-primary dropdown-toggle" 
type="button"
data-bs-toggle="dropdown" 
id="dropdownMenu_' . $hash . '"
aria-expanded="false"
>' . $name . '</button>';
        } else {
            $_MENU_HTML .= '<div class="dropdown-submenu"><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">' . $name . '</a>';
        }
        ksort($values);
        $_MENU_HTML .= '<div class="dropdown-menu" aria-labelledby="dropdownMenu_' . $hash . '">';
        foreach ($values as $link_name => $values2) {
            if (!is_array($values2)) {
                if (!$this->CheckPermissions($values2, true)) {
                    continue;
                }
                $_MENU_HTML .= '<a class="dropdown-item" href="' . $values2 . '">' . $link_name . '</a>' . PHP_EOL;
                continue;
            }
//            Debug($hash, $values2, Security::MD5(json_encode($values2)));
            $this->_RenderValues($link_name, $values2, $_MENU_HTML, true);

        }
        $_MENU_HTML .= '</div>' . PHP_EOL;
    }

    /**
     * @param $_MENU
     * @return string
     */
    public function RenderBootstrap($_MENU = null): string
    {
        if ($_MENU) {
            $this->_MENU = $_MENU;
        }

        $_MENU_HTML = '<div class="btn-group" role="group">';
        foreach ($this->_MENU as $name => $values) {
            $this->_RenderValues($name, $values, $_MENU_HTML);
        }
        return $_MENU_HTML . '</div>';
    }

    /**
     * @param int $count
     * @param string|null $params
     * @param string|null $_SORT_BY
     * @param string|null $_SORT_DIR
     * @param int|null $_PER_PAGE
     * @param string|null $_URL
     * @param bool $ShowViewAll
     * @return string
     */
    public static function BootstrapPaginationLinks(
        int     $count,
        ?string $params = null,
        ?string $_SORT_BY = null,
        ?string $_SORT_DIR = null,
        ?int    $_PER_PAGE = null,
        ?string $_URL = null,
        bool    $ShowViewAll = true): string
    {
        if ($params == null) {
            $params = [];
            foreach ($_GET as $k => $v) {
                if (!in_array($k, ['sort_by', 'dir', 'page', 'per_page'])) {
                    $params[] = $k . '=' . $v;
                }
            }
        }
        if (is_array($params)) {
            $params = implode('&', $params);
        }


        $_SORT_BY = $_SORT_BY ?: SORT_BY;
        $_SORT_DIR = $_SORT_DIR ?: SORT_DIR;
        $_PER_PAGE = $_PER_PAGE ?: PER_PAGE;
        $_URL = $_URL ?: CURRENT_PAGE;

        if ($_PER_PAGE > 0) {
            $num_pages = ceil($count / $_PER_PAGE);
            if ($num_pages <= 1) return '';

            $start_page = PAGE - 10;
            $end_page = PAGE + 10;
            if ($start_page < 0)
                $start_page = 0;
            if ($start_page >= $num_pages)
                $start_page = $num_pages - 1;
            if ($end_page < 0)
                $end_page = 0;
            if ($end_page >= $num_pages)
                $end_page = $num_pages - 1;

            $html = '<ul class="pagination">';
            if (PAGE > 10) {
                $html .= '<li class="page-item first"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (0) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;&lt;</a></li>';
                $html .= '<li class="page-item previous"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE - 10) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;</a></li>';
            }

            for ($j = $start_page; $j <= $end_page; $j++) {
                if ($j != PAGE)
                    $html .= '<li class="page-item"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=' . $_PER_PAGE . '&' . $params . '">' . ($j + 1) . '</a></li>';
                else
                    $html .= '<li class="page-item active"><a class="page-link" href="#">' . ($j + 1) . '</a></li>';
            }
            if (PAGE < $num_pages - 10 && $num_pages > 10) {
                $html .= '<li class="page-item next"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE + 10) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&gt;</a></li>';
                $html .= '<li class="page-item last"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . ($num_pages - 1) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&gt;&gt;</a></li>';
            }

            if ($ShowViewAll) {
                $html .= '<li class="page-item  view_all"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=0&' . $params . '">View All</a></li>';
            }
            return $html . '</ul>';
        }
        $html = '<br/><ul class="pagination">';
        if ($ShowViewAll) {
            $html .= '<li class="page-item view_all"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=0&' . $params . '">View Paginated</a></li>';
        }
        return $html . '</ul>';
    }

    /**
     * @param $_MENU
     * @return string
     */
    public function RenderTree($_MENU = null): string
    {
        if ($_MENU) {
            $this->_MENU = $_MENU;
        }

        $_MENU_HTML = '';
        foreach ($this->_MENU as $name => $values) {

            $has_visible = false;
            if (isset($values['links']) && sizeof($values['links'])) {
                foreach ($values['links'] as $url) {
                    if (isset($url['link']) && strcasecmp($url['link'], $name) == 0) {
                        continue;
                    }

//          if (!isset($url['link'])) {
//          } else {
//          }

                    $has_visible = true;
                    break;
                }
            }

            if ($has_visible) {
                $_MENU_HTML .= '<li>' . $name;
                ksort($values['links']);
                reset($values['links']);
                $_MENU_HTML .= '<ul>';
                foreach ($values['links'] as $link_name => $url) {
                    if (!is_array($url)) {
                        $_MENU_HTML .= '<li><a href="' . $url . '">' . $link_name . '</a></li>' . PHP_EOL;
                    } else {
                        if (isset($url['onclick'])) {
                            $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                        }

                        if (isset($url['links']) && sizeof($url['links'])) {

                            $_MENU_HTML .= '<li>' . $name;
                            $_MENU_HTML .= '<ul>' . PHP_EOL;
                            foreach ($url['links'] as $sub_name => $sub_url) {
                                $_MENU_HTML .= '<li><a href="' . $sub_url . '">' . $sub_name . '</a></li>' . PHP_EOL;
                            }
                            $_MENU_HTML .= '</ul>' . PHP_EOL;
                        } elseif (isset($url['onclick'])) {
                            $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                        } elseif (isset($url['link'])) {
                            $_MENU_HTML .= '<li><a href="' . $url['link'] . '">' . $link_name . '</a></li>' . PHP_EOL;
                        }
                    }
                }
                $_MENU_HTML .= '</ul></li>' . PHP_EOL;
            } elseif (isset($values['onclick'])) {
                $_MENU_HTML .= '<li><a href="#" onclick="' . $values['onclick'] . '">' . $name . '</a></li>';
            } elseif (isset($values['link'])) {
                $_MENU_HTML .= '<li><a href="' . $values['link'] . '"><b>' . $name . '</b></a></li>' . PHP_EOL;
            }
        }
        return $_MENU_HTML;
    }
}