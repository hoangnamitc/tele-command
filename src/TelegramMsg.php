<?php

namespace HoangnamItc\TeleCmd;

use Telegram\Bot\Api;
use Larso\Support\Config;
use Telegram\Bot\Traits\Telegram;

class TelegramMsg
{
    use Telegram;

    public function __construct()
    {
        $this->telegram = new Api(Config::get('bot.token'));
    }

    /**
     * Create new object Telegram
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Send message
     *
     * @param  string  $message
     * @param  null|int  $userId
     * @return int
     */
    public function send(string $message, ?string $userId = null)
    {
        $userId = is_null($userId)
            ? Config::get('user.id')
            : $userId;

        $response = $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text'    => $message,
        ]);

        return $response->getMessageId();
    }
}
