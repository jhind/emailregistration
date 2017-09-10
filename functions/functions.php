<?php

require './vendor/autoload.php';

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

function password_complexity($password) {
    
    //does password contain any of  !"#$%&'()*+,-./:;<=>?@[\]^_`{|}~
    $specialcharacters = "!#$%&\"'()*+,-./:;<=>?@[\]^_`{|}~";
    $arrayspecial = str_split($specialcharacters);
    
    
    
    
}

function send_email($email=null, $subject=null, $msg=null, $headers=null) {
    
    $mail = new PHPMailer;

    //$mail->SMTPDebug = 3;                               // Enable verbose debug output

    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = Config::SMTP_HOST;                      // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = Config::SMTP_USER;                  // SMTP username
    $mail->Password = Config::SMTP_PASSWORD;              // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = Config::SMTP_PORT;                      // TCP port to connect to
    $mail->setFrom('admin@jdeveloper.com', 'Admin');
    $mail->addAddress($email);

    $mail->Subject = $subject;
    $mail->Body    = $msg;
    $mail->AltBody = $msg;

    if(!$mail->send()) {
        
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        
    } else {
        
        echo 'Message has been sent';
        
    }

    // mail($email, $subject, $msg, $headers);
    
}


/* validation functions */

//checks and cleans user registration input prior to registration
//problems with this were caused by autocommit being turned off on the mysql server - use transactions

function validate_user_registration() {
    
    global $con;
    
    $errors = [];
    $min = 3;
    $max = 50;
    $minpassword = 8;

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
             
                set_message("<p class='bg-success text-center'>You have been registered. Please check your inbox or spam folder for an activation email.</p>");
                
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
            
            
            
            $subject = "Activate your account";
            $msg = " Please click the link below to activate your account
            
            <a href='http://login.app/activate.php?email=$email&code=$validation'>Activate Account</a>
            
            
            ";
            
            $header = "From: noreply@anywebsite.com";
            
            send_email($email, $subject, $msg, $headers);
            
            return true;
            
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
                
                set_message("<p class='bg-success text-center'>Your account has been activated, please login</p>");
                
                redirect("login.php");
                
            } else {
                
                set_message("<p class='bg-danger text-center'>Sorry your account could not be activated. Please contact support.</p>");
                
                redirect("login.php");
                
                
            }
            
        }
        
        
        
        
    }
    
    
    
}

/* login function(s) */


function validate_login() {
    
    $errors = [];
    
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        
        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $remember           = isset($_POST['remember']);
        
        //check that username and password have been entered
        
        if(empty($email)) {
            
            $errors = "Please enter an email address!";
            
        }
        
        if(empty($password)) {
            
            $errors = "Please enter a password!";
            
        }
        
        
        if(!empty($errors)) {

            foreach ($errors as $error) {

            //display errors here    

            display_validation_error($error);

            }
        
        // log user in if no errors
        } else {
            
            // call login_user here
            if(login_user($email, $password, $remember)) {
                
                redirect("admin.php");
                
            } else {
                
                echo display_validation_error("Your email or password are incorrect");
                
            }
                
        } 

    }
}


/* login user after validating form */

function login_user($email, $password, $remember) {
    
    $sql = "SELECT password, username, id FROM users WHERE email = '" . escape($email) . "' AND active = 1";
    
    $result = query($sql);
    
    if(row_count($result) == 1) {
        
        $row = fetch_array($result);
        
        $db_password = $row['password'];
        
        if(password_verify(escape($password), $db_password)) {
            
            if($remember == "on") {
                
                //expires after 60 secs
                setcookie('emailreg', $email, time() + 86400);
                
            }
        
            //$email has already been escaped in the sql query. But probably should escape it again
            //even though it's just being assigned to a session variable
            //though it is user unescaped user input
            
            $_SESSION['email'] = escape($email);
            $_SESSION['username'] = $row['username'];
                
            return true;
        
        } else {
            
            return false;
            
        }
        
    } else {
        
        return false;
    }

}

/* logged in function - setting session */

function logged_in() {
    
    if(isset($_SESSION['email']) || isset($_COOKIE['emailreg'])) {
        
        return true;
        
    } else {
        
        return false;
        
    }    
}

/* recover password function(s) */

