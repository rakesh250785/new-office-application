<?php

namespace App\Jobs;

use App\Mail\LoginLogoutNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Log;

class SendLoginLogoutEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $details;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function handle()
    {
        try {
            Mail::to(config('mail.to.address'))
                ->send(new LoginLogoutNotification($this->details));
        } catch (Exception $ex) {
            Log::error("SendLoginLogoutEmail failed: {$ex->getMessage()} - details: ".json_encode($this->details));
            throw $ex;
        }
    }

    public function failed(Exception $exception)
    {
        Log::error("SendLoginLogoutEmail::failed - {$exception->getMessage()} - details: ".json_encode($this->details));
    }
}
