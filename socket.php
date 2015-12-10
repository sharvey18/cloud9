<?php
    $host = getenv('IP');
    $user = 'sharvey18';
    $pass = "";
    $db = "sam";
    $port = 3306;
    
    // Create Connection
    $connection = mysqli_connect($host, $user, $pass, $db, $port)
    or die(mysql_error());
    
    // Check Connection
    
    // if ($db->connect_error) {
    //     die(mysql_error());
    // }

?>