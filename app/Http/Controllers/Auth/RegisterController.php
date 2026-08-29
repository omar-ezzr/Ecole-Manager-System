<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role as SchoolRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', RulesPassword::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => SchoolRole::ROLE_STUDENT,
            ]);

            $studentRole = Role::where('name', SchoolRole::ROLE_STUDENT)
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->assignRole($studentRole);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user;
        });

        return response()->json([
            'access_token' => $user->createToken('client')->plainTextToken,
        ]);
    }
}
