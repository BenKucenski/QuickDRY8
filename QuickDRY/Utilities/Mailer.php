<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use Exception;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Mailer
 */
class Mailer extends strongType
{
    public ?string $message = null;
    public ?string $subject = null;
    public ?string $to_email = null;
    public ?string $to_name = null;
    /**
     * Comma-separated list of additional recipients to Cc on the SAME message as to_email.
     * When set (even to an empty string), Send() sends ONE message with to_email in the To:
     * field and these addresses in the Cc: field, instead of the default behavior of sending
     * a separate individual message per address found in to_email.
     */
    public ?string $cc_emails = null;
    public ?bool $is_sent = null;
    public ?string $sent_at = null;
    public ?string $log = null;
    public ?string $headers = null;
    public ?string $from_email = null;
    public ?string $from_name = null;
    public ?array $embedded_images = null;

    public ?PHPMailer $mail = null;

    /**
     *
     * @param string $to_email
     * @param string $to_name
     * @param string $subject
     * @param string $message
     * @param array|null $attachments
     * @param string|null $from_email
     * @param string|null $from_name
     * @param array|null $embedded_images
     * @return Mailer
     */
    public static function Queue(
        string  $to_email,
        string  $to_name,
        string  $subject,
        string  $message,
        ?array  $attachments = null,
        ?string $from_email = null,
        ?string $from_name = null,
        ?array  $embedded_images = null
    ): Mailer
    {
        $t = new self();
        $t->to_email = $to_email;
        $t->to_name = $to_name;
        $t->from_email = $from_email;
        $t->from_name = $from_name;
        $t->subject = $subject;
        $t->message = $message;
        $t->log = null;
        $t->headers = json_encode($attachments);
        $t->embedded_images = $embedded_images;

        return $t;
    }

    /**
     * Opt-in variant of Queue() for sending ONE message to multiple recipients
     * (to_email in the To: field, cc_emails in the Cc: field) instead of the
     * default behavior, which sends a separate individual message per address.
     *
     * @param string $to_email Primary recipient (To:)
     * @param string $to_name
     * @param string|null $cc_emails Comma-separated list of additional recipients (Cc:). Pass '' for none.
     * @param string $subject
     * @param string $message
     * @param array|null $attachments
     * @param string|null $from_email
     * @param string|null $from_name
     * @param array|null $embedded_images
     * @return Mailer
     */
    public static function QueueWithCC(
        string  $to_email,
        string  $to_name,
        ?string $cc_emails,
        string  $subject,
        string  $message,
        ?array  $attachments = null,
        ?string $from_email = null,
        ?string $from_name = null,
        ?array  $embedded_images = null
    ): Mailer
    {
        $t = self::Queue($to_email, $to_name, $subject, $message, $attachments, $from_email, $from_name, $embedded_images);
        $t->cc_emails = $cc_emails ?? '';

        return $t;
    }

    /**
     * @param bool $debug
     * @param bool $smtp_output
     * @return int
     */
    public function Send(bool $debug = false, bool $smtp_output = false): int
    {

        if (defined('SMTP_ON')) {
            if (SMTP_ON == 0) {
                return -1;
            }
        }

        if (!defined('SMTP_FROM_EMAIL') || !defined('SMTP_FROM_NAME')) {
            exit('SMTP_FROM_EMAIL or SMTP_FROM_NAME not defined');
        }

        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            if (defined('SMTP_DEBUG_EMAIL')) {
                $this->to_email = SMTP_DEBUG_EMAIL;
                $this->cc_emails = null; // never leak real Cc recipients in debug mode
                $this->subject = 'TEST EMAIL: ' . $this->subject;
            } else {
                return -2;
            }
        }

        $to_emails = Strings::SplitEmails($this->to_email);

        // Opt-in single-message mode: one send, all "to" addresses in To:, cc_emails in Cc:
        if ($this->cc_emails !== null) {
            $mail = $this->buildMailer($smtp_output);

            foreach ($to_emails as $to) {
                if (!$to) {
                    continue;
                }
                try {
                    $mail->addAddress($to, $to);
                } catch (Exception $e) {
                    Exception('Mailer Add Address', $e->getMessage());
                }
            }

            foreach (Strings::SplitEmails($this->cc_emails) as $cc) {
                if (!$cc) {
                    continue;
                }
                try {
                    $mail->addCC($cc, $cc);
                } catch (Exception $e) {
                    Exception('Mailer Add CC', $e->getMessage());
                }
            }

            if (!$this->attachAndSend($mail, $debug)) {
                return 0;
            }

            $this->is_sent = true;
            $this->sent_at = Dates::Timestamp(time());

            return 1;
        }

