<?php
$mysqli = new mysqli("127.0.0.1", "root", "root", "", 8889);
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
if ($mysqli->query("CREATE DATABASE IF NOT EXISTS kasir")) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $mysqli->error . "\n";
}
$mysqli->close();
