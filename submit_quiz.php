<?php
$score=0;

if($_POST['q1']=="Delhi") $score++;
if($_POST['q2']=="1950") $score++;

echo "Your Score: ".$score;
?>