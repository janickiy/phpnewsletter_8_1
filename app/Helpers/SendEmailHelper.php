<?php

namespace App\Helpers;

use PHPMailer\PHPMailer;
use App\Models\{Attach, Smtp, CustomHeaders, Templates};
use Illuminate\Support\Facades\Storage;
use URL;

class SendEmailHelper
{
    public string $subject;

    public string $body;

    public string $email;

    public int $prior = 0;

    public ?string $name = 'USERNAME';

    public int $templateId = 0;

    public int $subscriberId = 0;

    public string $token = '';

    public bool $tracking = true;

    public bool $unsub = true;


    /**
     * Compose and deliver one personalized message using the configured transport and template options.
     *
     * @param int|null $attach
     * @return array
     * @throws PHPMailer\Exception
     */
    public function sendEmail(?int $attach = null)
    {
        $subject = $this->subject;
        $body = $this->body;
        $email = $this->email;
        $prior = $this->prior;
        $name = (string) ($this->name ?? '');
        $templateId = $this->templateId;
        $subscriberId = $this->subscriberId;
        $token = $this->token;

        $m = $this->createMailer();

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'smtp') {
            $m->IsSMTP();
            $m->SMTPKeepAlive = true;

            $smtp = Smtp::query()
                ->where('active', 1)
                ->inRandomOrder()
                ->first();

            if ($smtp) {
                $m->Host = $smtp->host;
                $m->Port = $smtp->port;
                $m->From = $smtp->email;
                $m->Username = $smtp->username;
                $m->Password = $smtp->password;
                $m->SMTPAuth = !empty($smtp->password);

                if ($smtp->secure === 'ssl') {
                    $m->SMTPSecure = 'ssl';
                } elseif ($smtp->secure === 'tls') {
                    $m->SMTPSecure = 'tls';
                }

                if ($smtp->authentication === 'plain') {
                    $m->AuthType = 'PLAIN';
                } elseif ($smtp->authentication === 'cram-md5') {
                    $m->AuthType = 'CRAM-MD5';
                }

                $m->Timeout = $smtp->timeout;
            } else {
                return [
                    'result' => false,
                    'error' => __('message.unable_connect_to_smtp'),
                ];
            }
        } elseif (
            SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'sendmail'
            && SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH') !== ''
        ) {
            $m->IsSendmail();
            $m->Sendmail = SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH');
        } else {
            $m->IsMail();
        }

        $m->CharSet = PHPMailer\PHPMailer::CHARSET_UTF8;

