<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--clear : Clear the webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set or clear the Telegram Bot Webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clear = $this->option('clear');

        if ($clear) {
            $this->info('Clearing Telegram webhook...');
            $response = Telegram::removeWebhook();
            $this->info('Webhook removed successfully: ' . json_encode($response));
            return 0;
        }

        $webhookUrl = env('TELEGRAM_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            $this->error('TELEGRAM_WEBHOOK_URL is not set in your .env file.');
            return 1;
        }

        $this->info('Setting Telegram webhook to: ' . $webhookUrl);
        
        try {
            $response = Telegram::setWebhook(['url' => $webhookUrl]);
            $this->info('Webhook set successfully: ' . json_encode($response));
        } catch (\Exception $e) {
            $this->error('Failed to set webhook: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
