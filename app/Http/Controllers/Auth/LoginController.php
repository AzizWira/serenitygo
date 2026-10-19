<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class LoginController extends Controller
{
    public function create() { return response()->view('auth.login')->header('X-Robots-Tag','noindex, nofollow'); }
    public function store(Request $request)
    {
        $data = $request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($data)) throw ValidationException::withMessages(['email'=>'Email atau password tidak sesuai.']);
        $request->session()->regenerate();
        return redirect()->intended(route('owner.dashboard'));
    }
    public function destroy(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
