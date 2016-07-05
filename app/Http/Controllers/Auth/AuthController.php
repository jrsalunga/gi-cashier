<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedFailed;
use Socialite;

class AuthController extends Controller
{
    protected $redirectPath = '/dashboard';
    protected $loginPath = '/login';
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    public function loginUsername() {
        return property_exists($this, 'username') ? $this->username : 'email';
    }



    public function postLogin(Request $request) {
        
        // $request->input('email') is from the form
        $login_type = filter_var($request->input('email'), FILTER_VALIDATE_EMAIL ) ? 'email' : 'username';

        $request->merge([ $login_type => $request->input('email'), 'admin'=>'5']); // added 5 for cashier


        if ($login_type == 'email') {
            $this->validate($request, [
                'email'    => 'required|email', // the validation can be separate
                'password' => 'required',
            ]);
        } else {
            $this->validate($request, [
                'username' => 'required',
                'password' => 'required',
            ]);
        }

        /*
        $this->validate($request, [
            $this->loginUsername() => 'required', 'password' => 'required',
        ]);
        */
        

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        //$throttles = $this->isUsingThrottlesLoginsTrait();
        $throttles = false;

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only($login_type, 'password', 'admin'); // added the admin for filter
        //$credentials = $this->getCredentials($request);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            if (app()->environment()==='production')
              event(new UserLoggedIn($request));
            
            return $this->handleUserWasAuthenticated($request, $throttles);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        if (app()->environment()==='production')
          event(new UserLoggedFailed($request));
        //return $this->loginUsername();
        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $this->getFailedLoginMessage(),
            ]);
    }

    public function getLogout()
    {
        Auth::logout();
        Session::flush();
        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
    }




    /**
     * Redirect the user to the Google authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('google')->user();

        $u = User::where('email', $user->email)->first();

        if(is_null($u))
            return redirect($this->loginPath())
            ->withErrors([
                $this->loginUsername() => 'Google Account is not associated with the Giligan\'s User.',
            ]);

        Auth::loginUsingId($u->id);

        return redirect('/?rdr=google');
    }
}
