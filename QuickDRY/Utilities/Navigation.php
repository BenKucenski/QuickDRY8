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

    protected ?string $Brand = null;

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

    public function SetBrand(?string $brand): void
    {
        $this->Brand = $brand;
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
     * @param string|null $CurrentPage
     * @param array|null $additional_params
     * @param string|null $additional_html
     * @return string
     */
    public function RenderBootstrap(
        ?string $CurrentPage = null,
        ?array  $additional_params = null,
        ?string $additional_html = null
    ): string
    {
        return $this->renderMenu($CurrentPage, $additional_params, $additional_html);
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
     * @param string|null $CurrentPage
     * @param array|null $additional_params
     * @param string|null $additional_html
     * @return string
     */
    public function renderMenu(
        ?string $CurrentPage = null,
        ?array  $additional_params = null,
        ?string $additional_html = null
    ): string
    {
        $html = '<div class="btn-group me-auto" role="group">' . PHP_EOL;

        $params = $additional_params && sizeof($additional_params) > 0 ? '?' . http_build_query($additional_params) : '';

        if ($this->Brand) {
            // Add a brand element first
            $html .= sprintf(
                    '<span class="btn btn-outline-secondary disabled fw-bold"  
style="background-color: #fff; color: var(--bs-primary); border-color: #fff;"
>%s</span>',
                    htmlspecialchars($this->Brand)
                ) . PHP_EOL;
        }

        foreach ($this->_MENU as $label => $items) {
            if (is_array($items)) {
                ksort($items);
                $found = false;
                foreach ($items as $item) {
                    if (!$this->CheckPermissions($item, true)) {
                        continue;
                    }
                    $found = true;
                }
                if (!$found) {
                    continue;
                }

                // Generate unique ID for aria-labelledby
                $id = 'dropdown_' . md5($label);

                $html .= '<div class="btn-group" role="group">' . PHP_EOL;
                $html .= sprintf(
                        '<button class="btn btn-primary dropdown-toggle" type="button" id="%s" data-bs-toggle="dropdown" aria-expanded="false">%s</button>',
                        htmlspecialchars($id),
                        htmlspecialchars($label)
                    ) . PHP_EOL;

                $html .= sprintf('<ul class="dropdown-menu" aria-labelledby="%s">', htmlspecialchars($id)) . PHP_EOL;
                foreach ($items as $itemLabel => $href) {

                    $active = strcmp($CurrentPage, $href) == 0;
                    // Determine classes
                    $classes = '';
                    if ($active) {
                        $classes .= ' active';
                    }

                    $html .= sprintf(
                            '<li><a class="dropdown-item %s" href="%s">%s</a></li>',
                            htmlspecialchars($classes),
                            htmlspecialchars($href . $params),
                            htmlspecialchars($itemLabel)
                        ) . PHP_EOL;
                }
                $html .= '</ul>' . PHP_EOL;
                $html .= '</div>' . PHP_EOL;
            } else {
                if (!$this->CheckPermissions($items, true)) {
                    continue;
                }

                $active = strcmp($CurrentPage, $items) == 0;
                // Determine classes
                $classes = 'btn btn-primary';
                if ($active) {
                    $classes .= ' active';
                }
                // Single link
                $html .= sprintf(
                        '<a class="%s" href="%s">%s</a>',
                        htmlspecialchars($classes),
                        htmlspecialchars($items . $params),
                        htmlspecialchars($label)
                    ) . PHP_EOL;
            }
        }

        $html .= $additional_html . '</div>' . PHP_EOL;

        return $html;
    }

}