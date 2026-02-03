<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    public function handle()
    {
        $email = $this->argument('email');
        \Mail::raw('SMTP is working!', function($msg) use ($email) {
            $msg->to($email)->subject('Artisan Test Mail');
        });
        $this->info("Test email sent to {$email}!");
    }
}
