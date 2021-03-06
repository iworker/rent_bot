<?php

require_once('settings.php');

function download($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,**;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
    ]);
    curl_setopt($ch, CURLOPT_ENCODING , "gzip");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function sendMessage($chat_id, $message)
{
    $url = 'https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage';

    $data = json_encode([
        'chat_id'    => $chat_id,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_exec($ch);
    curl_close($ch);
}

function sendPhoto($chat_id, $photo, $caption)
{
    $url = 'https://api.telegram.org/bot' . TG_TOKEN . '/sendPhoto';

    $data = json_encode([
        'chat_id'              => $chat_id,
        'photo'                => $photo,
        'disable_notification' => true,
        'caption'              => $caption,
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);

    curl_exec($ch);
    curl_close($ch);
}


function getUpdates($offset)
{
    $data = [
        'offset'  => $offset + 1,
        'timeout' => 60,
    ];

    $url = 'https://api.telegram.org/bot' . TG_TOKEN . '/getUpdates?' . http_build_query($data);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getChatsSettings()
{
    $users = json_decode(@file_get_contents('users.json'), true);

    if (isset($users['users']))
    {
        return $users['users'];
    }

    return [];
}