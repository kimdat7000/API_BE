<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class UserController
{
    public function index()
    {
        return User::latest()->get();
    }

    public function store(Request $request)
    {
        return User::create([
            'name' => $request->name,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'password' => md5($request->password),
            'is_admin' => $request->is_admin ?? 0,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->except('password');

        if ($request->filled('password')) {
            $data['password'] = md5($request->password);
        }

        $user->update($data);

        return $user;
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
