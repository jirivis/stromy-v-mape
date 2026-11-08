<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__
    . '/../lib/PHPMailer/src/Exception.php';

require_once __DIR__
    . '/../lib/PHPMailer/src/PHPMailer.php';

require_once __DIR__
    . '/../lib/PHPMailer/src/SMTP.php';


function sendAppMail(
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody = ''
): bool {

    require_once dirname(__DIR__).'/bootstrap.php';
    $config=svmConfig()['mail'];

    $mail =
        new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host =
            $config['host'];

        $mail->SMTPAuth =
            true;

        $mail->AuthType =
            'LOGIN';

        $mail->Username =
            $config['username'];

        $mail->Password =
            $config['password'];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            (int) $config['port'];

        $mail->CharSet =
            'UTF-8';

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress($to);

        $mail->isHTML(true);

        $mail->Subject =
            $subject;

        $mail->Body =
            $htmlBody;

        $mail->AltBody =
            $textBody !== ''
                ? $textBody
                : strip_tags($htmlBody);

        $mail->send();

        return true;

    } catch (Exception $exception) {

        error_log(
            'PHPMailer: '
            . $mail->ErrorInfo
            . ' | '
            . $exception->getMessage()
        );

        return false;
    }
}