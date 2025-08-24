@php
    $palette = match($status ?? 'info') {
    'verified' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'ring' =>
    'ring-green-400'],
    'already_verified' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800',
    'ring' => 'ring-emerald-400'],
    'invalid' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-800', 'ring' =>
    'ring-amber-400'],
    'expired' => ['bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'text' => 'text-violet-800', 'ring' =>
    'ring-violet-400'],
    'error' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'ring' => 'ring-red-400'],
    default => ['bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'text' => 'text-slate-800', 'ring' =>
    'ring-slate-400'],
    };

    $icon = match($status ?? 'info') {
    'verified', 'already_verified' => 'M16.704 4.153a.75.75 0 0 1 .143 1.052l-7.5 9.5a.75.75 0 0
    1-1.144.06l-3.5-3.5a.75.75 0 1 1 1.06-1.06l2.9 2.9 6.98-8.845a.75.75 0 0 1 1.06-.107Z',
    'invalid' => 'M9 12.75h.008v.008H9v-.008Zm0-6v4.5m0 6.75a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z',
    'expired' => 'M12 6v6l3.5 2M12 3.75a8.25 8.25 0 1 0 0 16.5 8.25 8.25 0 0 0 0-16.5Z', {{-- ikon jam --}}
    'error' => 'M12 9v3m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    default => 'M12 9v3m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    };
@endphp
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Verifikasi Email' }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
        <script>
            (function () {
                const s = document.createElement('script');
                s.src = "https://cdn.tailwindcss.com";
                s.onload = () => tailwind.config = {
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Inter', 'ui-sans-serif', 'system-ui']
                            }
                        }
                    }
                };
                document.head.appendChild(s);
            })();

        </script>
</head>

<body class="min-h-dvh bg-gradient-to-br from-slate-50 to-slate-100 antialiased">
    <main class="mx-auto max-w-xl p-6">
        <div class="mt-16">
            <div
                class="rounded-3xl border {{ $palette['border'] }} bg-white shadow-sm ring-1 {{ $palette['ring'] }}/10 overflow-hidden">
                <div class="p-8">
                    <div class="flex items-start gap-4">
                        <div
                            class="shrink-0 inline-flex items-center justify-center w-12 h-12 rounded-2xl {{ $palette['bg'] }} ring-1 {{ $palette['ring'] }}/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 {{ $palette['text'] }}">
                                <path d="{{ $icon }}" />
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <h1
                                class="text-2xl font-semibold tracking-tight {{ $palette['text'] }}">
                                {{ $title ?? 'Verifikasi Email' }}
                            </h1>
                            <p class="text-slate-600 leading-relaxed">
                                {{ $message ?? 'Status verifikasi email.' }}
                            </p>
                            @isset($code)
                                <p class="text-xs text-slate-400">Kode status: {{ $code }}</p>
                            @endisset

                            {{-- CTA utama --}}
                            <div class="pt-4 flex flex-wrap gap-3">
                                <blade
                                    if|((%24status%20%3F%3F%20%26%2339%3B%26%2339%3B)%20%3D%3D%3D%20%26%2339%3Bverified%26%2339%3B%20%7C%7C%20(%24status%20%3F%3F%20%26%2339%3B%26%2339%3B)%20%3D%3D%3D%20%26%2339%3Balready_verified%26%2339%3B)>
                                    <a href="{{ url('/login') }}"
                                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                                        Masuk
                                    </a>
                                    <a href="{{ url('/') }}"
                                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300">Beranda</a>
                                    <blade
                                        elseif|((%24status%20%3F%3F%20%26%2339%3B%26%2339%3B)%20%3D%3D%3D%20%26%2339%3Bexpired%26%2339%3B) />
                                    {{-- Form kirim ulang verifikasi --}}
                                    <form method="POST" action="{{ route('verification.resend') }}"
                                        class="flex flex-col sm:flex-row gap-3 w-full">
                                        @csrf
                                        <input type="email" name="email"
                                            value="{{ old('email', $prefillEmail ?? '') }}"
                                            placeholder="Email kamu" required
                                            class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400" />
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600">
                                            Kirim Ulang Link
                                        </button>
                                    </form>
                                    <a href="{{ url('/login') }}"
                                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300">
                                        Login (nanti bisa minta verifikasi dari profil)
                                    </a>
                                @else
                                    <a href="{{ url('/') }}"
                                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300">Beranda</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="px-8 py-4 bg-slate-50/60 border-t {{ $palette['border'] }} text-xs text-slate-500 flex items-center justify-between">
                    <span>Jika ini bukan kamu, abaikan halaman ini.</span>
                    <span>&copy; {{ date('Y') }} • Aplikasi Kamu</span>
                </div>
            </div>

            {{-- Tips kontekstual --}}
            @if(($status ?? '') === 'expired')
                <div class="mt-6 rounded-xl bg-white border border-violet-200 shadow-sm p-4 text-sm text-slate-600">
                    Tips: Cek folder <strong>Spam/Promosi</strong> setelah meminta link baru. Link verifikasi biasanya
                    berlaku terbatas waktu.
                </div>
            @endif
        </div>
    </main>
</body>

</html>
