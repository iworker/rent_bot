<?php

require_once('vendor/autoload.php');
require_once('settings.php');
require_once('functions.php');

use PHPHtmlParser\Dom;

$saved_offers = json_decode(@file_get_contents('offers.json'), true);

if (empty($saved_offers))
{
    $saved_offers = [];
}

if (!LOAD_FROM_TEMP) {
    $result = download(URL);
    file_put_contents(TMP_FILE, $result);
} else {
    $result = file_get_contents(TMP_FILE);
}

$dom = new Dom;
$dom->loadStr($result, []);
$items = $dom->find('.catalog-preview');

foreach ($items as $item) {
    $link_elem = $item->find('a.title');
    $id        = substr($link_elem->href, 3);

    if (isset($saved_offers[$id]))
    {
        continue;
    }

    $link  = BASE_URL . $link_elem->href;
    $title = $link_elem->text;
    $metro   = html_entity_decode($item->find('.metro > a')->text);
    $address = $item->find('.address')->text;
    $images  = $item->find('img');

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
    $lis   = $item->find('li');
    $props = [];

    foreach ($lis as $li) {
        $props[] = $li->text;
    }

    $properties = implode(',', $props);

    $created_elem = $item->find('.archive');
    $created_at   = $created_elem->text;

    $saved_offers[$id] = [
        'image'      => $image,
        'metro'      => $metro,
        'address'    => $address,
        'properties' => $properties,
        'price'      => $price,
        'created'    => $created_at,
        'title'      => $title,
        'new'        => true,
    ];
}

foreach ($saved_offers as $id => $offer)
{
    if (!$offer['new'])
    {
        continue;
    }

    echo 'Sent 1 message' . PHP_EOL;

    unset($saved_offers[$id]['new']);

    $message = <<<MESSAGE
{$offer['title']}
Цена: {$offer['price']} руб.
{$offer['metro']}
Адрес: {$offer['address']}
Описание: {$offer['properties']}
Дата добавления: {$offer['created']}

{$offer['image']}

MESSAGE;

    sendMessage(CHAT_ID, $message);
}

file_put_contents('offers.json', json_encode($saved_offers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

