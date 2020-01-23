<?php

class Content
{
    public static function pagination($link_url, $page, $total_rows, $rows_per_page = 0, $max_pages = 0, $pagination_label = false, $target_elem = false)
    {
      //  $first_text = Lang::string('first-page');
	  //$last_text = Lang::string('last-page');
		
		 $first_text = '<<';
        $last_text = ">>";

        $link_url = preg_replace('/[^a-zA-Z\.-]/', '', $link_url);
        $page = preg_replace('/[^0-9]/', '', $page);
        $total_rows = preg_replace('/[^0-9]/', '', $total_rows);
        $rows_per_page = preg_replace('/[^0-9]/', '', $rows_per_page);
        $max_pages = preg_replace('/[^0-9]/', '', $max_pages);
        $first_page = false;
        $last_page = false;

        $page = ($page > 0) ? $page : 1;
        if (!($rows_per_page > 0))
            return false;

        if ($total_rows > $rows_per_page) {
            $num_pages = ceil($total_rows / $rows_per_page);
            $page_array = range(1, $num_pages);

            if ($max_pages > 0) {
                $p_deviation = ($max_pages - 1) / 2;
                $alpha = $page - 1;
                $alpha = ($alpha < $p_deviation) ? $alpha : $p_deviation;
                $beta = $num_pages - $page;
                $beta = ($beta < $p_deviation) ? $beta : $p_deviation;
                if ($alpha < $p_deviation) $beta = $beta + ($p_deviation - $alpha);
                if ($beta < $p_deviation) $alpha = $alpha + ($p_deviation - $beta);
            }

            if ($page != 1)
                $first_page = '<a href="' . $link_url . '?' . http_build_query(array('page' => 1)) . '">' . $first_text . '</a>';
            if ($page != $num_pages)
                $last_page = ' <a href="' . $link_url . '?' . http_build_query(array('page' => $num_pages)) . '">' . $last_text . '</a>';

            $pagination = '<div class="pagination"><div >' . $first_page;

            $p_one_more = 0;

            $right_interval = true;
            $left_interval = true;

            if ($page < $max_pages) {
                $left_interval = false;
            }

            if ($left_interval) {
                $left_interval_page = $page - 1;

                $pagination .= '
                <a href="' . $link_url . '?' . http_build_query(array('page' => $left_interval_page)) . '">...</a>
                ';
            }

            foreach ($page_array as $p) {
                if (($p >= ($page - $alpha) && $p <= ($page + $beta)) || $max_pages == 0) {
                    if ($p == $page) {
                        $pagination .= ' <span>' . $p . '</span> ';
                    } else {
                        $pagination .= ' <a href="' . $link_url . '?' . http_build_query(array('page' => $p)) . '">' . $p . '</a> ';
                    }
                    $p_one_more = $p + 1;
                    if ($p == $num_pages) {
                        $right_interval = false;
                    }
                }
            }
            $pagination .= '';

            $label = str_ireplace('[results]', '<b>' . $total_rows . '</b>', Lang::string('transactions-pagination'));
            $label = str_ireplace('[num_pages]', '<b>' . $num_pages . '</b>', $label);

            $right_interval_page = $p_one_more < $num_pages ? $p_one_more : $num_pages;

            if ($right_interval) {
                $pagination .= '
                <a href="' . $link_url . '?' . http_build_query(array('page' => $right_interval_page)) . '">...</a>
                ';
            }
            $pagination .= $last_page . '</div>';
            return $pagination;
        }
        return false;
    }
}

?>