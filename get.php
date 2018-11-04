<?php

include 'db.php';

$db = new Db('mysql', 'localhost', 'chat', 'root', '');
$polls = $db->select('polls_polls', '*')->all();


if (empty($polls))
{
    die('Not found');
}

foreach ($polls AS $key => $one)
{
    $polls[$key]->admin_title = $one->title;
    $polls[$key]->locales = $db->select('polls_polls_locales', '*', array('poll_id' => $one->id))->all();

    $polls[$key]->coins = $db->select('polls_coins', '*', array('poll_id' => $one->id))->all();
    if (!empty($polls[$key]->coins))
    {
        foreach ($polls[$key]->coins as $k => $t)
        {
            $polls[$key]->coins[$k]->locales = $db->select('polls_coins_locales', '*', array('coin_id' => $t->id))->all();
        }
    }
}






echo json_encode($polls);

die;
