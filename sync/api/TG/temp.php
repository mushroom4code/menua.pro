<?php

//print date("Y-m-d H:i:s");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

//sendSticker
printr(sendTelegram('sendSticker', ['chat_id' => '325908361', 'sticker' => 'CAACAgIAAxkBAAMFYI_S0LndE9RlJQ3roFS6zmst31YAAj8AA0QNzxfQ0mUJZ61_Eh8E']));
