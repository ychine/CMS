<?php

$toastMessage = "";
$toastType = "";

$mysqli = new mysqli("localhost", "root", "", "cms");

if ($mysqli->connect_error) {
    $toastMessage = "❌ Failed to connect to database.";
    $toastType = "error";
}

return $mysqli;


