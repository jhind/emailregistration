<?php

$con = mysqli_connect('192.168.10.10','homestead','secret','login_db');

if(!$con) {
    
    echo "Database connection problem";
    
} else {
    
    echo "db connected" . "<br>";
    echo mysqli_get_host_info($con) . "<br>";
    $sql = "SELECT * FROM user1 WHERE id = 1";
    echo $sql . "<br>";
    $result = mysqli_query($con, $sql);
    if(!$result) {
        
        die("SQL query has failed " . mysqli_error($con));
        
    } else {
        
        "There must be data returned!";
        
    }
    
    $row = mysqli_fetch_array($result);
    echo $row['id'] . " there should be some db output here<br>";
    print_r($row);
    
}

function row_count($result) {
    
    return mysqli_num_rows($result);
    
}


function escape($string) {
    
    global $con;  
    return mysqli_real_escape_string($con, $string);
    
}


function query($query) {
    
    global $con;
    return mysqli_query($con, $query);

}

function confirm($result) {
    
    global $con;
    
    if(!$result) {
        
        die("SQL query has failed " . mysqli_error($con));
        
    }
    
}


function fetch_array($result) {
    
    global $con;
    return mysqli_fetch_array($result);
    
}


?>