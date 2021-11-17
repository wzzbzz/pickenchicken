<?php

namespace pickenchicken\Actions;

use \pickenchicken\Models\DailyScheduleOfGames;

// will be run on a cron job every 30 minutes
class refreshGamesFeedAction
{
    const FEED_URL = "https://api2.dimedata.net/v4/?feedID=470&api-key=a3ed14a6a5cf5c6d86aabfbef95ab6da";
    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function do()
    {

        // fetch feed
        $feed = json_decode(file_get_contents(self::FEED_URL));

        // we got nothing, get outta here
        if (empty($feed) || empty($feed->data)) {
            return false;
        }

        // sort games into date buckets
        $dailySchedule = array();
        foreach ($feed->data as $game) {
            preg_match("/[^\-]+\-.[^\-]+\-(.*)/", $game->gameUID, $matches);
            $dailySchedule[date("m/d/Y", strtotime($matches[1]))][] = $game;
        }

        foreach ($dailySchedule as $date => $games) {
            $post = \bandpress\Models\Posts::getPostByTitle($date, 'daily-schedule');
            // if there's no post, create one.
            if (empty($post)) {
                $post_date = date("Y-m-d 03:00:00", strtotime($date));

                $status = (strtotime($post_date) < time()) ? "publish" : "future";
                $args = [
                    'post_title' => $date,
                    'post_type' => 'daily-schedule',
                    'post_date' => $post_date,
                    'post_name' => sanitize_title($date),
                    'post_status' => $status
                ];
                $post_id = wp_insert_post($args);
                $post = get_post($post_id);
            }
            $schedule = new DailyScheduleOfGames($post);
            $schedule->updateGamesFromFeed($games);
        }
        update_option("last_feed_cron_time", time());

        echo get_option("last_feed_cron_time");
        die;
    }
}
