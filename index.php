<?php include("includes/header.php"); ?>
<?php include("includes/nav.php"); ?>



	<div class="jumbotron">
		<h1 class="text-center"> Home Page</h1>
	</div>
	
	<?php
        
        /*
        
        if($con) {
            
            echo "db connected" . "<br>";
            
        }
        
        */

        $sql = "SELECT * FROM users";

        $result = query($sql);

        confirm($result);

        $row = fetch_array($result);

        echo $row['username'] . " " . $row['first_name'] . " " . $row['last_name'];



    ?>


<?php include("includes/footer.php"); ?>
