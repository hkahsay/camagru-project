<?php

declare(strict_types=1);

final class Mailer
{
    public function sendEmailConfirmation(string $email, string $username, string $verificationUrl): void
    {
        $subject = 'Confirm your Camagru account';
        $message = "Hello {$username},\n\nConfirm your Camagru account using this link:\n{$verificationUrl}\n\nThis link expires in 24 hours.\n";

        if (getenv('MAIL_DRIVER') === 'mail') {
            mail($email, $subject, $message, 'From: no-reply@camagru.local');
            return;
        }

        $this->writeToLog($email, $subject, $message);
    }

    private function writeToLog(string $email, string $subject, string $message): void
    {
        if (!is_dir(STORAGE_PATH)) {
            mkdir(STORAGE_PATH, 0755, true);
        }

        $entry = sprintf(
            "[%s]\nTo: %s\nSubject: %s\n%s\n\n",
            gmdate('Y-m-d H:i:s'),
            $email,
            $subject,
            $message
        );

        file_put_contents(STORAGE_PATH . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
