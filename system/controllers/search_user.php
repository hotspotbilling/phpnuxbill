<?php

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($query)) {    
    $results = ORM::for_table('tbl_customers')
        ->where_like('username', "%$query%")
        ->find_many();

    if ($results) {
        echo '<ul>';
        foreach ($results as $user) {
            echo '<li><a href="'.$_url.'?_route=customers/view/'.$user->id.'">' . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . Lang::T('No users found.') . '</p>';
    }
} else {
    echo '<p>' . Lang::T('Please enter a search term.') . '</p>';
}
