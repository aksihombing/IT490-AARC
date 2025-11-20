<?php>

// this script is to go on the backup VM only 
const primaryVM = "172.28.172.114";  // might need to be change bc it might be a differnt machine ip address? 
const backVM = ""; // i chose this bc im going to make it match with the backup vm 

const heartBeatURL = "http://172.28.172.114/healthCheck.php";  
const timeCheck = 2; 
const timeFailture = 3;

// shell scripts / the file paths 
const db = 'sudo /user/local/bin/db.sh';
const dbTakeover = 'sudo /user/local/bin/dbTakeover.sh';

function primaryVM 
(

)
</php>

