<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

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

    /**
     * @param string|null $brand
     * @return void
     */
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
     * @param string|null $style
     * @return string
     */
    public function RenderBootstrap(
        ?string $CurrentPage = null,
        ?array  $additional_params = null,
        ?string $additional_html = null,
        ?string $style ='background-color: #fff; color: var(--bs-primary); border-color: #fff;',
    ): string
    {
        return $this->renderMenu($CurrentPage, $additional_params, $additional_html, $style);
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
        bool    $ShowViewAll = true
    ): string {
        // Parse and build query parameters
        if ($params === null) {
            $query = [];
            foreach ($_GET as $k => $v) {
                if (!in_array($k, ['sort_by', 'dir', 'page', 'per_page'])) {
                    $query[] = $k . '=' . urlencode($v);
                }
            }
            $params = implode('&', $query);
        }

        $_SORT_BY  ??= SORT_BY;
        $_SORT_DIR ??= SORT_DIR;
        $_PER_PAGE ??= PER_PAGE;
        $_URL      ??= CURRENT_PAGE;

        // No pagination needed
        if ($_PER_PAGE <= 0 || $count <= $_PER_PAGE) {
            if ($ShowViewAll) {
                return '<ul class="pagination mt-3"><li class="page-item"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=0&' . $params . '">View Paginated</a></li></ul>';
            }
            return '';
        }

        $num_pages = ceil($count / $_PER_PAGE);
        $current   = PAGE;
        $start     = max($current - 10, 0);
        $end       = min($current + 10, $num_pages - 1);

        $buildUrl = function ($page) use ($_URL, $_SORT_BY, $_SORT_DIR, $_PER_PAGE, $params) {
            return "$_URL?sort_by=$_SORT_BY&dir=$_SORT_DIR&page=$page&per_page=$_PER_PAGE&$params";
        };

        $html = '<ul class="pagination mt-3">';

        // First and Previous
        if ($current > 10) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl(0) . '">&laquo;</a></li>';
            $html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl($current - 10) . '">&lsaquo;</a></li>';
        }

        // Page links
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $current ? ' active' : '';
            $html .= '<li class="page-item' . $active . '">';
            $html .= '<a class="page-link" href="' . ($active ? '#' : $buildUrl($i)) . '">' . ($i + 1) . '</a></li>';
        }

        // Next and Last
        if ($current < $num_pages - 10 && $num_pages > 10) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl($current + 10) . '">&rsaquo;</a></li>';
            $html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl($num_pages - 1) . '">&raquo;</a></li>';
        }

        // View All
        if ($ShowViewAll) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=0&per_page=0&' . $params . '">View All</a></li>';
        }

        $html .= '</ul>';
        return $html;
    }

    /**
     * @param string|null $CurrentPage
     * @param array|null $additional_params
     * @param string|null $additional_html
     * @param string|null $style
     * @return string
     */
    public function renderMenu(
        ?string $CurrentPage = null,
        ?array  $additional_params = null,
        ?string $additional_html = null,
        ?string $style = 'background-color: #fff; color: var(--bs-primary); border-color: #fff;',
    ): string
    {
        $html = '<div class="btn-group me-auto" role="group">' . PHP_EOL;

        $params = $additional_params && sizeof($additional_params) > 0 ? '?' . http_build_query($additional_params) : '';

        if ($this->Brand) {
            // Add a brand element first
            $html .= sprintf(
                    '<span class="btn btn-outline-secondary disabled fw-bold"  
style="' . $style . '"
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

                    $active = strcmp($CurrentPage ?? '', $href ?? '') == 0;
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

                $active = strcmp($CurrentPage ?? '', $items) == 0;
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