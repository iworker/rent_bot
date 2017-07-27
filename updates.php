<?php

require_once('functions.php');

define('START_COMMAND', '/start');
define('UNSUBSCRIBE_COMMAND', '/unsubscribe');

$lock = fopen('updates.lock', 'w');

if (!flock($lock, LOCK_EX | LOCK_NB))
{
    die;
}

if (!file_exists('users.json'))
{
    touch('users.json');
}

while (true) {
    $users = json_decode(@file_get_contents('users.json'), true);

    $last_update_id = $users['last_update_id'];

    $result = json_decode(getUpdates($last_update_id), true);

    if ($result['ok']) {
        foreach ($result['result'] as $update) {
            $update_id = $update['update_id'];

            if ($last_update_id >= $update_id) {
                continue;
            }

            $message = $update['message'];

            switch ($message['text']) {
                case START_COMMAND:
                    $chat_id = $message['chat']['id'];

                    $users['users'][] = $chat_id;

                    sendMessage($chat_id, "Hello. You're subscribed. Type /unsubscribe to unsubscribe");
                    break;
                case UNSUBSCRIBE_COMMAND:
                    $chat_id = $message['chat']['id'];

                    $idx = array_search($chat_id, array_column($users, 'chat_id'));

                    unset($users['users'][$idx]);

                    sendMessage($chat_id, "Hello. You're unsubscribed. Type /start to subscribe again");
                    break;
            }

            $last_update_id = $update_id;
        }
    }

    $users['last_update_id'] = $last_update_id;

    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

flock($lock, LOCK_UN);