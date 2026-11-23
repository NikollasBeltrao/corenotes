<?php

namespace App\Services;

use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserService implements UserServiceInterface
{
    public function getAllUsers(): Collection
    {
        return User::where('id_user', Auth::id())->get();
    }

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function getUserById(int $id): ?User
    {
        return User::where('id_user', Auth::id())->find($id);
    }

    public function updateUser(int $id, array $data): User
    {
        $user = User::where('id_user', Auth::id())->findOrFail($id);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function deleteUser(int $id): bool
    {
        return User::where('id_user', Auth::id())->whereKey($id)->delete() > 0;
    }
}
