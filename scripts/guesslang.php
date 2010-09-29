<?php

require_once("scripts/cmdline.php");
require_once("engine/start.php");

function set_lang($entity)
{
    echo "{$entity->get_url()}";
    $entity->language = GoogleTranslate::guess_language($entity->content);
    if ($entity->language)
    {
        echo " -> {$entity->language}";
        $entity->save();        
    }
    echo "\n";
}

foreach (NewsUpdate::query()->where('language is null')->filter() as $newsUpdate)
{
    set_lang($newsUpdate);
}

foreach (Widget::query()->where('language is null')->where("content <> ''")->filter() as $widget)
{
    set_lang($widget);
}

foreach (FeaturedSite::query()->where('language is null')->where("content <> ''")->filter() as $featuredSite)
{
    set_lang($featuredSite);
}