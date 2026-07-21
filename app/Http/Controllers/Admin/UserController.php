<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['editUser' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', "Benutzer \"{$data['name']}\" wurde angelegt.");
    }

    public function edit(User $editUser)
    {
        return view('admin.users.form', compact('editUser'));
    }

    public function update(Request $request, User $editUser)
    {
        $data = $request->validate($this->rules($request, $editUser));

        $editUser->name = $data['name'];
        $editUser->email = $data['email'];
        $editUser->role = $data['role'];

        if (filled($data['password'] ?? null)) {
            $editUser->password = Hash::make($data['password']);
        }

        $editUser->save();

        return redirect()->route('admin.users.index')->with('status', "Benutzer \"{$editUser->name}\" wurde aktualisiert.");
    }

    public function destroy(Request $request, User $editUser)
    {
        if ($editUser->id === $request->user()->id) {
            return back()->with('status', 'Du kannst deinen eigenen Account nicht löschen.');
        }

        if ($editUser->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('status', 'Der letzte verbleibende Administrator kann nicht gelöscht werden.');
        }

        $name = $editUser->name;
        $editUser->delete();

        return redirect()->route('admin.users.index')->with('status', "Benutzer \"{$name}\" wurde gelöscht.");
    }

    private function rules(Request $request, ?User $editUser = null): array
    {
        $isEdit = $editUser?->exists ?? false;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editUser?->id)],
            'role' => ['required', Rule::in(['admin', 'editor'])],
            'password' => [$isEdit ? 'nullable' : 'required', 'confirmed', Password::min(10)],
        ];
    }
}
