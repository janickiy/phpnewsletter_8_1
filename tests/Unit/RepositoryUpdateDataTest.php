<?php

namespace Tests\Unit;

use App\DTO\Update\CategoryUpdateData;
use App\DTO\Update\MacrosUpdateData;
use App\DTO\Update\ReadySentReadData;
use App\DTO\Update\ScheduleUpdateData;
use App\DTO\Update\SettingsUpdateData;
use App\DTO\Update\SmtpUpdateData;
use App\DTO\Update\SubscriberUpdateData;
use App\DTO\Update\TemplatesUpdateData;
use App\DTO\Update\UserUpdateData;
use App\Repositories\CategoryRepository;
use App\Repositories\MacrosRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\SmtpRepository;
use App\Repositories\SubscriberRepository;
use App\Repositories\TemplateRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RepositoryUpdateDataTest extends TestCase
{
    #[DataProvider('repositoryUpdateDataProvider')]
    public function test_repository_update_methods_require_entity_update_data(
        string $repository,
        string $methodName,
        int $dataParameterIndex,
        string $expectedDataClass,
    ): void {
        $method = new ReflectionMethod($repository, $methodName);
        $dataType = $method->getParameters()[$dataParameterIndex]->getType();

        $this->assertNotNull($dataType);
        $this->assertSame($expectedDataClass, $dataType->getName());
    }

    public static function repositoryUpdateDataProvider(): array
    {
        return [
            [CategoryRepository::class, 'update', 1, CategoryUpdateData::class],
            [MacrosRepository::class, 'update', 1, MacrosUpdateData::class],
            [ScheduleRepository::class, 'update', 1, ScheduleUpdateData::class],
            [SettingsRepository::class, 'setSettings', 0, SettingsUpdateData::class],
            [SmtpRepository::class, 'update', 1, SmtpUpdateData::class],
            [SubscriberRepository::class, 'update', 1, SubscriberUpdateData::class],
            [TemplateRepository::class, 'update', 1, TemplatesUpdateData::class],
            [UserRepository::class, 'update', 1, UserUpdateData::class],
            [ReadySentRepository::class, 'markAsRead', 0, ReadySentReadData::class],
        ];
    }

    public function test_update_data_is_converted_to_repository_attributes(): void
    {
        $this->assertSame(
            ['name' => 'News'],
            (new CategoryUpdateData('News'))->toArray(),
        );

        $this->assertSame(
            ['name' => 'first_name', 'value' => 'Alex', 'type' => 2],
            (new MacrosUpdateData('first_name', 'Alex', 2))->toArray(),
        );

        $this->assertSame(
            [
                'event_name' => 'Newsletter',
                'event_start' => '27.07.2026 10:00',
                'event_end' => '27.07.2026 11:00',
                'template_id' => 5,
            ],
            (new ScheduleUpdateData(
                'Newsletter',
                '27.07.2026 10:00',
                '27.07.2026 11:00',
                5,
                [1, 2],
            ))->toArray(),
        );

        $this->assertSame(
            [
                'host' => 'smtp.example.com',
                'username' => 'mailer',
                'email' => 'mail@example.com',
                'password' => null,
                'port' => 587,
                'authentication' => 'plain',
                'secure' => 'tls',
                'timeout' => 30,
            ],
            (new SmtpUpdateData(
                'smtp.example.com',
                'mailer',
                'mail@example.com',
                null,
                587,
                'plain',
                'tls',
                30,
            ))->toArray(),
        );

        $this->assertSame(
            ['email' => 'subscriber@example.com', 'name' => null],
            (new SubscriberUpdateData('subscriber@example.com', null))->toArray(),
        );

        $this->assertSame(
            ['APP_NAME' => 'Newsletter', 'LIMIT_SEND' => 1],
            (new SettingsUpdateData([
                'APP_NAME' => 'Newsletter',
                'LIMIT_SEND' => 1,
            ]))->toArray(),
        );

        $this->assertSame(
            ['name' => 'Template', 'body' => '<p>Body</p>', 'prior' => 1],
            (new TemplatesUpdateData('Template', '<p>Body</p>', 1))->toArray(),
        );

        $this->assertSame(
            [
                'name' => 'Admin',
                'login' => 'admin',
                'role' => 'admin',
                'description' => null,
                'password' => null,
            ],
            (new UserUpdateData('Admin', 'admin', 'admin'))->toArray(),
        );
    }
}
