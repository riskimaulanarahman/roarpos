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
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'store_name' => ['required', 'string', 'max:255'],
    //         'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
    //         'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
    //         'password'   => ['required', 'string', 'min:6', 'max:255'],
    //     ]);

    //     try {
    //         DB::transaction(function () use ($data) {
    //             $user = User::create([
    //                 'name'        => $data['store_name'],
    //                 'store_name'  => $data['store_name'],
    //                 'email'       => $data['email'],
    //                 'phone'       => $data['phone'],
    //                 'password'    => Hash::make($data['password']),
    //             ]);

    //             // Kirim verifikasi SECARA SINKRON agar kalau gagal -> throw -> rollback
    //             // (JANGAN di-queue di step registrasi)
    //             $user->notifyNow(new CustomVerifyEmail);
    //         });

    //         return response()->json([
    //             'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
    //         ], 201);

    //     } catch (Throwable $e) {
    //         Log::error('Register gagal (rollback): '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'message' => 'Registrasi gagal. Silakan coba lagi.',
    //         ], 500);
    //     }
    // }
    public function register(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:6', 'max:255'],
        ]);

        try {
            $user = null;

            DB::transaction(function () use ($data, &$user) {
                $user = User::create([
                    'name'        => $data['store_name'],
                    'store_name'  => $data['store_name'],
                    'email'       => $data['email'],
                    'phone'       => $data['phone'],
                    'password'    => Hash::make($data['password']),
                ]);
            });

            // ⬇️ Kirim SETELAH commit + via queue
            $user->notify(
                (new \App\Notifications\CustomVerifyEmail)
                    ->afterCommit()
                    ->onQueue('mail')
            );

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email untuk aktivasi akun.',
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Register gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
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

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi.'], 200);
        }

        // Key throttle gabungan email + IP agar adil per user & per klien
        $key = 'resend-email-verification:' . sha1($request->ip() . '|' . strtolower($user->email));

        // Izinkan 1 permintaan per 5 menit (300 detik)
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Terlalu sering meminta kirim ulang. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        try {
            // Lock lebih dulu supaya race-condition tidak kirim ganda
            RateLimiter::hit($key, 300); // 300 detik = 5 menit

            // Kirim ulang email verifikasi (boleh di-queue)
            $user->notify((new \App\Notifications\CustomVerifyEmail)
                ->afterCommit()
                ->onQueue('mail'));

            return response()->json(['message' => 'Email verifikasi telah dikirim ulang.'], 200);

        } catch (Throwable $e) {
            // Kalau gagal kirim, lepaskan lock supaya user bisa coba lagi
            RateLimiter::clear($key);

            Log::warning('Gagal kirim ulang verifikasi: '.$e->getMessage());
            return response()->json([
                'message' => 'Gagal mengirim ulang email verifikasi. Coba beberapa saat lagi.'
            ], 500);
        }
    }

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

    //logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout success',
        ]);
    }
}
