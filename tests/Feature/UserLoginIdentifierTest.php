<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserLoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_a_lowercase_username_and_normalized_whatsapp_number(): void
    {
        $this->actingAs($this->userAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Kasir',
                'username' => 'Kasir_01',
                'email' => 'kasir@example.com',
                'password' => 'secret-pass',
                'whatsapp_number' => '081298765432',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'kasir@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('kasir_01', $user->username);
        $this->assertSame('6281298765432', $user->whatsapp_number);
        $this->assertNull($user->whatsapp_verified_at);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
    }

    public function test_admin_form_rejects_a_short_username(): void
    {
        $this->actingAs($this->userAdmin());

        $component = Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Kasir',
                'username' => 'ab',
                'email' => 'kasir@example.com',
                'password' => 'secret-pass',
            ])
            ->call('create');

        $this->assertSame(
            ['Username harus 3-50 karakter: huruf, angka, strip, atau garis bawah.'],
            $component->errors()->get('data.username'),
        );

        $this->assertNull(User::query()->where('email', 'kasir@example.com')->first());
    }

    private function userAdmin(): User
    {
        $role = Role::findOrCreate('super_admin', 'web');

        foreach (['ViewAny:User', 'Create:User'] as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }
}
