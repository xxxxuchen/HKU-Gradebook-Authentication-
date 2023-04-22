<?php
  session_start();
  if(isset($_SESSION['uid']) && isset($_GET['course'])){

    if (isset($_SESSION['start']) && (time() - $_SESSION['start'] > 300)) {
      sessionExpire();
    }
    displayScores($_SESSION['uid'], $_GET['course']);
    
  }else{
    sessionExpire();
  }

  function sessionExpire(){
    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(),'',time()-3600, '/');
    }
    session_unset();
    session_destroy();
    setcookie("expire", "expire", time()+5 , "/");
    header('Location: http://localhost:9080/login.php');
  }

  function displayScores($uid, $course){
?>
    <h1>COMP3322B-Gradebook</h1>
<?php
    $db_conn=mysqli_connect("mydb", "dummy", "c3322b", "db3322")
      or die("Connection Error!".mysqli_connect_error());
    $query="SELECT * FROM courseinfo WHERE uid = '$uid' AND course = '$course'";
    $result = mysqli_query($db_conn, $query)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    $total = 0;
    if (mysqli_num_rows($result) > 0) {
?>
      <div class="container">
      <p class="grid-title"> Assessment Scores: </P>
      <div class="grid-container">
        <div class="item center">Item</div>
        <div class="score center">Score</div>
<?php
      while ($row=mysqli_fetch_array($result)) {
        $total = $total + $row['score'];
        echo "<div class=padding-left>".$row['assign']."</div>";
        echo "<div class=center>".$row['score']."</div>";
      }
?>
        <div></div>
        <div class="total">Total: <?php echo "<span>".$total."</span>" ?></div>
      </div>
      </div>
<?php
    }else{
      echo "<p class=error-message>You do not have the gradebook for the course: COMP3322A in the system</p>";
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment-4 Gradebooks</title>
  <link rel="stylesheet" href="../styles/scores.css" />
</head>
<body>

</body>
</html>