<?php

$con = mysqli_connect('192.168.10.10','homestead','secret','login_db');

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