        // Default (legacy) mode: one separate message per address in to_email
        foreach ($to_emails as $to) {

            $mail = $this->buildMailer($smtp_output);

            try {
                $mail->addAddress($to, $to);
            } catch (Exception $e) {
                Exception('Mailer Add Address', $e->getMessage());
            }

            if (!$this->attachAndSend($mail, $debug)) {
                return 0;
            }
        }
        $this->is_sent = true;
        $this->sent_at = Dates::Timestamp(time());

        return 1;
    }

    /**
     * Builds and configures a fresh PHPMailer instance shared by both send modes.
     *
     * @param bool $smtp_output
     * @return PHPMailer
     */
    private function buildMailer(bool $smtp_output): PHPMailer
    {
        $mail = new PHPMailer();

        if (!defined('SMTP_HOST')) {
            exit('SMTP_HOST undefined');
        }
        $mail->Host = SMTP_HOST;
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 25;

        try {
            $mail->setFrom($this->from_email ?? SMTP_FROM_EMAIL, $this->from_name ?? SMTP_FROM_NAME);
        } catch (Exception $e) {
            exit('Mailer 1');
        }
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        $this->from_email = $mail->From;
        $this->from_name = $mail->FromName;
        $mail->SMTPDebug = $smtp_output;

        if (defined('SMTP_USER') && defined('SMTP_PASS')) {
            if (SMTP_USER && SMTP_PASS) {
                if (!defined('SMTP_AUTH')) {
                    exit('SMTP_AUTH undefined');
                }
                $mail->Password = SMTP_PASS;
                $mail->Username = SMTP_USER;
                $mail->AuthType = SMTP_AUTH;
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
            }
        }
        $mail->Mailer = 'smtp';

        return $mail;
    }

    /**
     * Attaches subject/body/embedded images/attachments to a configured PHPMailer
     * instance and sends it. Shared by both send modes in Send().
     *
     * @param PHPMailer $mail
     * @param bool $debug
     * @return bool
     */
    private function attachAndSend(PHPMailer $mail, bool $debug): bool
    {
        $mail->Subject = $this->subject;
        try {
            $mail->msgHTML($this->message);
        } catch (Exception $e) {
            Exception('Mailer MsgHTML', $e->getMessage());
        }

        if (is_array($this->embedded_images)) {
            foreach ($this->embedded_images as $id => $file) {
                try {
                    $mail->addEmbeddedImage($file, $id, basename($file));
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    Exception($e->getMessage());
                }
            }
        }

        if ($this->headers) {
            $attachments = json_decode($this->headers, true);
            if (json_last_error()) {
                $attachments = unserialize($this->headers);
            }

            if (is_array($attachments)) {
                foreach ($attachments as $name => $path) {

                    if ($name === 'report_id') {
                        // don't handle this here, we need to update the email queue record
                        return false;
                    } elseif (is_object($path)) {
                        if (get_class($path) == 'EmailAttachment') {
                            $name = $path->FileName;
                            $path = $path->FileLocation;
                        } else {
                            return false;
                        }
                    }

                    if (file_exists($path)) {
                        try {
                            $mail->addAttachment($path, $name);
                        } catch (Exception $ex) {
                            $this->log = $ex->getMessage();
                            return false;
                        }
                    } else {
                        try {
                            $mail->addStringAttachment(base64_decode($path), $name);
                        } catch (Exception $ex) {
                            $this->log = $ex->getMessage();
                            return false;
                        }
                    }
                }
            }
        }

        try {
            if (!$mail->send()) {
                if ($debug) {
                    Testing([$mail->ErrorInfo, $mail]);
                }
                $this->log = $mail->ErrorInfo;
                $this->mail = $mail;
                return false;
            }
        } catch (Exception $e) {
            $this->log = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param string $filename
     * @param array $values
     * @return string
     */
    public static function Template(string $filename, array $values): string
    {
        if (!file_exists($filename)) {
            Exception(['error' => 'File does not exist', $filename]);
        }
        $html = file_get_contents($filename);
        foreach ($values as $key => $value) {
            $html = str_ireplace('##' . $key . '##', $value, $html);
        }
        $matches = [];
        preg_match_all('/##(.*?)##/si', $html, $matches);
        if (sizeof($matches[1])) {
            Exception(['Error' => 'HTML still contains variables', $matches[1], $html]);
        }
        return $html;
    }
}