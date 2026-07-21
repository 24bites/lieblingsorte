<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function editor(): User
    {
        return User::create([
            'name' => 'Redaktion', 'email' => 'redaktion@lieblingsorte.test',
            'password' => Hash::make('editor123'), 'role' => 'editor',
        ]);
    }

    public function test_admin_can_create_a_new_admin_user(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Neue Redaktion',
            'email' => 'neu@lieblingsorte.test',
            'role' => 'editor',
            'password' => 'ein-sicheres-passwort',
            'password_confirmation' => 'ein-sicheres-passwort',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'neu@lieblingsorte.test', 'role' => 'editor']);
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->editor())->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_blank_password_on_update_keeps_existing_password(): void
    {
        $admin = $this->admin();
        $target = User::create([
            'name' => 'Zweiter Admin', 'email' => 'zweiter@lieblingsorte.test',
            'password' => Hash::make('original-password'), 'role' => 'editor',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Zweiter Admin (umbenannt)',
            'email' => 'zweiter@lieblingsorte.test',
            'role' => 'editor',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $target->refresh();
        $this->assertTrue(Hash::check('original-password', $target->password));
        $this->assertSame('Zweiter Admin (umbenannt)', $target->name);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_deleting_down_to_the_last_admin_is_protected(): void
    {
        $admin1 = $this->admin();
        $admin2 = User::create([
            'name' => 'Zweiter Admin', 'email' => 'zweiter@lieblingsorte.test',
            'password' => Hash::make('x'), 'role' => 'admin',
        ]);

        // With two admins present, deleting one other than yourself is fine.
        $this->actingAs($admin1)->delete(route('admin.users.destroy', $admin2));
        $this->assertSame(1, User::where('role', 'admin')->count());

        // Now only admin1 is left; deleting the sole remaining admin must be blocked.
        $this->actingAs($admin1)->delete(route('admin.users.destroy', $admin1));
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertDatabaseHas('users', ['id' => $admin1->id]);
    }

    public function test_new_admin_requires_password_confirmation_to_match(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Test',
            'email' => 'mismatch@lieblingsorte.test',
            'role' => 'editor',
            'password' => 'ein-sicheres-passwort',
            'password_confirmation' => 'etwas-anderes',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@lieblingsorte.test']);
    }
}
