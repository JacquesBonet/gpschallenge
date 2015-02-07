<?php
   if (!file_put_contents('gpsChallenge_2015.json', $_POST['data'])) {
   		echo "write failed";
   		http_response_code(500);
   }
   else {
   		echo $_POST['modified'];
    	http_response_code(200);
   };
?>
