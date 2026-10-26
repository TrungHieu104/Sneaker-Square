<?php

namespace Tests\Feature;

use App\Logging\LogChannel;
use App\Mail\CartReminderMail;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\Support\CapturesLogs;
use Tests\TestCase;

/**
 * Which log file each kind of incident lands in, and for how long.
 */
class LogChannelsTest extends TestCase
{
    use CapturesLogs;

    public function test_moi_loai_log_mot_file_rieng_theo_ngay_giu_7_ngay(): void
    {
        $config = require config_path('logging.php');

        $this->assertSame(['daily'], $config['channels']['stack']['channels']);

        foreach (['daily' => 'laravel', LogChannel::PAYMENT => 'payment', LogChannel::SHIPPING => 'shipping', LogChannel::MAIL => 'mail'] as $channel => $file) {
            $this->assertSame('daily', $config['channels'][$channel]['driver']);
            $this->assertSame(7, $config['channels'][$channel]['days']);
            $this->assertSame(storage_path("logs/{$file}.log"), $config['channels'][$channel]['path']);
        }
    }

    public function test_mail_gui_di_ghi_nguoi_nhan_va_tieu_de(): void
    {
        $log = $this->captureLog(LogChannel::MAIL);

        Mail::raw('Nội dung', fn ($message) => $message->to('khach@example.test')->subject('Xác nhận đơn hàng'));

        $this->assertSame([[['khach@example.test'], 'Xác nhận đơn hàng']], array_map(
            fn (array $line) => [$line['to'], $line['subject']],
            $this->logged($log, 'Mail sent'),
        ));
    }

    public function test_mail_trong_hang_doi_that_bai_duoc_ghi_lai_viec_khac_thi_khong(): void
    {
        $log = $this->captureLog(LogChannel::MAIL);

        event(new JobFailed('redis', $this->job(SendQueuedMailable::class, CartReminderMail::class), new RuntimeException('SMTP từ chối')));
        event(new JobFailed('redis', $this->job('App\\Jobs\\Other', 'App\\Jobs\\Other'), new RuntimeException('Lỗi khác')));

        $this->assertSame([[CartReminderMail::class, 'SMTP từ chối']], array_map(
            fn (array $line) => [$line['mailable'], $line['message']],
            $this->logged($log, 'Mail failed'),
        ));
    }

    private function job(string $command, string $name): Job
    {
        $job = Mockery::mock(Job::class);
        $job->allows('payload')->andReturn(['data' => ['commandName' => $command]]);
        $job->allows('resolveName')->andReturn($name);

        return $job;
    }
}
