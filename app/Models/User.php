<?php
namespace Jolt\Models;

class User extends Model
{
    /////////////////////////
    // Validation
    /////////////////////////
    protected $rules = [
        'username'      => 'required|alpha_dash|min:3|max:16|unique:users',
        'email'         => 'required|email',
        'password'      => 'required|min:5|max:255', // Not hashed when validated if password was updated.
        'is_superadmin' => 'boolean'
    ];

    /////////////////////////
    // Login
    /////////////////////////
    public static function IsLoggedIn()
    {
        return self::where('id', '=', \request()->session()->get('me'))->first() !== null;
    }

    /**
     * Gets the currently logged in user, or throws an authentication exception.
     */
    public static function Me()
    {
        $me = self::where('id', '=', \request()->session()->get('me'))->first();
        if ($me) return $me;
        else \abort(403);
    }

    /**
     * Logs the user in for the current session.
     */
    public function Login()
    {
        \request()->session()->put('me', $this->id);
    }

    /**
     * Logs the user out for the current session.
     */
    public function Logout()
    {
        \request()->session()->forget('me');
    }

    /////////////////////////
    // Authentication
    /////////////////////////
    
    /**
     * Checks whether the given password matches the current user's password. This should only be used for re-checking
     * a password, never for login (because the time to check the passwords differs substantially depending on whether
     * the user exists or not). Use self::VerifyLogin for logins.
     *
     * @param   string  $password   The password to check.
     * @return  bool                true if the password matches, otherwise false.
     */
    public function VerifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * Updates the user's password, using the most recent secure practices (bcrypt at the time of writing). Doesn't
     * save the user, so you'll still need to call ->save() as usual.
     * 
     * @param   string  $newPassword    The new password.
     * @return  void
     */
    public function UpdatePassword($newPassword)
    {
        $this->password = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Validates whether the given credentials are correct (trying to take roughly the same amount of time regardless
     * of whether a user exists or not.)
     * 
     * @param   string  $username   The username to check.
     * @param   string  $password   The password associated with the username.
     * @return  bool                true if the username and password match, otherwise false.
     */
    public static function VerifyLogin($username, $password)
    {
        $user = self::where('username', '=', $username)->first();
        $checkPw = $user ? $user->password : '$2y$12$.'.rand(0,PHP_INT_MAX); // Equalize the time spent if no user
        return password_verify($password, $checkPw);
    }

    /////////////////////////
    // Laravel
    /////////////////////////

    /**
     * In general: configures static properties which would otherwise need to be set outside of the class.
     * This instance: automatically hash the password before sending it to the database, and automatically sets the
     * superadmin flag on the first user to register.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->UpdatePassword($model->password);
            if (self::count() == 0) {
                $model->is_superadmin = true;
            }
        });
    }
}