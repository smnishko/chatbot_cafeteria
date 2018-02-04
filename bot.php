<?php
$verify_token = 'TOKEN123456abcd';
$token  = "EAAExZAZBDWvugBAKNzJ1V5KUV2gOvVyADK0Qfeqg2RxWsaDoZAZC8bZCLa8AUZBRtbI5bFSNbW1NlMZAHCyYoMQvtjwO7YWQvuzwhSGFQKqrbZAIexbVkggUu0Py28xHS3lnXZBiWXMJ5djOQPlBI0DUyDjg7VAHoVRh98WS8J3SiLAZDZD";
require_once(dirname(__FILE__) . '/vendor/autoload.php');
require 'rb.php';
R::setup('mysql:host=localhost; dbname=chatbot','johnnykokos','');
use pimax\FbBotApp;
use pimax\Messages\Message;
use pimax\UserProfile;
use pimax\Messages\QuickReply;
use pimax\Messages\SenderAction;
use pimax\Menu\MenuItem;
use pimax\Menu\LocalizedMenu;


$bot = new FbBotApp($token);

$bot->setGetStartedButton("HELLO");
$bot->deletePersistentMenu();
$bot->setPersistentMenu(createMenu());
$bot->deleteGetStartedButton();

if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' && $_REQUEST['hub_verify_token'] == $verify_token)
{
     // Webhook setup request
    echo $_REQUEST['hub_challenge'];
}else{
     $data = json_decode(file_get_contents("php://input"), true);       
     if (!empty($data['entry'][0]['messaging']))
     {
        foreach ($data['entry'][0]['messaging'] as $message)
        {
           
            if (!empty($message['delivery'])) continue;
            $id = $message['sender']['id'];
            if (recordNewPerson($message['sender']['id'], $bot)){$bot->send(new Message($message['sender']['id'], "Hey ".$bot->userProfile($id)->getFirstName().", nice to see you here! Use the menu bellow to order the coffee or just type here. And don't forget about the first free cofee ;)"));};
 
            if (!empty($message['postback']['payload']) or !empty($message['message']['quick_reply']['payload'])){
                if (!empty($message['postback']['payload'])) {$command = $message['postback']['payload'];};
                if (!empty($message['message']['quick_reply']['payload'])) {$command = $message['message']['quick_reply']['payload'];};

                if(stripos($command,'BALANCE_SHOW')!==false)   {setBalance($id, $bot, 'BALANCE_SHOW');};
                if(stripos($command,'ESPRESSO_ORDER')!==false) {setBalance($id, $bot, 'ESPRESSO_ORDER');};
                if(stripos($command,'LATTE_ORDER')!==false)    {setBalance($id, $bot, 'LATTE_ORDER');};
                if(stripos($command,'Yes')!==false)            {checkPhrase($id, $bot, 'offee');};
                if(stripos($command,'No')!==false)             {$bot->send(new Message($id, "ok, cheers :)"));};
            }else{

                $command = $message['message']['text'];
                checkPhrase($id, $bot, $command);
                if(stripos($command,'add')!==false)            {increaseBalance($id, $command, $bot);};
            }
        }
     }
}
function recordNewPerson($id, $bot){
    $persons = R::findOne('persons','number='.$id);
    if (empty($persons)){
        $person = R::dispense('persons');
        $person->number = $id;
        $person->freeCoffee = true;
        $person->balance = 0;
        $person->admin = false;
        $person->name = $bot->userProfile($id)->getFirstName();
        R::store($person);
        return true;
    }
    return false;
}
function getButton($title, $payload, $cup=false){
        $arr = array();
        $arr["title"] = $title;
        $arr["payload"] = $payload;
        $arr["content_type"] = 'text';
        if ($cup) {$arr["image_url"] = "https://cafeteria-bot-johnnykokos.c9users.io/1.png";};
        return $arr;
}
function createMenu(){

    $myAccountItems[] = new MenuItem('postback', 'Balance', 'BALANCE_SHOW');
    $CoffeeItems[]   = new MenuItem('postback', 'Espresso (2€)', 'ESPRESSO_ORDER');
    $CoffeeItems[]   = new MenuItem('postback', 'Latte (3€)', 'LATTE_ORDER');

    $account = new MenuItem('nested', 'Account', $myAccountItems);
    $coffee = new MenuItem('nested', 'Coffee', $CoffeeItems);

    $enMenu = new LocalizedMenu('default', false, [
      $account,
      $coffee
    ]);
     
    $localizedMenu[] = $enMenu;
    return $localizedMenu;
    
}
function checkPhrase($id, $bot, $command){

    if ((strpos($command, 'atte') !== false) and (strpos($command, 'spresso') !== false)) {
            $buttons = array(getButton("Latte", "LATTE_ORDER", true), getButton("Espresso", "Espresso_ORDER", true)); 
            $bot->send(new QuickReply($id, "What do you prefer?", $buttons));
    }elseif (strpos($command, 'atte') !== false) {
        setBalance($id, $bot, 'LATTE_ORDER');   
    }elseif (strpos($command, 'spresso') !== false){
        setBalance($id, $bot, 'ESPRESSO_ORDER');  
    }elseif (strpos($command, 'alance') !== false){
        setBalance($id, $bot, 'BALANCE_SHOW');    
    }elseif (strpos($command, 'offee') !== false){
        if ((strpos($command, "don't") !== false) or (strpos($command, "do not") !== false)){
            $buttons = array(getButton("Yes", "Yes"), getButton("No", "No")); 
            $bot->send(new QuickReply($id, "I dont understand, do you want a coffee? :)", $buttons));         
        }else{
            $buttons = array(getButton("Latte", "LATTE_ORDER", true), getButton("Espresso", "Espresso_ORDER", true)); 
            $bot->send(new QuickReply($id, "What do you want?", $buttons));            
        }
    }

}
function setBalance($id, $bot, $str){
    $persons = R::findOne('persons','number='.$id);
    $person = R::Load('persons', $persons->id);
    $text = "";
   
    if ($str=="ESPRESSO_ORDER"){
        if ($person->free_coffee){
            $person->free_coffee = false;
            recordTransaction($person->name,"Espresso");
            $text = "Espresso is done, and this one is on the house ;)";
        }elseif ($person->balance-2 >= 0){
            $person->balance = $person->balance - 2;
            recordTransaction($person->name,"Espresso");
            $text = "Espresso is ready, you can take it.";
        }else{
            $text = "Sorry, not enough money for Espresso";    
        } 
    }
    if ($str=="LATTE_ORDER"){
        if ($person->free_coffee){
            $person->free_coffee = false;
            recordTransaction($person->name,"Latte");
            $text = "Latte is done, and this one's on the house ;)";
        }elseif ($person->balance-3 >= 0){
            $person->balance = $person->balance - 3;
            recordTransaction($person->name,"Latte");
            $text = "Latte is ready, you can take it.";
        }else{
            $text = "Sorry, not enough money for the Latte";    
        } 
    }
    if ($str=="BALANCE_SHOW"){
        $text= "Your balance is: ".$person->balance;    
    }
    R::store($person);
    $bot->send(new Message($id, $text));
}
function increaseBalance($id, $command, $bot){
    
    $admin = R::FindOne('persons', 'number="'.$id.'"');
    if ($admin->admin){
        $words = explode(" ", $command);
        $person = NULL; $number = NULL;
            
        foreach($words as $word){
            $per = R::findOne('persons','name="'.$word.'"');
            if (!empty($per->number))    {$person = $per;};
            if (is_numeric($word))  {$number = intval($word);};
        }
        if (is_null($person) or is_null($number)) {
            if (is_null($number))  {$bot->send(new Message($id, "check the amount of money"));};
            if (is_null($person))  {$bot->send(new Message($id, "check the name"));};
        }else{
            $per = R::load('persons', $person->id);
            $per->balance = $per->balance + $number;
            R::store($per);
            $bot->send(new Message($id, "Done: ".$person->name." +".$number));
        }
    }  
}
function recordTransaction($name, $coffee){
   
    $record = R::dispense('records');
    $record->time = date("Y-m-d H:i:s");
    $record->name = $name;
    $record->coffee = $coffee;
    R::store($record);
}

?>