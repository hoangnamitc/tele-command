<?php

namespace HoangnamItc\TeleCmd;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Telegram\Bot\Objects\User;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Traits\Telegram;
use Illuminate\Support\Collection;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\ChatMember;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\Objects\Update as UpdateObject;

/**
 * AbstractCommand
 *
 * - protected string $pattern: dữ liệu lấy về từ Bot. vd: `{domain}` HOẶC `{param} {param2}`
 * - protected string $name: định nghĩa lệnh gửi tới bot (ko bao gồm dấu /). vd: `start` HOẶC `help`
 */
abstract class AbstractCommand extends Command
{
    use Telegram;

    protected array $ownerIds = [];

    protected string $pattern = '{domain}';

    /**
     * @link https://core.telegram.org/bots/api#deletemessage
     */
    protected function deleteMess(int $chatId, int $messId)
    {
        return $this->telegram->deleteMessage([
            'chat_id'    => $chatId,
            'message_id' => $messId,
        ]);
    }

    /**
     * @link https://core.telegram.org/bots/api#editmessagetext
     */
    protected function editMess(int|string $chatId, int $messId, string $text, string $parseMode = 'markdown', bool $disableWebPagePreview = true): Message
    {
        return $this->telegram->editMessageText([
            'chat_id'                  => $chatId,
            'message_id'               => $messId,
            'text'                     => $text,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => $disableWebPagePreview,
        ]);
    }

    /**
     * Gán chủ sở hữu bot
     */
    protected function setOwnerIds(array $ownerIds): void
    {
        $this->ownerIds = $ownerIds;
    }

    /**
     * Thêm chủ sở hữu bot
     */
    protected function addOwnerId(int $owner): void
    {
        $this->ownerIds[] = $owner;
    }

    protected function getHookUpdate(bool $shouldDispatchEvents = true, ?RequestInterface $request = null): UpdateObject
    {
        return $this->telegram->getWebhookUpdate($shouldDispatchEvents, $request);
    }

    /**
     * @link https://core.telegram.org/bots/api#getchat
     *
     * @throws TelegramSDKException
     */
    protected function getChat(): Collection
    {
        return $this->getHookUpdate()->getChat();
    }

    /**
     * @return UpdateObject[]
     */
    protected function getUpdateObject(array $params = [], bool $shouldDispatchEvents = true): array
    {
        return $this->telegram->getUpdates($params, $shouldDispatchEvents);
    }

    /**
     * Nhận về các tham số từ đoạn chát
     */
    protected function getPatternValues(): Collection
    {
        $keys = str($this->getPattern())
            ->between('{', '}')
            ->explode('} {')
            ->unique()
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => filled($item));

        $result = collect();

        if ($keys->isEmpty()) {
            return $result;
        }

        foreach ($keys as $name) {

            $value = $this->argument($name);

            if (blank($value)) {
                continue;
            }

            $result->add($value);
        }

