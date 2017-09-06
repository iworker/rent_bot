<?php

require_once('vendor/autoload.php');
require_once('settings.php');
require_once('functions.php');

use PHPHtmlParser\Dom;

$users = getChatsSettings();

foreach ($users as $user_id => $url) {
    $hash = md5($url);

    $filename = "cache/offers_$hash.json";

    if (!file_exists($filename)) {
        touch($filename);
    }

    $saved_offers = json_decode(@file_get_contents($filename), true);

    if (empty($saved_offers)) {
        $saved_offers = [];
    }

    $modified = filemtime($filename);

    if (abs($modified - time()) > TIMEOUT) {
        if (!LOAD_FROM_TEMP) {
            $result = download($url);
            file_put_contents(TMP_FILE, $result);
        } else {
            $result = file_get_contents(TMP_FILE);
        }

        $dom = new Dom;
        $dom->loadStr($result, []);
        $items = $dom->find('.catalog-preview');

        foreach ($items as $item) {
            $link_elem = $item->find('a.title');
            $id = substr($link_elem->href, 3);

            if (isset($saved_offers[$id])) {
                continue;
            }

            $link = BASE_URL . $link_elem->href;
            $title = $link_elem->text;
            $metro = html_entity_decode($item->find('.metro > a')->text);
            $address = $item->find('.address')->text;
            $images = $item->find('img');

            $image = DEFAULT_IMAGE_URL;

            foreach ($images as $img) {
                if (!$img->src) {
                    continue;
                }

                $url = $img->src;

                if (strpos($url, 'http') === 0) {
                    $local_path = '/media/maps/' . $id . '.jpg';
                } else {
                    $local_path = $url;
                }

                if (!file_exists('.' . $local_path)) {
                    if (strpos($url, 'http') === 0) {
                        $image = $url;
                    } else {
                        $image = BASE_URL . $url;
                    }

                    $image_data = file_get_contents($image);

                    exec('mkdir -p ' . dirname('.' . $local_path));

                    file_put_contents('.' . $local_path, $image_data);
                }

                $image = BASE_LOCAL_URL . $local_path;
                break;
            }
            //
            $price_elem = $item->find('.price > strong');
            $price = html_entity_decode($price_elem->text);
            //
            $lis = $item->find('li');
            $props = [];

            foreach ($lis as $li) {
                $props[] = $li->text;
            }

            $properties = implode(', ', $props);

            $created_elem = $item->find('.date > span');
            $created_at = $created_elem->text;

            if (empty($created_at) || $created_at === ' ') {
                $created_elem = $item->find('.archive');
                $created_at = $created_elem->text;
            }

            $saved_offers[$id] = [
                'image' => $image,
                'metro' => $metro,
                'address' => $address,
                'properties' => $properties,
                'price' => $price,
                'created' => $created_at,
                'title' => $title,
                'link' => BASE_URL . '/id' . $id,
            ];
        }
    }

    $sent = 0;

    foreach ($saved_offers as $id => &$offer) {
        if (in_array($user_id, $offer['sent_to'])) {
            continue;
        }

        $message = <<<MESSAGE
    Ссылка на объявление: {$offer['link']}
    {$offer['title']}
    Цена: {$offer['price']} руб.
    {$offer['metro']}
    Адрес: {$offer['address']}
    Описание: {$offer['properties']}
    Дата добавления: {$offer['created']}
MESSAGE;

        // sendMessage($user_id, $message);
        // sendPhoto($chat_id, $offer['image'], $offer['title']);

        $offer['sent_to'][] = $user_id;

        ++$sent;
    }

    echo "Sent " . (($sent > 0) ? ($sent . ' messages to user ' . $user_id . '.') : 'no messages.');

    file_put_contents($filename, json_encode($saved_offers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

