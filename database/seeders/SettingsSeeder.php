<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->settings() as $row) {
            Settings::query()->firstOrCreate(
                ['name' => $row['name']],
                ['value' => $row['value']]
            );
        }
    }

    /**
     * Build localized default settings for the selected installer locale.
     *
     * @return array<int, array{name: string, value: mixed}>
     */
    private function settings(): array
    {
        $localized = $this->localizedSettings();
        $locale = $this->locale();
        $languageSettings = $localized[$locale] ?? $localized['en'];

        return array_merge($this->baseSettings(), [
            ['name' => 'SUBJECT_TEXT_CONFIRM', 'value' => $languageSettings['subject']],
            ['name' => 'TEXT_CONFIRMATION', 'value' => $languageSettings['confirmation']],
            ['name' => 'UNSUBLINK', 'value' => $languageSettings['unsubscribe']],
        ]);
    }

    /**
     * Settings that do not depend on the interface language.
     *
     * @return array<int, array{name: string, value: mixed}>
     */
    private function baseSettings(): array
    {
        return [
            ['name' => 'EMAIL', 'value' => 'vasya-pupkin@my-domain.com'],
            ['name' => 'FROM', 'value' => 'my-domain.com'],
            ['name' => 'RETURN_PATH', 'value' => ''],
            ['name' => 'LIST_OWNER', 'value' => ''],
            ['name' => 'ORGANIZATION', 'value' => ''],
            ['name' => 'REQUIRE_SUB_CONFIRMATION', 'value' => 1],
            ['name' => 'SHOW_UNSUBSCRIBE_LINK', 'value' => '1'],
            ['name' => 'REQUEST_REPLY', 'value' => '0'],
            ['name' => 'NEW_SUBSCRIBER_NOTIFY', 'value' => '0'],
            ['name' => 'SLEEP', 'value' => '0'],
            ['name' => 'LIMIT_NUMBER', 'value' => '300'],
            ['name' => 'LIMIT_SEND', 'value' => '0'],
            ['name' => 'DAYS_FOR_REMOVE_SUBSCRIBER', 'value' => '7'],
            ['name' => 'REMOVE_SUBSCRIBER', 'value' => '0'],
            ['name' => 'PRECEDENCE', 'value' => 'bulk'],
            ['name' => 'CONTENT_TYPE', 'value' => 'html'],
            ['name' => 'HOW_TO_SEND', 'value' => 'php'],
            ['name' => 'SENDMAIL_PATH', 'value' => '/usr/sbin/sendmail'],
            ['name' => 'URL', 'value' => ''],
            ['name' => 'INTERVAL_TYPE', 'value' => 'no'],
            ['name' => 'INTERVAL_NUMBER', 'value' => '1'],
        ];
    }

    /**
     * Localized email text used by default subscription confirmation settings.
     *
     * @return array<string, array{subject: string, confirmation: string, unsubscribe: string}>
     */
    private function localizedSettings(): array
    {
        return [
            'en' => [
                'subject' => 'Newsletter subscription',
                'confirmation' => "Hello, %NAME%\r\n\r\nReceiving a newsletter is possible after the completion of activation. To activate your subscription, click on the following link: %CONFIRM%\r\n\r\nIf you have not subscribed to this email, just ignore this email or follow the link: %UNSUB%\r\n\r\nSincerely, \r\nteam %SERVER_NAME%",
                'unsubscribe' => 'Unsubscribe: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'ru' => [
                'subject' => 'Подписка на рассылку',
                'confirmation' => "Здравствуйте, %NAME%\r\n\r\nПолучение рассылки возможно после завершения этапа активации подписки. Чтобы активировать подписку, перейдите по следующей ссылке: %CONFIRM%\r\n\r\nЕсли Вы не производили подписку на данный email, просто проигнорируйте это письмо или перейдите по ссылке: %UNSUB%\r\n\r\nС уважением, \r\nадминистратор сайта %SERVER_NAME%",
                'unsubscribe' => 'Отписаться от рассылки: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'es' => [
                'subject' => 'Suscripción al boletín',
                'confirmation' => "Hola, %NAME%\r\n\r\nPara recibir el boletín, primero debe completar la activación. Para activar su suscripción, haga clic en el siguiente enlace: %CONFIRM%\r\n\r\nSi no se suscribió con este email, ignore este mensaje o siga este enlace: %UNSUB%\r\n\r\nAtentamente, \r\nequipo de %SERVER_NAME%",
                'unsubscribe' => 'Darse de baja: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'fr' => [
                'subject' => 'Abonnement à la newsletter',
                'confirmation' => "Bonjour, %NAME%\r\n\r\nLa réception de la newsletter est possible après l'activation. Pour activer votre abonnement, cliquez sur le lien suivant : %CONFIRM%\r\n\r\nSi vous ne vous êtes pas abonné avec cet email, ignorez simplement ce message ou suivez ce lien : %UNSUB%\r\n\r\nCordialement, \r\nl'équipe %SERVER_NAME%",
                'unsubscribe' => 'Se désabonner : <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'de' => [
                'subject' => 'Newsletter-Abonnement',
                'confirmation' => "Hallo, %NAME%\r\n\r\nDer Empfang des Newsletters ist erst nach der Aktivierung möglich. Um Ihr Abonnement zu aktivieren, klicken Sie auf den folgenden Link: %CONFIRM%\r\n\r\nWenn Sie sich nicht mit dieser E-Mail-Adresse angemeldet haben, ignorieren Sie diese Nachricht bitte oder folgen Sie diesem Link: %UNSUB%\r\n\r\nMit freundlichen Grüßen, \r\nTeam %SERVER_NAME%",
                'unsubscribe' => 'Abmelden: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'zh-cn' => [
                'subject' => '订阅邮件列表',
                'confirmation' => "您好，%NAME%\r\n\r\n完成激活后才可以接收邮件列表。要激活您的订阅，请点击以下链接：%CONFIRM%\r\n\r\n如果您没有使用此邮箱订阅，请忽略此邮件，或点击以下链接：%UNSUB%\r\n\r\n此致，\r\n%SERVER_NAME% 团队",
                'unsubscribe' => '退订：<a href=%UNSUB%>%UNSUB%</a>',
            ],
            'pt' => [
                'subject' => 'Assinatura da newsletter',
                'confirmation' => "Olá, %NAME%\r\n\r\nO recebimento da newsletter só será possível após a ativação. Para ativar sua assinatura, clique no seguinte link: %CONFIRM%\r\n\r\nSe você não se inscreveu com este email, ignore esta mensagem ou siga este link: %UNSUB%\r\n\r\nAtenciosamente, \r\nequipe %SERVER_NAME%",
                'unsubscribe' => 'Cancelar inscrição: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'ar' => [
                'subject' => 'الاشتراك في النشرة البريدية',
                'confirmation' => "مرحبًا، %NAME%\r\n\r\nلا يمكن استلام النشرة البريدية إلا بعد إكمال التفعيل. لتفعيل اشتراكك، اضغط على الرابط التالي: %CONFIRM%\r\n\r\nإذا لم تقم بالاشتراك بهذا البريد الإلكتروني، فتجاهل هذه الرسالة أو اتبع الرابط التالي: %UNSUB%\r\n\r\nمع التحية، \r\nفريق %SERVER_NAME%",
                'unsubscribe' => 'إلغاء الاشتراك: <a href=%UNSUB%>%UNSUB%</a>',
            ],
            'hi' => [
                'subject' => 'न्यूज़लेटर सदस्यता',
                'confirmation' => "नमस्ते, %NAME%\r\n\r\nन्यूज़लेटर प्राप्त करने के लिए पहले सक्रियण पूरा करना आवश्यक है। अपनी सदस्यता सक्रिय करने के लिए इस लिंक पर क्लिक करें: %CONFIRM%\r\n\r\nयदि आपने इस ईमेल से सदस्यता नहीं ली है, तो इस संदेश को अनदेखा करें या इस लिंक पर जाएँ: %UNSUB%\r\n\r\nसादर, \r\n%SERVER_NAME% टीम",
                'unsubscribe' => 'सदस्यता समाप्त करें: <a href=%UNSUB%>%UNSUB%</a>',
            ],
        ];
    }

    /**
     * Resolve the locale used for installer seed data.
     */
    private function locale(): string
    {
        return in_array(config('app.locale'), config('app.locales', []), true)
            ? config('app.locale')
            : config('app.fallback_locale', 'en');
    }
}
