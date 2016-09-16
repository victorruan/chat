<?php
$client_id = $_GET['client_id'];
\Workerman\Protocols\Http::header('Content-Type:image/jpeg');
global $images;
global $users;
if((empty($images))or(!count($images)>0)){
    $card_list = json_decode(file_get_contents("http://vgdb.ptbus.com/api/?s=card_list"),true)['result'];
    array_walk($card_list,function($value,$key){
        global $images;
        $images[] = $value['icon'];
    });
}else{

}
if(!isset($users[$client_id]['photo'])){
    $users[$client_id]['photo'] = file_get_contents($images[array_rand($images,1)]);
}
echo $users[$client_id]['photo'];
