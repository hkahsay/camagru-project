<?php

declare(strict_types=1);

final class Mailer
{
    public function sendEmailConfirmation(string $email, string $username, string $verificationUrl): void
    {
        $subject = 'Confirm your Camagru account';
        $message = "Hello {$username},\n\nConfirm your Camagru account using this link:\n{$verificationUrl}\n\nThis link expires in 24 hours.\n";

        if (getenv('MAIL_DRIVER') === 'smtp') {
            try {
                $this->sendSmtp($email, $subject, $message);
            } catch (RuntimeException $exception) {
                $this->writeToLog($email, $subject, $message . "\nSMTP error: " . $exception->getMessage());
            }

            return;
        }

        if (getenv('MAIL_DRIVER') === 'mail') {
            mail($email, $subject, $message, 'From: no-reply@camagru.local');
            return;
        }

        $this->writeToLog($email, $subject, $message);
    }

    private function sendSmtp(string $email, string $subject, string $message): void
    {
        $host = getenv('SMTP_HOST') ?: 'mailpit';
        $port = (int) (getenv('SMTP_PORT') ?: 1025);
        $from = getenv('MAIL_FROM') ?: 'no-reply@camagru.local';
        $username = getenv('SMTP_USERNAME') ?: '';
        $password = getenv('SMTP_PASSWORD') ?: '';
        $encryption = getenv('SMTP_ENCRYPTION') ?: '';
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 5);

        if ($socket === false) {
            $this->writeToLog($email, $subject, $message . "\nSMTP unavailable: {$errorCode} {$errorMessage}");
            return;
        }

        stream_set_timeout($socket, 5);
        $this->expect($socket, 220);

        if ($encryption === 'tls') {
            $this->command($socket, 'EHLO camagru.local', 250);
            $this->command($socket, 'STARTTLS', 220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not start encrypted SMTP connection.');
            }

            $this->command($socket, 'EHLO camagru.local', 250);
        } else {
            $this->command($socket, 'EHLO camagru.local', 250);
        }

        if ($username !== '' && $password !== '') {
            $this->command($socket, 'AUTH LOGIN', 334);
            $this->command($socket, base64_encode($username), 334);
            $this->command($socket, base64_encode($password), 235);
        }

        $this->command($socket, 'MAIL FROM:<' . $from . '>', 250);
        $this->command($socket, 'RCPT TO:<' . $email . '>', [250, 251]);
        $this->command($socket, 'DATA', 354);

        fwrite($socket, $this->formatMessage($from, $email, $subject, $message) . "\r\n.\r\n");
        $this->expect($socket, 250);
        $this->command($socket, 'QUIT', 221);
        fclose($socket);
    }

    private function formatMessage(string $from, string $to, string $subject, string $message): string
    {
        $safeMessage = preg_replace('/^\./m', '..', $message) ?? $message;

        return implode("\r\n", [
            'From: Camagru <' . $from . '>',
            'To: ' . $to,
            'Subject: ' . $subject,
            'Content-Type: text/plain; charset=UTF-8',
            '',
            str_replace("\n", "\r\n", $safeMessage),
        ]);
    }

    private function command($socket, string $command, int|array $expectedCode): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    private function expect($socket, int|array $expectedCode): void
    {
        $line = '';
        $lastLine = '';

        do {
            $line = fgets($socket);

            if ($line === false) {
                throw new RuntimeException('SMTP server did not respond.');
            }

            $lastLine = $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($lastLine, 0, 3);
        $expectedCodes = is_array($expectedCode) ? $expectedCode : [$expectedCode];

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($lastLine));
        }
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
