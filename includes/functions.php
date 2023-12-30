<?php

//function to create user account (ie) to signup
function signup(){
    if(isset($_POST["submit"])){
      $name=$_POST["name"];
      $email=$_POST["email"];
      $password=$_POST["password"];
      $confirm_password=$_POST["confirm_password"];
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $error = array();
      if(empty($name) || empty($email) || empty($password) || empty($confirm_password)){
        array_push($error,"All fields are required");
      }else if(!preg_match ("/^[a-zA-z]*$/", $name)){ 
        array_push($error," Name should only contain alphabets");
      }else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        array_push($error,"Email is not valid");
      }else if(!preg_match ("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $password)){
        array_push($error,"Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters");
      }else if($password!==$confirm_password){
        array_push($error,"Password does not match");
      }
      if(count($error)>0){
        foreach($error as $errors){
        echo"<div class='alert alert-danger'>$errors</div>";
      }
      }else{
        require_once "connect.php";
        $sql = "SELECT * FROM `users` WHERE email='$email'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_num_rows($result);
        if($user>0){
          echo "<div class='alert alert-danger'>Email already exists!</div>";
        }else{
            $sql = "INSERT INTO `users`(name, email, password) VALUES(?,?,?)";
            $stmt = mysqli_stmt_init($conn);
            $preparestmt= mysqli_stmt_prepare($stmt,$sql);
            if($preparestmt){
                mysqli_stmt_bind_param($stmt,"sss",$name,$email,$hashed_password);
                mysqli_stmt_execute($stmt);
                session_start();
                $_SESSION["email"]="$email";
                $_SESSION["password"]="$password";
                $_SESSION["user"]="yes";
                header("Location: home.php");
            }else{
                die("something went wrong");
            }
        }
      }
    }
}

//function to login
function login(){
    if(isset($_POST["login"])) {
        $email=$_POST["email"];
        $password=$_POST["password"];
        if(empty($email) || empty($password)){
            echo "<div class='alert alert-danger'>All fields are required</div>";
        }else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo "<div class='alert alert-danger'>Email is not valid</div>";
        }else if(!preg_match ("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $password)){
            echo "<div class='alert alert-danger'>Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters</div>";
        }else{
            require "connect.php";
            $sql = "SELECT * FROM `users` WHERE email='$email'";
            $result = mysqli_query($conn, $sql);
            $user = mysqli_num_rows($result);
            if($user==1) {
              $sql= "SELECT password FROM `users` WHERE email='$email'"; 
              $result=mysqli_query($conn,$sql);
              $row = $result->fetch_assoc();
              $hash = $row["password"];
              if(password_verify($password, $hash)) { 
                session_start();
                $_SESSION["email"]="$email";
                $_SESSION["password"]="$password";
                $_SESSION["user"]="yes";
                header("Location: home.php");
              }else {
               echo "<div class='alert alert-danger'>Password is incorrect!</div>"; 
              }       
            }else {
            echo "<div class='alert alert-danger'>Email is incorrect!</div>"; 
            }
        }
    }
}

//function to logout session
function logout(){
    if(isset($_POST["logout"])) {
      unset($_SESSION['email']);
      unset($_SESSION['password']);
      session_destroy();
      header("Location: index.php");
    }
}
logout();

