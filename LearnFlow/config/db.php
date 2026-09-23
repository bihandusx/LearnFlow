<?php

$host = "127.0.0.1";
$username = "root";

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| Put the SAME password here that you entered when this worked:
|
| mysql.exe -u root -p
|
*/
$password = "1995";

$database = "learnflow_db";
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    die(
        "Database connection failed: " .
        $e->getMessage()
    );
}

?>