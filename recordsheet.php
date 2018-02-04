<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');
require 'rb.php';
R::setup('mysql:host=localhost; dbname=chatbot','johnnykokos','');
function getInfo(){
    $persons = R::FindAll('records', ' ORDER BY time DESC LIMIT 5 ');
    foreach($persons as $person){
        echo '<tr>
                <td>'.$person->time.'</td>
                <td>'.$person->name.' </td>
                <td>'.$person->coffee.' </td>
              </tr>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Records sheet</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h2>Records sheet</h2>
  <p>Here is the records of the cafeteria participants:</p>            
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Time</th>
        <th>Name</th>
        <th>Coffee</th>
      </tr>
    </thead>
    <tbody>
        <?php getInfo(); ?>
    </tbody>
  </table>
</div>

</body>
</html>
