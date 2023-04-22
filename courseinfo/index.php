<?php
  session_start();
  if(isset($_SESSION['uid'])){

    if(!isset($_SESSION['start'])){
      $_SESSION['start'] = time();
    }

    if (isset($_SESSION['start']) && (time() - $_SESSION['start'] > 300)) {
      sessionExpire();
    }
    displayCourses($_SESSION['uid']);

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

  function displayCourses($uid){
?>
    <div class="container">
      <header>
        <h1> Course Information </h1>
        <h4> Retrieve continuous assessment scores for: </h4>
      </header>
      <div class="course-title-container">
<?php
    $db_conn=mysqli_connect("mydb", "dummy", "c3322b", "db3322")
      or die("Connection Error!".mysqli_connect_error());
    $query="SELECT * FROM courseinfo WHERE uid = '$uid'";
    $result = mysqli_query($db_conn, $query)
      or die("<p>Query Error!<br>".mysqli_error($db_conn)."</p>");
    $uniqueCourse = '';
    if (mysqli_num_rows($result) > 0) {
      while ($row=mysqli_fetch_array($result)) {
        if($row['course'] != $uniqueCourse){
          $uniqueCourse = $row['course'];
          echo "<p><a href=./getscore.php?course=".$row['course'].">".$row['course']."</a></p>";
        }
      }
    }else{
        echo "<p>No record!!</p>";
      }
?>
      </div>
    </div>
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
  <link rel="stylesheet" href="../styles/course.css" />
</head>
<body>

</body>
</html>