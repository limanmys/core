<?php

$functions = get_defined_functions();
$keys = array_keys($functions['user']);
$last_index = array_pop($keys);

// Functions PHP
if(is_file($argv[1])){
    include($argv[1]);
}

$functions = get_defined_functions();
$new_functions = array_slice($functions['user'], $last_index);
$arr = [];
foreach($new_functions as $new){
    array_push($arr,[
        "name" => $new
    ]);
}
echo json_encode($arr);