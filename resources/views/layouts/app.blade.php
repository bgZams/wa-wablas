<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Bot WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        /* Custom styles untuk animasi dan background */
        @layer base {
            body {
                @apply font-sans antialiased;
            }
        }
        @layer components {
            .card-stats {
                @apply rounded-xl p-6 shadow-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl;
            }
            .background-animation {
                background: linear-gradient(-45deg, #a5b4fc, #818cf8, #6366f1, #3b82f6);
                background-size: 400% 400%;
                animation: gradientBG 15s ease infinite;
            }
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Animasi fade-in */
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="background-animation text-slate-800">

    <nav class="bg-white/30 backdrop-blur-lg shadow-lg fixed top-0 w-full z-50 transition-all duration-300">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a class="text-2xl font-bold text-slate-800 transition-colors hover:text-slate-600" href="#">WA Bot Dashboard</a>
            <div class="block lg:hidden">
                <button id="nav-toggle" class="flex items-center px-3 py-2 border rounded border-slate-400 text-slate-500 hover:text-slate-700 hover:border-slate-700">
                    <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
                </button>
            </div>
            <div class="w-full lg:flex lg:items-center lg:w-auto hidden lg:block" id="nav-content">
                <ul class="lg:flex justify-end items-center text-lg mt-4 lg:mt-0">
                    <li>
                        <a class="block lg:inline-block text-slate-600 hover:text-slate-800 mr-4 transition-colors font-medium" href="{{ route('dashboard') }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="block lg:inline-block text-slate-600 hover:text-slate-800 transition-colors font-medium" href="{{ route('usage') }}">
                            Panduan Penggunaan
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mx-auto px-4 md:px-8 pt-24 pb-8">
        <header class="flex flex-col md:flex-row items-center justify-between mb-6 fade-in">
            <h1 class="text-3xl font-extrabold text-slate-800 text-center md:text-left">Dashboard Bot WhatsApp 🤖</h1>
        </header>

        <hr class="my-6 border-t border-slate-300">

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in">
            <div class="bg-blue-600/80 text-white card-stats">
                <div class="text-sm font-semibold mb-2 opacity-90">Pesan Terkirim</div>
                <h2 class="text-4xl font-bold">{{ $totalSent }}</h2>
                <p class="text-xs mt-1 opacity-80">Total pesan yang berhasil dikirim.</p>
            </div>
            <div class="bg-indigo-600/80 text-white card-stats">
                <div class="text-sm font-semibold mb-2 opacity-90">Pesan Diterima</div>
                <h2 class="text-4xl font-bold">{{ $totalReceived }}</h2>
                <p class="text-xs mt-1 opacity-80">Total pesan masuk yang diproses.</p>
            </div>
            <div class="bg-slate-600/80 text-white card-stats">
                <div class="text-sm font-semibold mb-2 opacity-90">Pesan Gagal</div>
                <h2 class="text-4xl font-bold">{{ $totalFailed }}</h2>
                <p class="text-xs mt-1 opacity-80">Total pesan yang gagal dikirim.</p>
            </div>
        </section>

        <hr class="my-6 border-t border-slate-300">

        <section class="bg-white/40 backdrop-blur-lg rounded-xl shadow-xl overflow-hidden fade-in">
            <div class="bg-white/20 px-6 py-4 border-b border-slate-300/50">
                <h3 class="text-xl font-semibold text-slate-800">Kotak Masuk (Inbox) 📥</h3>
            </div>
            <div class="p-6">
                <ul class="divide-y divide-slate-300/50">
                    @forelse ($uniqueContacts as $contact)
                    <li class="py-4">
                        <a href="{{ route('conversation', ['phone' => $contact['phone']]) }}" class="block p-3 rounded-lg transition-colors hover:bg-white/50">
                            <div class="flex items-start justify-between">
                                <h4 class="text-lg font-medium text-slate-800">
                                    <span class="font-bold">Kontak:</span> {{ $contact['phone'] }}
                                </h4>
                                <small class="text-slate-600 text-sm flex-shrink-0 ml-4">
                                    {{ \Carbon\Carbon::createFromTimestamp($contact['timestamp'])->diffForHumans() }}
                                </small>
                            </div>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ Str::limit($contact['latest_message'], 100) }}
                            </p>
                        </a>
                    </li>
                    @empty
                    <li class="py-4 text-center text-slate-500">Belum ada pesan masuk.</li>
                    @endforelse
                </ul>
            </div>
        </section>
        </main>
    <script>
        document.getElementById('nav-toggle').onclick = function(){
            const navContent = document.getElementById('nav-content');
            navContent.classList.toggle('hidden');
        }
    </script>

</body>
</html>
