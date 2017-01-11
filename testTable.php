<?php

echo 'Hello World';

require_once('table/table.php');

$pdo = new PDO('mysql:dbname=test;host=localhost','root','');
$sqlStmt = "SELECT * FROM `testcsv`";
$sth = $pdo->query($sqlStmt);
$sqlResult = $sth->fetchAll();

//print_r($sqlResult);

 $table = new Table($sqlResult);
echo $table->build();