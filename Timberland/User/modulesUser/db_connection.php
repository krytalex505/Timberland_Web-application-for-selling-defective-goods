<?php
$connection = new mysqli(
    'sql307.infinityfree.com',
    'if0_42508245',
    'ii4bm2JsCGf5UJ',
    'if0_42508245_timberland_db'
);

if ($connection->connect_error) {
    die("Error connection db: " . $connection->connect_error);
}
?>