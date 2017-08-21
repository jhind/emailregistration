<?php include("functions/init.php");

    session_destroy();

        if(isset($_COOKIE['emailreg'])) {
            
            unset($_COOKIE['emailreg']);
            
            setcookie('emailreg', '', time() - 86400);
            
        }

    redirect("login.php");



?>