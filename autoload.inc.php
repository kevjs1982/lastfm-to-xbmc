<?php
function __autoload($class_name) 
{
    include 'Classes/' .  $class_name . '.class.php';
}

?>