        return $result;
    }

    /**
     * Xác định rằng không có bất kỳ tham số nào đc truyền từ người chát
     */
    protected function isParameterEmpty(): bool
    {
        if (empty($this->getPattern())) {
            return true;
        }

        return $this->getPatternValues()->isEmpty();
    }

    /**
     * Lấy giá trị của pattern theo vị trí (áp dụng cho $pattern là số nhiều)
     *
     * @return mixed|false
     */
    protected function getPatternArg(int $position): mixed
    {
        $collect = $this->getPatternValues();

        if ($collect->isEmpty() || ! $collect->has($position)) {
            return false;
        }

        return $collect->get($position, false);
    }

    /**
     * Xác định loại của cuộc trò chuyện
     *
     * @return string ```private``` ```group``` ```supergroup``` ```channel```
     */
    protected function getType(): ?string
    {
        return $this->getChat()->get('type');
    }

    /**
     * Get the number of members in a chat.
     *
     * ````
     * $params = [
     *      'chat_id'  => '',  // string|int - Unique identifier for the target chat or username of the target supergroup or channel (in the format "@channelusername").
     * ]
     * ````
     *
     * @link https://core.telegram.org/bots/api#getchatmembercount
     *
     * @throws TelegramSDKException
     */
    protected function getMemberCount(array $params): int
    {
        return $this->telegram->getChatMemberCount($params);
    }

    /**
     * Get information about a member of a chat.
     *
     * ````
     * $params = [
     *      'chat_id'  => '',  // string|int - Unique identifier for the target chat or username of the target supergroup or channel (in the format "@channelusername").
     *      'user_id'  => '',  // int        - Unique identifier of the target user.
     * ]
     * ````
     *
     * @link https://core.telegram.org/bots/api#getchatmember
     *
     * @throws TelegramSDKException
     */
    protected function getMembers(array $params): ChatMember
    {
        return $this->telegram->getChatMember($params);
    }

    /**
     * Get a list of administrators in a chat.
     *
     * ````
     * $params = [
     *      'chat_id'  => '',  // string|int - Unique identifier for the target chat or username of the target supergroup or channel (in the format "@channelusername");
     * ]
     * ````
     *
     * @link https://core.telegram.org/bots/api#getchatadministrators
     *
     * @return ChatMember[]
     *
     * @throws TelegramSDKException
     */
    protected function getAdministrator(array $params): array
    {
        return $this->telegram->getChatAdministrators($params);
    }

    protected function from(): Collection|User
    {
        return $this->getHookUpdate()->getMessage()->get('from', collect());
    }

    /**
     * Xác định username của người gửi tin nhắn
     */
    protected function getUsernameFrom(): ?string
    {
        return $this->from()->get('username');
    }

    /**
     * Xác định firstName của người gửi tin nhắn
     */
    protected function getFirstNameFrom(): ?string
    {
        return $this->from()->get('firstName');
    }

    /**
     * Xác định lastName của người gửi tin nhắn
     */
    protected function getLastNameFrom(): ?string
    {
        return $this->from()->get('lastName');
    }

    /**
     * Tìm và trả về tên người dùng hiện tại
     *
     * Trường Username chưa đặt thì lấy họ hoặc tên
     */
    protected function detectNameFrom(): ?string
    {
        $names = [
            $this->getUsernameFrom(),
            $this->getFirstNameFrom(),
            $this->getLastNameFrom(),
        ];

        foreach ($names as $name) {
            if (filled($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * is SuperAdmin
     */
    protected function isSuperAdmin(): bool
    {
        return $this->isOwner();
    }

    /**
     * Xác định đúng là người sở hữu BOT
     */
    protected function isOwner(): bool
    {
        return in_array($this->getChatId(), $this->ownerIds);
    }

    /**
     * Xác định người tạo ra bot có là admin của nhóm không?
     */
    protected function ownerBotIsAdminOfGroup(): bool
    {
        if (! $this->isGroupChat()) {
            return false;
        }

        $groupId = $this->getChatId();

        if (is_null($groupId)) {
            return false;
        }

        $administrators = $this->getAdministrator(['chat_id' => $groupId]);

        $first = collect($administrators)->first(function ($admin) {

            $user    = $admin->getUser();
            $adminId = $user->getId();

            // $adminUsername = $user->getUsername();

            return in_array($adminId, $this->ownerIds);
        });

        return ! is_null($first);

    }

    /**
     * Xác định tin nhắn đến từ nhóm
     */
    protected function isGroupChat(): bool
    {
        return in_array($this->getType(), ['group', 'supergroup']);
    }

    /**
     * Xác định tin nhắn đến từ cá nhân
     */
    protected function isPrivateChat(): bool
    {
        return $this->getType() === 'private';
    }

    /**
     * Trả về id của cuộc hội thoại từ vị trí có bot (userId | groupId)
     */
    protected function getChatId(): string|int|null
    {
        $array = [$this->getChat()->first(), $this->getChat()->get('id')];

        foreach ($array as $value) {

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Kiểm tra quyền với nhóm chát
     *
     * - Nhóm chát phải có owner là Admin
     */
    protected function authGroupChat(): bool
    {
        if (! $this->isGroupChat()) {
            return true;
        }

        $result = $this->ownerBotIsAdminOfGroup();

        if (! $result) {
            $this->setDescription('Đây là đâu? Tại sao tôi lại ở trong nhóm này? 🥸');
        }

        return $result;
    }

    /**
     * Kiểm tra quyền với chát riêng
     *
     * - Không chát với người Lạ
     */
    protected function authPrivateChat(): bool
    {
        if (! $this->isPrivateChat()) {
            return true;
        }

        $result = $this->isOwner();

        if (! $result) {
            $this->setDescription('Bạn không có quyền gọi lệnh với tôi đâu 😝');
        }

        return $result;
    }

    /**
     * Kiểm tra tính hợp lệ của BOT.
     * - Hợp lệ khi không chát riêng với người lạ.
     * - Hợp lệ khi không tham gia nhóm của người lạ.
     */
    protected function auth(): bool
    {
        return $this->isGroupChat()
            ? $this->authGroupChat()
            : $this->authPrivateChat();
    }

    /**
     * Gửi 1 link curl
     *
     * Nếu `$resultFully === false` thì trả về OK là thành công và ngược lại là các status của HTTP
     */
    protected function sendUri(string $uri, string $method = 'GET', bool $resultFully = false): ResponseInterface|string
    {
        $method = str($method)->trim()->upper()->toString();
        $uri    = str($uri)->trim()->stripTags()->toString();

        if (blank($method) || blank($uri)) {
            return false;
        }

        $client  = new Client();
        $request = new Request($method, $uri);
        $result  = $client->sendRequest($request);

        return $resultFully
            ? $result
            : $result->getReasonPhrase();
    }

    protected function linkValidate(string $link): bool
    {
        return str($link)->match('#((https?)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i')->isNotEmpty();
    }
}
