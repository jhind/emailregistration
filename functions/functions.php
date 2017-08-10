<?php

/* helper functions */


function clean($string) {

    return htmlentities($string);

}

function redirect($location) {

    return header("Location: {$location}");

}

function set_message($message) {

    if(!empty($message)) {

        $_SESSION['message'] = $message;

    } else {

        $message = "";

    }

}

function display_message() {

    if(isset($_SESSION['message'])) {

        echo $_SESSION['message'];

        unset($_SESSION['message']);    

    }

}

function token_generator() {

    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));

    return $token;

}

function display_validation_error($error) {

$message = <<<DELIMITER

        <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <strong>Problem!</strong> $error</div>

DELIMITER;

    echo $message;


}

function email_exists($email) {

    $sql = "SELECT id FROM users WHERE email = '$email'";

    $result = query($sql);

    confirm($result);

    if(row_count($result) > 0) {

        return true;

    } else {

        return false;
    }

}

function username_exists($username) {

    $sql = "SELECT id FROM users WHERE username = '$username'";

    $result = query($sql);

    confirm($result);

    if(row_count($result) > 0) {

        return true;

    } else {

        return false;
    }

}

function send_email($email, $subject, $msg, $headers) {
    
    mail($email, $subject, $msg, $headers);
    
}


/* validation functions */

//checks and cleans user registration input prior to registration
//problems with this were caused by autocommit being turned off on the mysql server - use transactions

function validate_user_registration() {
    
    global $con;
    
    $errors = [];
    $min = 3;
    $max = 20;

    if($_SERVER['REQUEST_METHOD'] == "POST") {

        $first_name         = clean($_POST['first_name']);
        $last_name          = clean($_POST['last_name']);
        $username           = clean($_POST['username']);
        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $confirm_password   = clean($_POST['confirm_password']);

        //most of these validations are daft!
        //add password complexity checks instead and remove length checks other than for passwords
        //verify email is not blank
        //probably not necessary to verify the format of the address as this is 
        //designed to verify email account anyway

        if(strlen($first_name) < $min) {

            $errors[] = "Your first name cannot be less than {$min} characters";

        }

        if(strlen($first_name) > $max) {

            $errors[] = "Your first name cannot be more than {$max} characters";

        }


        if(strlen($last_name) < $min) {

            $errors[] = "Your last name cannot be less than {$min} characters";

        }

        if(strlen($last_name) > $max) {

            $errors[] = "Your last name cannot be more than {$max} characters";

        }

        if(username_exists($username)) {

            $errors[] = "That username is already in use!";

        }

        if(strlen($username) < $min) {

            $errors[] = "Your username cannot be less than {$min} characters";

        }

        if(strlen($username) > $max) {

            $errors[] = "Your username cannot be more than {$max} characters";

        }

        if(email_exists($email)) {

            $errors[] = "That email address is already registered!";

        }

        if(strlen($email) < $min) {

            $errors[] = "Your email address should be more than {$min} characters";

        }

        if($password !== $confirm_password) {

            $errors[] = "Your passwords do not match. Please check and re-enter.";

        }



        if(!empty($errors)) {

            foreach ($errors as $error) {

            //display errors here    

            display_validation_error($error);

            }
        
        // register user if no errors
        } else {
            
            if(register_user($first_name, $last_name, $username, $email, $password)) {
             
                set_message("<p class='bg-success text-center'>You have been registgered. Please check your inbox or spam folder for an activation email.</p>");
                
                redirect("index.php");
                
                //echo "You have been registered";
                
            } 
            
            
           /* 
           
           else {
                
                echo "Unknown problem with user registration. <br>";
                print_r(mysqli_error_list($con));
                echo "SQLState error: " . mysqli_sqlstate($con);
                echo "Affected rows: " . mysqli_affected_rows($con);
                 
            }
            */
            
        }
    }
}

/* registration function(s) */

function register_user($first_name, $last_name, $username, $email, $password) {
    
    //registers the user if the validation checks above pass.
    //problems with this were due to autocommit being turned off on the mysql server - use transactions..
    //send email with activation code / link 
    
    global $con;
    
    $first_name = escape($first_name);
    $last_name  = escape($last_name);
    $username   = escape($username);
    $email      = escape($email);
    $password   = escape($password);
    
    if(email_exists($email)) {
        
        return false;
        
    } else if (username_exists($username)) {
        
        return false;
        
    } else {
        
        //use basline cost
        $password = password_hash($password, PASSWORD_BCRYPT);
        
        $validation = token_generator();
        
        $sql = "INSERT INTO users (first_name, last_name, username, password, validation_code, active, email)";
        $sql .= " VALUES ('$first_name','$last_name','$username','$password','$validation','0','$email')";
        
        // echo $sql . "<br>";
        
        $result = query($sql);
        
        if($result) {
            
            return true;
            
            $subject = "Activate your account";
            $msg = " Please click the link below to activate your account
            
                http://login.app/login/activate.php?email=$email&code=$validation
            
            
            ";
            
            $header = "From: noreply@anywebsite.com";
            
            send_email($email, $subject, $msg, $headers);
            
        } else {
            
            //need some improved user feedback if registation fails either here or in calling function
            
            echo "Error : " . mysqli_error($con);
            
            return false;
            
        }
        
       
        
        
      
        
        
    }
    
}

/* activation function(s) */

function activate_user() {
    
    if($_SERVER['REQUEST_METHOD'] == "GET") {
        
        if(isset($_GET['email'])) {
            
            $email = clean($_GET['email']);
            
            $validation = clean($_GET['code']);
            
            $sql = "SELECT id FROM users WHERE email = '" . escape($email) . "' AND validation_code = '" . escape($validation) . "'";
            
            $result = query($sql);
            confirm($result);
            
            if(row_count($result) == 1) {
            
                $sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '" . escape($email) . "' AND validation_code = '" . escape($validation) . "'";
                $result2 = query($sql2);
                confirm($result2);
                
                set_message("<p class='bg-success'>Your account has been activated, please login</p>");
                
                redirect("login.php");
                
            }
            
        }
        
        
        
        
    }
    
    
    
}

?>