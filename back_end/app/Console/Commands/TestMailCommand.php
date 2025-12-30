<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackMail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Test the SMTP configuration by sending a test email';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Tentative d'envoi d'un email de test à : $email");

        $data = [
            'name' => 'Test System',
            'rating' => 5,
            'comment' => 'Ceci est un test de diagnostic SMTP.'
        ];

        try {
            Mail::to($email)->send(new FeedbackMail($data));
            $this->info("Succès ! L'email a été envoyé (en théorie). Vérifiez votre boîte de réception.");
        } catch (\Exception $e) {
            $this->error("Échec de l'envoi !");
            $this->error("Erreur : " . $e->getMessage());
            
            if (str_contains($e->getMessage(), '535')) {
                $this->warn("Diagnostic : Erreur d'authentification (535).");
                $this->line("- Vérifiez que votre MAIL_PASSWORD est correct.");
                $this->line("- Vérifiez que l'expéditeur (MAIL_FROM_ADDRESS) est validé dans Brevo.");
            }
        }
    }
}
