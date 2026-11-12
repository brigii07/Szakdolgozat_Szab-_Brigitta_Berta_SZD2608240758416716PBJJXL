<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    public function showProfile(Request $request)
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $this->profileService->updateProfile($user, $request->validated());
        return redirect()->route('profile.show')->with('success', 'A profilod adatai frissítve.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->profileService->updatePassword($request->user(), $request->validated('current_password'), $request->validated('new_password'));
        return redirect()->route('profile.show')->with('success', 'A jelszavad megváltozott.');
    }
}