//function to change password
function change_password(){
    if(isset($_POST["submit"])) {
        $email=$_POST["email"];
        $old_password=$_POST["old_password"];
        $new_password=$_POST["new_password"];
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if(empty($email) || empty($old_password) || empty($new_password)){
          echo "<div class='alert alert-danger'>All fields are required</div>";
        }
        else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
          echo "<div class='alert alert-danger'>Email is not valid</div>";
        }
        else if($email != $_SESSION["email"]){
          echo "<div class='alert alert-danger'>Email is not valid</div>";
        }
        else if(!preg_match ("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $old_password)){
          echo "<div class='alert alert-danger'>Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters</div>";
        }
        else if(!preg_match ("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $new_password)){
            echo "<div class='alert alert-danger'>Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters</div>";
        }
        else{
          require_once "connect.php";
          $sql = "UPDATE `users` SET password='$hashed_password' WHERE email='$email'";
          $result = mysqli_query($conn, $sql);
          if($result){
            echo "<div class='alert alert-success'>Congratulations! You have successfully changed your password</div>";
          }
          else{
            echo "Old Password is wrong";
          }
        }
    }
}

//function to create new task
function create(){
  if(isset($_POST["add"])) {
    require_once "connect.php";
    $task=$_POST["task"];
    $sqlid="SELECT id FROM `users` where email = '$_SESSION[email]'";
    $id=mysqli_query($conn,$sqlid);
    $user = $id->fetch_assoc();
    $t = $user['id'];
    if($user>0){
      $sql = "INSERT INTO `tasks` (task,user_id) VALUES(?,?)";
      $stmt = mysqli_stmt_init($conn);
      $preparestmt= mysqli_stmt_prepare($stmt,$sql);
        if($preparestmt){
            mysqli_stmt_bind_param($stmt,"si",$task,$t);
            mysqli_stmt_execute($stmt);
        }else{
        die("something went wrong");
        }
    }
  }
}

//function to view the task
function read(){
  require "connect.php";
    $sql= "SELECT t.id,t.task,t.status,t.created_on  FROM `tasks` as t LEFT JOIN `users` as u ON t.user_id=u.id WHERE u.email='$_SESSION[email]'";
    $result = mysqli_query($conn, $sql);
    $id = 1;
    while($row = mysqli_fetch_array($result)){
      $idd =$row['id'];
      $task=$row['task'];
      $status=$row['status'];
      $date =$row['created_on'];
      echo '<tr>
            <td>'. $id++ .'</td>
            <td>'. $task .'</td>
            <td>'. $status .'</td>
            <td>'. $date .'</td>
            <td><form action="update.php" method="get"><a href="update.php?edit='.$idd.'"><input class="btn" type="submit" value="" name="edit" style="decoration:none"><img src=images/edit.png></a></form></td>
            <td><form action="home.php" method="get"><a href="home.php?delete='.$idd.'"><input class="btn" type="submit" value="" name="delete" style="decoration:none"><img src=images/del.png></a></form></td>
            </tr>'; 
    }
}


//function to edit the task
function edit(){
  if(isset($_GET['edit'])){
    $id=$_GET['edit'];
    require_once "connect.php";
    $sql= "SELECT task ,status, created_on  FROM `tasks` WHERE id='$id'";
    $result = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_array($result)){
      $taskk= $row['task'];
      $status= $row['status'];
      $datetime= $row['created_on'];
      echo '<div class="container-fluid">
            <form action="#" method="post">
            <div class="container">
            <div class="row">
            <div class="col col-lg col-sm me-2">
              <label class="form-label" for="task" >Task</label>
              <textarea class= "form-control border-#adb5bd" id="task" name="edittask" style="width:320px;height:100px;" placeholder="'.$taskk.'"></textarea>
            </div>
            <div class="col col-lg col-sm">
              <label class="form-label" for="date" >Created On</label>
              <input type="text" class="form-control border-#adb5bd" id="date" name="datetime" style="width:250px;height:50px;" placeholder="'.$datetime.'" readonly>
            </div>
            <div class="col col-lg col-sm">
              <label class="form-label" for="dropdown">Status</label>
              <select class="form-select" name="status" style="width:250px;height:50px;">
              <option>Todo</option>
              <option>In Progress</option>
              <option>Completed</option>
              </select>
            </div>
            </div><br><br>
            <input type="submit" name="save" class="btn" style="width: 150px; background-color:#E74E35; color:white;" value="Save changes">
            </div>
            </form>
            </div>';
    }
  }
}

//function to update the task
function update(){
if(isset($_POST['save']) && isset($_GET['edit'])){
  $idd=$_GET['edit'];
  $edittask=$_POST['edittask'];
  $status=$_POST['status'];
  if(!empty($edittask)){
    require "connect.php";
      $sqltask = "UPDATE `tasks` SET task='$edittask',created_on= now() WHERE id='$idd'";
      $resulttask= mysqli_query($conn,$sqltask);
      if($resulttask){
        header("location: home.php");
      }else "something went wrong";
  }
  if(!empty($status)){
    require "connect.php";
      $sqlstatus = "UPDATE `tasks` SET created_on= now(), status='$status' WHERE id='$idd'";
      $resultstatus= mysqli_query($conn,$sqlstatus);
      if($resultstatus){
        header("location: home.php");
      }else "something went wrong";
  }
}else "something went wrong";
}
  
//function to delete the task  
function delete(){
  if(isset($_GET['delete'])){ 
    require "connect.php";
    $id = $_GET['delete'];
    $sql = "DELETE FROM `tasks` WHERE id='$id'";
    if(mysqli_query($conn,$sql)){
      echo "successful";
    }
  }else{
    echo"Something went wrong";
  }
}

//function to view user profile
function profile(){
  require_once "connect.php";
    $sql= "SELECT name,email FROM `users` WHERE email ='$_SESSION[email]'";
    $result = mysqli_query($conn, $sql);
    $row = $result->fetch_assoc();
    if($result){
    echo "<h3>User Profile</h3><br>";
    echo "<p><b>Name: </b>". $row['name']. "<br><b>Email: &nbsp</b>". $row['email']."</p>";
}
}

?>