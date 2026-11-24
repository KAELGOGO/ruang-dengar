<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AgeTier;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    /** New users meet Dengi first; listeners go straight to their dashboard. */
    protected function redirectTo(): string
    {
        return auth()->user()->needsOnboarding() ? route('onboarding.show') : $this->redirectTo;
    }

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'birth_date' => [
                'required', 'date', 'after:1900-01-01',
                'before_or_equal:'.now()->subYears(AgeTier::MIN_USER_AGE)->toDateString(),
            ],
            // Admin accounts are only created by the seeder, never through this form.
            'register_as' => ['required', Rule::in([User::ROLE_USER, User::ROLE_LISTENER])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'birth_date.before_or_equal' => 'Minimal umur '.AgeTier::MIN_USER_AGE.' tahun.',
        ])->after(function ($validator) use ($data) {
            $lahir = strtotime($data['birth_date'] ?? '') ? Carbon::parse($data['birth_date']) : null;
            if (($data['register_as'] ?? null) === User::ROLE_LISTENER && $lahir
                && AgeTier::age($lahir) < AgeTier::MIN_LISTENER_AGE) {
                $validator->errors()->add('register_as', 'Listener minimal berumur '.AgeTier::MIN_LISTENER_AGE.' tahun.');
            }
        });
    }

    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'birth_date' => $data['birth_date'],
            ]);
            $user->role = $data['register_as'];
            $user->save();

            if ($user->isListener()) {
                $user->listenerProfile()->create(); // pending until an admin approves the documents
            }

            return $user;
        });
    }
}
