# Telegram Api Command Abstract Class

```
composer require hoangnamitc/tele-command
```

## Gửi tin nhắn

[replyWithMessage](https://telegram-bot-sdk.com/docs/guides/commands-system/#basic-command)

```
$result = $this->replyWithMessage([
    'text'       => $this->getDescription(),
    'parse_mode' => 'markdown',
]);
```

là Alias của

```
sendMessage()
```

Thêm thêm tại đây:
[sendMessage](https://core.telegram.org/bots/api#sendmessage)

@return
[Message](https://core.telegram.org/bots/api#message)

## Xóa tin nhắn

- Xóa tin của người gửi gần nhất

```
$this->deleteMess(
    $this->getChatId(),
    $this->getMessId()
);
```

- Xóa tin của bot trả về, viết sau $result trên

```
$this->deleteMess(
    $this->getChatId(),
    data_get($result, 'message_id')
);
```
