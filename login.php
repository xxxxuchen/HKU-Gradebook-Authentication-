<?php
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;
  use PHPMailer\PHPMailer\Exception;
  
  require 'PHPMailer/src/Exception.php';
  require 'PHPMailer/src/PHPMailer.php';
  require 'PHPMailer/src/SMTP.php';
  session_start();
  setcookie('expire', time()-3600);
  if(isset($_POST["login"])) {
    $email=$_POST['email'];
    // check if user existed in database if not:
    if (!isUser('email', $email)){
      displayLoginForm("Unknown user - we don't have the records for ????@#####.hku.hk in the
      system.", "red");
      
    }else{
      // if yes: store the timestamp and secrete into database, and generate token and send email
      displayLoginForm("Please check your email for the authentication URL", "blue");
      $infoArray = setSecretAndTime();
      $encodingArray = $infoArray[0];
      $token = encodeToken($encodingArray);
      $tokenURL = "http://localhost:9080/login.php?token=".$token;
      $emailContent = "Dear Student,<br><br>You can log on to the system via the following link:<br>".$tokenURL;
      sendEmail($email, $emailContent);
    }
    
  }else if(isset($_GET['token'])){
    $curTime = time();
    $userInput = decodeToken(($_GET['token'])); //$userInput is an array [uid, secret]
    $userID = $userInput['uid'];
    
    if (isUser('uid', $userID) == false){
      displayLoginForm("Unknown user - cannot identify the student.", "red");
    }else{

      $infoArray = isUser('uid', $userID);
      if($curTime > $infoArray[1] + 60 ){
        displayLoginForm("Fail to authenticate - OTP expired!", "red");  

      }else if($userInput['secrete'] != $infoArray[0]['secrete']){
        displayLoginForm("Fail to authenticate - incorrect secret!", "red");

      }else{ // all good then redirect
        $_SESSION['uid'] = $userID;
        $redirectUrl = "http://localhost:9080/courseinfo/index.php";
        header('Location: '.$redirectUrl);
      }
    }
  }else{  // GET Method 
    if(($_COOKIE['expire']==='expire') && !isset($_SESSION['start'])){  // redirect from other page
      setcookie('expire', time()-3600);
      displayLoginForm("Session expired. Please login again.", "red");
    }else{  // GET directly
      displayLoginForm();
    }
  }
 
  function isUser($colName, $field){
    $db_conn=mysqli_connect("mydb", "dummy", "c3322b", "db3322")
      or die("Connection Error!".mysqli_connect_error());
    $query="SELECT * FROM user WHERE $colName = '$field'";
    $result = mysqli_query($db_conn, $query)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    if (mysqli_num_rows($result) <= 0) {
      return false;
    }else{
      return getInfoArray($result);;
    }
  }

  function setSecretAndTime(){
    $db_conn=mysqli_connect("mydb", "dummy", "c3322b", "db3322")
      or die("Connection Error!".mysqli_connect_error());

    $email=$_POST['email'];
    $timeStamp = time();
    $secret = bin2hex(random_bytes(8));
    $queryTime="UPDATE user SET timestamp ='$timeStamp' WHERE email='$email'";
    $result = mysqli_query($db_conn, $queryTime)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
  
    $querySecret="UPDATE user SET secret ='$secret' WHERE email='$email'";
    $result = mysqli_query($db_conn, $querySecret)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");

    $querySelect="SELECT * From user WHERE email='$email'";
    $result = mysqli_query($db_conn, $querySelect)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    return getInfoArray($result);
  }
  
  function deleteSecretAndTime($uid){
    $db_conn=mysqli_connect("mydb", "dummy", "c3322b", "db3322")
      or die("Connection Error!".mysqli_connect_error());
    $query="UPDATE user SET timestamp = NULL WHERE uid='$uid'";
    $result = mysqli_query($db_conn, $query)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    $query="UPDATE user SET secret = NULL WHERE uid='$uid'";
    $result = mysqli_query($db_conn, $query)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    // mysqli_free_result($result);
    mysqli_close($db_conn);
  }

  function getInfoArray($result){
    $rowArray = mysqli_fetch_array($result);
    $secretArray['uid'] = $rowArray['uid'];
    $secretArray['secret'] = $rowArray['secret'];
    $infoArray[0]=$secretArray;
    $infoArray[1]=$rowArray['timestamp'];
    return $infoArray;
  }

  function encodeToken($array){
    $jsonString = json_encode($array);
    $token = bin2hex($jsonString);
    return $token;
  }

  function decodeToken($token){
    $jsonString = hex2bin($token);
    $array = json_decode($jsonString, true);
    return $array;
  }

  function sendEmail($address, $content){
    $mail = new PHPMailer(true);

    if (isset($_POST['email'])) {
      try {
          //Server settings
          //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
          $mail->isSMTP();                                            //Send using SMTP
          $mail->Host       = 'testmail.cs.hku.hk';                     //Set the SMTP server to send through
          $mail->SMTPAuth   = false;                                   //Enable SMTP authentication
          $mail->Port       = 25;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
      
          //Sender
          $mail->setFrom('c3322@cs.hku.hk', 'COMP3322');
          //******** Add a recipient to receive your email *************
          $mail->addAddress($address);     
      
          //Content
          $mail->isHTML(true);                                  //Set email format to HTML
          $mail->Subject = 'Send by PHPMailer';
          $mail->Body    = $content;
          $mail->AltBody = $content;
      
          $mail->send();
          // echo 'Message has been sent';
      } catch (Exception $e) {
          echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }
    } else {
      echo "Please specify the recipent's email and name.";
    }
  }

  function displayLoginForm($message='', $color=''){
?>
    <h1 class="heading-login">Grade Book Accessing Page</h1>
    <div class="section-form">
      <form action="./login.php" method="post">
        <h2 class="form-title">My Gradebooks</h2>
        <div class="form-input">
          <label for="email" >Email: </label>
          <input type="email" id=email name="email" pattern=".+@cs\.hku\.hk|.+@connect.hku.hk" required>
        </div>
        <div class="btn">
          <input class="btn-login" type="submit" name="login" value="Login">
        </div>
      </form>
    </div>
    <div class="login-message <?php echo $color;?>"><?php echo $message ?></div>
<?php
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment-4 Gradebooks</title>
  <link rel="stylesheet" href="./styles/login-page.css" />
</head>
<body>

  <script>
    const iEmail= document.getElementById("email");
    iEmail.addEventListener("input", function (event) {
    if (iEmail.validity.patternMismatch) {
      console.log(iEmail.validationMessage);
      iEmail.setCustomValidity("Must be an email address with @cs.hku.hk or @connect.hku.hk");
    } else {
      iEmail.setCustomValidity("");
    }
    });
  </script>
</body>
</html>

