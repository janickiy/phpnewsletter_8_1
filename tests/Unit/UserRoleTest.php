<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Http\Requests\Admin\Users\StoreRequest;
use App\Http\Requests\Admin\Users\UpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_existing_string_roles_keep_their_permission_checks(): void
    {
        foreach ([
            'admin' => [true, false, false],
            'project_admin' => [false, true, false],
            'moderator' => [false, false, true],
        ] as $role => $permissions) {
            $user = new User(['role' => $role]);

            $this->assertSame($role, $user->role);
            $this->assertSame($permissions, [$user->isAdmin(), $user->isProjectAdmin(), $user->isModerator()]);
            $this->assertSame(UserRole::from($role)->label(), $user->role_label);
            $this->assertSame(UserRole::from($role)->badgeClass(), $user->role_badge_class);
        }

        $this->assertSame('project_admin', User::ROLE_EDITOR);
    }

    public function test_user_creation_accepts_only_the_three_supported_roles(): void
    {
        $rules = (new StoreRequest())->rules()['role'];

        foreach (['admin', 'project_admin', 'moderator'] as $role) {
            $this->assertTrue(Validator::make(['role' => $role], ['role' => $rules])->passes());
        }

        foreach (['editor', 'organization_admin', 'unknown'] as $role) {
            $this->assertTrue(Validator::make(['role' => $role], ['role' => $rules])->fails());
        }
    }

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $user = new User(['role' => 'admin']);
        $user->id = 17;

        $request = UpdateRequest::create('/users/update', 'PUT', ['id' => 17]);
        $request->setUserResolver(fn () => $user);
        $rules = $request->rules()['role'];

        $this->assertTrue(Validator::make(['role' => 'admin'], ['role' => $rules])->passes());
        $this->assertTrue(Validator::make(['role' => 'project_admin'], ['role' => $rules])->fails());
        $this->assertTrue(Validator::make(['role' => 'moderator'], ['role' => $rules])->fails());
    }
}
