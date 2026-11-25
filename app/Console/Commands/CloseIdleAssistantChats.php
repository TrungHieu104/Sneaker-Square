<?php

namespace App\Console\Commands;

use App\Actions\CloseConversationAction;
use Illuminate\Console\Command;

class CloseIdleAssistantChats extends Command
{
    protected $signature = 'assistant:close-idle';

    protected $description = 'Đóng các cuộc chat nhân viên đã trả lời mà khách không nhắn thêm, để trợ lý trả lời lại';

    public function handle(CloseConversationAction $close): int
    {
        $this->info('Đã đóng '.$close->closeIdle().' cuộc chat.');

        return self::SUCCESS;
    }
}
