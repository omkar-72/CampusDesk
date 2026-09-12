<?php

$host = "localhost";
$port = "5432";
$dbname = "campusdesk";
$user = "postgres";
$password = "Shri@2005";

$conn = pg_connect(
    "host=$host port=$port dbname=$dbname user=$user password=$password"
);

if (!$conn) {
    die("Database connection failed");
}

?>