<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:6', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($data) {
                $user = User::create([
                    'name'        => $data['store_name'],
                    'store_name'  => $data['store_name'],
                    'email'       => $data['email'],
                    'phone'       => $data['phone'],
                    'password'    => Hash::make($data['password']),
                ]);

                // Kirim verifikasi SECARA SINKRON agar kalau gagal -> throw -> rollback
                // (JANGAN di-queue di step registrasi)
                $user->notifyNow(new CustomVerifyEmail);
            });

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
            ], 201);

        } catch (Throwable $e) {
            Log::error('Register gagal (rollback): '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Registrasi gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    public function verify(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            if (! hash_equals((string) $hash, sha1($user->email))) {
                return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
            }

            if ($user->hasVerifiedEmail()) {
                if ($redirect = $request->query('redirect')) {
                    return redirect()->away($redirect . '?status=already_verified');
                }
                return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
            }

            DB::transaction(function () use ($user) {
                if ($user->markEmailAsVerified()) {
                    event(new Verified($user));
                }
            });

            if ($redirect = $request->query('redirect')) {
                return redirect()->away($redirect . '?status=verified');
            }

            return response()->json(['message' => 'Email berhasil diverifikasi. Silakan login.'], 200);

        } catch (Throwable $e) {
            Log::error('Verifikasi gagal: '.$e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat verifikasi.'], 500);
        }
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $user = User::where('email', $request->email)->first();
            if (! $user) {
                return response()->json(['message' => 'Email tidak ditemukan.'], 404);
            }
            if ($user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
            }

            // Boleh di-queue untuk kirim ulang (user sudah pasti ada).
            // afterCommit mastiin job dikirim setelah transaksi (kalau ada) komit.
            $user->notify((new CustomVerifyEmail)->afterCommit()->onQueue('mail'));

            return response()->json(['message' => 'Email verifikasi telah dikirim ulang.'], 200);

        } catch (Throwable $e) {
            Log::warning('Gagal kirim ulang verifikasi: '.$e->getMessage());
            return response()->json(['message' => 'Gagal mengirim ulang email verifikasi. Coba beberapa saat lagi.'], 500);
        }
    }


    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'store_name' => ['required', 'string', 'max:255'],
    //         'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
    //         'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
    //         'password'   => ['required', 'string', 'min:6', 'max:255'],
    //     ]);

    //     $user = User::create([
    //         'name'        => $data['store_name'],
    //         'store_name'  => $data['store_name'],
    //         'email'       => $data['email'],
    //         'phone'       => $data['phone'],
    //         'password'    => Hash::make($data['password']),
    //     ]);

    //     // Kirim email verifikasi
    //     // $user->notify(new CustomVerifyEmail());
    //     $user->notify((new CustomVerifyEmail)->onQueue('mail'));

    //     return response()->json([
    //         'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
    //     ], 201);
    // }

    // // Link dari email mengarah ke sini (signed URL)
    // public function verify(Request $request, $id, $hash)
    // {
    //     $user = User::findOrFail($id);

    //     // validasi hash email & tanda tangan URL (middleware 'signed' juga memeriksa signature+expiry)
    //     if (! hash_equals((string) $hash, sha1($user->email))) {
    //         return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
    //     }

    //     if ($user->hasVerifiedEmail()) {
    //         // optional: redirect ke FE jika param ?redirect= tersedia
    //         if ($redirect = $request->query('redirect')) {
    //             return redirect()->away($redirect . '?status=already_verified');
    //         }
    //         return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
    //     }

    //     if ($user->markEmailAsVerified()) {
    //         event(new Verified($user));
    //     }

    //     // jika ingin redirect ke FE (misal halaman login) tambahkan ?redirect=https://fe-app.com/verified
    //     if ($redirect = $request->query('redirect')) {
    //         return redirect()->away($redirect . '?status=verified');
    //     }

    //     return response()->json(['message' => 'Email berhasil diverifikasi. Silakan login.'], 200);
    // }

    // // Kirim ulang email verifikasi
    // public function resendVerification(Request $request)
    // {
    //     $request->validate(['email' => ['required', 'email']]);

    //     $user = User::where('email', $request->email)->first();
    //     if (! $user) {
    //         return response()->json(['message' => 'Email tidak ditemukan.'], 404);
    //     }
    //     if ($user->hasVerifiedEmail()) {
    //         return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
    //     }

    //     // $user->notify(new CustomVerifyEmail());
    //     $user->notify((new CustomVerifyEmail)->onQueue('mail'));

    //     return response()->json(['message' => 'Email verifikasi telah dikirim ulang.'], 200);
    // }

    // LOGIN Anda (pastikan cek verified)
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        if (! $user) {
            return response(['message' => ['Email not found']], 404);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => ['Email belum terverifikasi. Cek email Anda.']], 403);
        }

        if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response(['message' => ['Password is wrong']], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response(['user' => $user, 'token' => $token], 200);
    }

    // public function login(Request $request)
    // {
    //     $loginData = $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);

    //     $user = \App\Models\User::where('email', $request->email)->first();

    //     if (!$user) {
    //         return response([
    //             'message' => ['Email not found'],
    //         ], 404);
    //     }

    //     if (!Hash::check($request->password, $user->password)) {
    //         return response([
    //             'message' => ['Password is wrong'],
    //         ], 404);
    //     }

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response([
    //         'user' => $user,
    //         'token' => $token,
    //     ], 200);
    // }

    //logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout success',
        ]);
    }
}
