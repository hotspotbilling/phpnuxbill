<?php

/**
 *  PHP Mikrotik Billing (https://github.com/SiberTech/)
 *  by https://t.me/ibnux
 **/


class Paginator
{
    public static function findMany($query, $search = [], $per_page = '10', $append_url = "")
    {
        global $routes, $ui;
        $adjacents = "2";
        $page = _get('p', 1);
        $page = (empty($page) ? 1 : $page);
        $url = U . implode('/', $routes);
        if (count($search) > 0) {
            $url .= '&' . http_build_query($search);
        }
        $url .= $append_url.'&p=';
        $totalReq = $query->count();
        $lastpage = ceil($totalReq / $per_page);
        $lpm1 = $lastpage - 1;
        $limit = $per_page;
        $startpoint = ($page * $limit) - $limit;
        if ($lastpage >= 1) {
            $pages = [];
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    $pages[] = $counter;
                }
            } elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        $pages[] = $counter;
                    }
                    $pages[] = "...";
                    $pages[] = $lpm1;
                    $pages[] = $lastpage;
                } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                    $pages[] = "1";
                    $pages[] = "2";
                    $pages[] = "...";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        $pages[] = $counter;
                    }
                    $pages[] = "...";
                    $pages[] = $lpm1;
                    $pages[] = $lastpage;
                } else {
                    $pages[] = "1";
                    $pages[] = "2";
                    $pages[] = "...";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        $pages[] = $counter;
                    }
                }
            }

            $result = [
                'count' => $lastpage,
                'limit' => $per_page,
                'startpoint' => $startpoint,
                'url' => $url,
                'page' => $page,
                'pages' => $pages,
                'prev' => ($page > 0) ? ($page - 1) : "0",
                'next' => ($page >= $lastpage) ? $lastpage : $page + 1
            ];
            if ($ui) {
                $ui->assign('paginator', $result);
            }
            return $query->offset($startpoint)->limit($per_page)->find_many();
        }
    }

    public static function build($table, $colVal = [], $query = '', $per_page = '10')
    {
        global $routes;
        global $_L;
        $url = U . implode('/', $routes);
        $query = urlencode($query);
        $adjacents = "2";
        $page = (int)(empty(_get('p')) ? 1 : _get('p'));
        $pagination = "";
        foreach ($colVal as $k => $v) {
            if (!is_array($v) && strpos($v, '%') === false) {
                $table = $table->where($k, $v);
            } else {
                if (is_array($v)) {
                    $table = $table->where_in($k, $v);
                } else {
                    $table = $table->where_like($k, $v);
                }
            }
        }
        $totalReq = $table->count();
        $page = ($page == 0 ? 1 : $page);
        $next = $page + 1;
        $lastpage = ceil($totalReq / $per_page);
        $lpm1 = $lastpage - 1;
        $limit = $per_page;
        $startpoint = ($page * $limit) - $limit;
        if ($lastpage >= 1) {
            $pagination .= '<ul class="pagination">';
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                    else
                        $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$counter&q=$query'>$counter</a></li>";
                }
            } elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$counter&q=$query'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$lpm1&q=$query'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$lastpage&q=$query'>$lastpage</a></li>";
                } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=1&q=$query'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=2&q=$query'>2</a></li>";
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$counter&q=$query'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$lpm1&q=$query'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$lastpage&q=$query'>$lastpage</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=1&q=$query'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=2&q=$query'>2</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item'><a class='page-link disabled'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$counter&q=$query'>$counter</a></li>";
                    }
                }
            }

            if ($page < $counter - 1) {
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$next&q=$query'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}&p=$lastpage&q=$query'>" . Lang::T('Last') . "</a></li>";
            } else {
                $pagination .= "<li class='page-item disabled'><a class='page-link disabled'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item disabled'><a class='page-link disabled'>" . Lang::T('Last') . "</a></li>";
            }
            $pagination .= "</ul>";
            $pagination = '<nav>' . $pagination . '</nav>';
            return array("startpoint" => $startpoint, "limit" => $limit, "found" => $totalReq, "page" => $page, "lastpage" => $lastpage, "contents" => $pagination);
        }
    }

    public static function bootstrap($table, $w1 = '', $c1 = '', $w2 = '', $c2 = '', $w3 = '', $c3 = '', $w4 = '', $c4 = '', $per_page = '10')
    {
        global $routes;
        global $_L;
        $url = U . $routes['0'] . '/' . $routes['1'] . '/';
        $adjacents = "2";
        $page = (int)(!isset($routes['2']) ? 1 : $routes['2']);
        $pagination = "";

        if (is_object($table)) {
            if ($w1 != '') {
                $totalReq = $table->where($w1, $c1)->count();
            } elseif ($w2 != '') {
                $totalReq = $table->where($w1, $c1)->where($w2, $c2)->count();
            } elseif ($w3 != '') {
                $totalReq = $table->where($w1, $c1)->where($w2, $c2)->where($w3, $c3)->count();
            } elseif ($w4 != '') {
                $totalReq = $table->where($w1, $c1)->where($w2, $c2)->where($w3, $c3)->where($w4, $c4)->count();
            } else {
                $totalReq = $table->count();
            }
        } else {
            if ($w1 != '') {
                $totalReq = ORM::for_table($table)->where($w1, $c1)->count();
            } elseif ($w2 != '') {
                $totalReq = ORM::for_table($table)->where($w1, $c1)->where($w2, $c2)->count();
            } elseif ($w3 != '') {
                $totalReq = ORM::for_table($table)->where($w1, $c1)->where($w2, $c2)->where($w3, $c3)->count();
            } elseif ($w4 != '') {
                $totalReq = ORM::for_table($table)->where($w1, $c1)->where($w2, $c2)->where($w3, $c3)->where($w4, $c4)->count();
            } else {
                $totalReq = ORM::for_table($table)->count();
            }
        }

        $i = 0;
        $page = ($page == 0 ? 1 : $page);
        $start = ($page - 1) * $per_page;

        $prev = $page - 1;
        $next = $page + 1;
        $lastpage = ceil($totalReq / $per_page);

        $lpm1 = $lastpage - 1;
        $limit = $per_page;
        $startpoint = ($page * $limit) - $limit;

        if ($lastpage >= 1) {
            $pagination .= '<ul class="pagination">';
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                    else
                        $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                }
            } elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lpm1'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>$lastpage</a></li>";
                } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}1'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}2'>2</a></li>";
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lpm1'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>$lastpage</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}1'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}2'>2</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item'><a class='page-link disabled'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                }
            }

            if ($page < $counter - 1) {
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$next'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>" . Lang::T('Last') . "</a></li>";
            } else {
                $pagination .= "<li class='page-item disabled'><a class='page-link disabled'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item disabled'><a class='page-link disabled'>" . Lang::T('Last') . "</a></li>";
            }
            $pagination .= "</ul>";
            $pagination = '<nav>' . $pagination . '</nav>';

            $gen = array("startpoint" => $startpoint, "limit" => $limit, "found" => $totalReq, "page" => $page, "lastpage" => $lastpage, "contents" => $pagination);
            return $gen;
        }
    }

    public static function bootstrapRaw($table, $w1 = '', $c1 = [], $per_page = '10')
    {
        global $routes;
        global $_L;
        $url = U . $routes['0'] . '/' . $routes['1'] . '/';
        $adjacents = "2";
        $page = (int)(!isset($routes['2']) ? 1 : $routes['2']);
        $pagination = "";
        if (is_object($table)) {
            if ($w1 != '') {
                $totalReq = $table->where_raw($w1, $c1)->count();
            } else {
                $totalReq = $table->count();
            }
        } else {
            if ($w1 != '') {
                $totalReq = ORM::for_table($table)->where_raw($w1, $c1)->count();
            } else {
                $totalReq = ORM::for_table($table)->count();
            }
        }

        $i = 0;
        $page = ($page == 0 ? 1 : $page);
        $start = ($page - 1) * $per_page;

        $prev = $page - 1;
        $next = $page + 1;
        $lastpage = ceil($totalReq / $per_page);

        $lpm1 = $lastpage - 1;
        $limit = $per_page;
        $startpoint = ($page * $limit) - $limit;

        if ($lastpage >= 1) {
            $pagination .= '<ul class="pagination">';
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                    else
                        $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                }
            } elseif ($lastpage > 5 + ($adjacents * 2)) {
                if ($page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lpm1'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>$lastpage</a></li>";
                } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}1'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}2'>2</a></li>";
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-link' href='javascript:void(0);'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lpm1'>$lpm1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>$lastpage</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}1'>1</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='{$url}2'>2</a></li>";
                    $pagination .= "<li class='page-item'><a class='page-link' href='#'>...</a></li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class='page-item active'><a class='page-item disabled'>$counter</a></li>";
                        else
                            $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$counter'>$counter</a></li>";
                    }
                }
            }

            if ($page < $counter - 1) {
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$next'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item'><a class='page-link' href='{$url}$lastpage'>" . Lang::T('Last') . "</a></li>";
            } else {
                $pagination .= "<li class='page-item disabled'><a class='page-item disabled'>" . Lang::T('Next') . "</a></li>";
                $pagination .= "<li class='page-item disabled'><a class='page-item disabled'>" . Lang::T('Last') . "</a></li>";
            }
            $pagination .= "</ul>";
            $pagination = '<nav>' . $pagination . '</nav>';

            $gen = array("startpoint" => $startpoint, "limit" => $limit, "found" => $totalReq, "page" => $page, "lastpage" => $lastpage, "contents" => $pagination);
            return $gen;
        }
    }
}