function recover_password() {
    
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        
        if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']) {
        
            $email = clean($_POST['email']);
            $email = escape($email);
            
            if(email_exists($email)) {
                
                $validation_code = token_generator();
                
                setcookie('temp_access_code', $validation_code, time() + 900);
                
                $sql = "UPDATE users SET validation_code = '$validation_code' WHERE email = '$email'";
                $result = query($sql);
                confirm($result);
                
                $subject = "Please reset your password";
                $message = " Here is your password reset code {$validation_code}
                <br/>
                Please copy and past the link into your browser or click to reset your password and copy the code into the box<br/> <a href='http://login.app/code.php?email=$email&code=$validation_code'>http://login.app/code.php?email=$email&code=$validation_code</a>                
                ";
                
                $headers = "noreply@login.com";
                
                if(!send_email($email, $subject, $message, $headers)) {
                    
                    echo display_validation_error("Email could not be sent.");
                    
                } 
                    
                set_message("<p class='bg-success text-center'>Please check your email for a password reset link and code.</p>");
                
                redirect("index.php");
                    
                
            } else {
                
                echo display_validation_error("Cannot find that email address. You do not appear to be registered. Please register or contact support if you believe this is an error.");
                
            }
            
        
        } else {
            
            redirect("index.php");
            
        } //token check
        
        if(isset($_POST['cancel_submit'])) {
            
            
            redirect("login.php");
            
        }
        
    } // post request check
    
}

/* validation password reset code */

function validate_code() {
    
    //check that temp_access_code cookie is set
    if(isset($_COOKIE['temp_access_code'])) {
            
            if(!isset($_GET['email']) || !isset($_GET['code'])) {
                
                redirect("index.php");
                
            } else if(empty(($_GET['email']) || empty($_GET['code']))  ){ 
                
                redirect("index.php");
                
            } else {
                
                if(isset($_POST['code'])) {
                    
                    $email = clean($_GET['email']);
                    
                    $validation_code = clean($_POST['code']);
                    
                    $sql = "SELECT id FROM users WHERE validation_code = '" . escape($validation_code) . "' AND email = '" . escape($email) . "'";
                    // set_message("<p>$sql</p>");
                    $result = query($sql);
                    confirm($result);
                    
                    if(row_count($result) == 1) {
                        
                        setcookie('temp_access_code', $validation_code, time() + 900);
                        
                        redirect("reset.php?email=$email&code=$validation_code");
                        
                    } else {
                        
                        echo display_validation_error("Sorry your validation code seems to have expired.");
                        
                        redirect("recover.php");
                        
                    }
                    
                }
                
            }
        
    } else {
        
        set_message("<p class='bg-danger text-center'>Sorry your password reset period has expired.</p>");
        
        redirect("recover.php");
        
    }
    
    
    
}

//alternate validation for reset code bypass the code page
//take user straight to the password reset page without need to
//copy code into the box

function validate_code_2() {
    
        
    
    
    
}


/* password reset */

function password_reset() {
    
    if(isset($_COOKIE['temp_access_code'])) {
    
        if(isset($_GET['email']) && isset($_GET['code'])) {
        
            if(isset($_SESSION['token']) && isset($_POST['token']))  {

               if($_POST['token'] === $_SESSION['token']) {
                   
                   if($_POST['password'] === $_POST['confirm_password']) {
                       
                       $password = escape($_POST['password']);
                       $password = password_hash($password, PASSWORD_BCRYPT);
            
                       echo "Passwords match";
                       
                   } else {
                       
                       echo "Passwords do not match";
                       
                   }
                   
                   $sql = "UPDATE users SET password = '$password', validation_code = 0 WHERE email = '" . escape($_GET['email']) . "'";
                   $result = query($sql);
                   confirm($result);
                   
                   set_message("<p class='bg-success text-center'>Your password has been reset, please login.</p>");
                   
                   redirect("login.php");
                   
                   //echo "It works, token is set";
                   
               } else {
                   
                   echo "Something went wrong";
                   
               }

            }
            
        }
        
        

    } else {
        
        set_message("<p class='bg-danger text-center'>Sorry your reset period has expired, please try again.</p>");
        
        redirect("recover.php");
        
    }
    
}
    
    


?>