        if ($prior == 1) {
            $m->Priority = 1;
        } elseif ($prior == 2) {
            $m->Priority = 5;
        } else {
            $m->Priority = 3;
        }

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') !== 'smtp') {
            $m->From = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        }

        $m->FromName = SettingsHelper::getInstance()->getValueForKey('FROM');

        if (SettingsHelper::getInstance()->getValueForKey('LIST_OWNER') !== '') {
            $m->addCustomHeader("List-Owner: <" . SettingsHelper::getInstance()->getValueForKey('LIST_OWNER') . ">");
        }

        if (SettingsHelper::getInstance()->getValueForKey('RETURN_PATH') !== '') {
            $m->addCustomHeader("Return-Path: <" . SettingsHelper::getInstance()->getValueForKey('RETURN_PATH') . ">");
        }

        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') === 'html') {
            $m->isHTML();
        } else {
            $m->isHTML(false);
        }

        $subject = str_replace('%NAME%', $name, $subject);

        $m->Subject = $subject;

        if (SettingsHelper::getInstance()->getValueForKey('ORGANIZATION') !== '') {
            $m->addCustomHeader("Organization: " . SettingsHelper::getInstance()->getValueForKey('ORGANIZATION'));
        }

        $m->AddAddress($email);

        if (
            (int) SettingsHelper::getInstance()->getValueForKey('REQUEST_REPLY') === 1
            && SettingsHelper::getInstance()->getValueForKey('EMAIL') !== ''
        ) {
            $m->addCustomHeader("Disposition-Notification-To: " . SettingsHelper::getInstance()->getValueForKey('EMAIL'));
            $m->ConfirmReadingTo = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        }

        if (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'bulk') {
            $m->addCustomHeader("Precedence: bulk");
        } elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'junk') {
            $m->addCustomHeader("Precedence: junk");
        } elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'list') {
            $m->addCustomHeader("Precedence: list");
        }

        $unsubscribeUrl = '';

        if ($this->unsub) {
            $unsubscribeUrl = URL::route('frontend.unsubscribe', [
                'subscriber' => $subscriberId,
                'token' => $token,
            ]);
            $unsublink = str_replace(
                '%UNSUB%',
                $unsubscribeUrl,
                SettingsHelper::getInstance()->getValueForKey('UNSUBLINK')
            );

            if (
                (int) SettingsHelper::getInstance()->getValueForKey('SHOW_UNSUBSCRIBE_LINK') === 1
                && SettingsHelper::getInstance()->getValueForKey('UNSUBLINK') !== ''
            ) {
                $body .= "<br><br>" . $unsublink;
            }

            $m->addCustomHeader("List-Unsubscribe: " . $unsubscribeUrl);
        }

        foreach (CustomHeaders::get() ?? [] as $customheader) {
            $m->addCustomHeader($customheader->name . ": " . $customheader->value);
        }

        $msg = $body;
        $url_info = parse_url(SettingsHelper::getInstance()->getValueForKey('URL'));

        $referralProjectId = preg_match('/%REFERRAL:/i', $msg) === 1 && $templateId > 0
            ? Templates::query()->whereKey($templateId)->value('project_id')
            : null;

        $msg = preg_replace_callback("/%REFERRAL\:(.+)%/isU", function ($matches) use ($subscriberId, $referralProjectId) {
            $parameters = [
                'ref' => rtrim(strtr(base64_encode($matches[1]), '+/', '-_'), '='),
                'subscriber' => $subscriberId,
            ];

            if ($referralProjectId !== null) {
                $parameters['project_id'] = $referralProjectId;
            }

            return URL::route('frontend.referral', $parameters);
        }, $msg);

        $msg = str_replace('%NAME%', $name, $msg);
        $msg = str_replace('%UNSUB%', $unsubscribeUrl, $msg);
        $msg = str_replace('%SERVER_NAME%', $url_info['host'], $msg);
        $msg = str_replace('%USERID%', $subscriberId, $msg);
        $msg = str_replace('%URL_PATH%', URL::to('/'), $msg);
        $msg = StringHelper::macrosReplacement($msg);

        if ($attach) {
            foreach (Attach::where('template_id', $attach)->get() ?? [] as $f) {
                $path = Attach::DIRECTORY . '/' . $f->file_name;

                if (Storage::disk('local')->exists($path)) {
                    $storagePath = Storage::disk('local')->path($path);

                    $ext = pathinfo($f->file_name, PATHINFO_EXTENSION);
                    $mime_type = StringHelper::getMimeType($ext);
                    $m->AddAttachment($storagePath, $f->name, 'base64', $mime_type);
                }
            }
        }

        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') === 'html') {
            if ($this->tracking) {
                $imageUrl = URL::route('frontend.pic', ['subscriber' => $subscriberId, 'template' => $templateId]);
                $IMG = '<img alt="" border="0" src="' . $imageUrl . '" width="1" height="1">';
                $msg .= $IMG;
            }
        } else {
            $msg = preg_replace('/<br(\s\/)?>/i', "\n", $msg);
            $msg = StringHelper::removeHtmlTags($msg);
        }

        $m->Body = $msg;

        if (!$m->Send()) {
            $result = ['result' => false, 'error' => $m->ErrorInfo];
        } else {
            $result = ['result' => true, 'error' => null];
        }

        $m->ClearCustomHeaders();
        $m->ClearAllRecipients();
        $m->ClearAttachments();

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'smtp') {
            $m->SmtpClose();
        }

        return $result;
    }

    /**
     * Create a mailer without exposing the framework and version in X-Mailer.
     */
    protected function createMailer(): PHPMailer\PHPMailer
    {
        $mailer = new PHPMailer\PHPMailer();
        $mailer->XMailer = null;

        return $mailer;
    }

    /**
     * Attempt an SMTP connection with the supplied credentials and security settings.
     *
     * @param string $host
     * @param string $email
     * @param string $username
     * @param string|null $password
     * @param int $port
     * @param string $authentication
     * @param string $secure
     * @param int $timeout
     * @return bool
     * @throws PHPMailer\Exception
     */
    public static function checkConnection(
        string $host,
        string $email,
        string $username,
        ?string $password,
        int $port,
        string $authentication,
        string $secure,
        int $timeout = 5
    ): bool {
        $m = new PHPMailer\PHPMailer();
        $m->isSMTP();
        $m->Host = $host;
        $m->Port = $port;

        if ($password) {
            $m->SMTPAuth = true;
        } else {
            $m->SMTPAuth = false;
        }

        $m->SMTPKeepAlive = true;

        if ($secure === 'ssl') {
            $m->SMTPSecure = 'ssl';
        } elseif ($secure === 'tls') {
            $m->SMTPSecure = 'tls';
        }

        $m->AuthType = match ($authentication) {
            'plain' => 'PLAIN',
            'cram-md5', 'crammd5' => 'CRAM-MD5',
            'login' => 'LOGIN',
            default => '',
        };
        $m->Username = $username;
        $m->Password = $password;
        $m->Timeout = $timeout;
        $m->From = $email;
        $m->FromName = $email;

        if ($m->smtpConnect()) {
            $m->smtpClose();
            return true;
        } else {
            return false;
        }
    }
}
