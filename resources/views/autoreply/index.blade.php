<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kelola Auto-Reply — WhatsApp Bot Pelayanan Nagari Sungai Aua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --wa-green: #00a884;
            --wa-green-dark: #008069;
            --wa-green-light: #25d366;
            --wa-chat-bg: #efeae2;
            --wa-panel-bg: #f0f2f5;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: #f3f4f6;
            color: #111827;
        }
        .wa-top-bar {
            background-color: var(--wa-green-dark);
        }
        /* Custom Switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #00a884;
            background-color: #00a884;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #00a884;
        }
        /* Glass effect */
        .glass-card {
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
        }
        pre.chat-bubble {
            font-family: inherit;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Top Navigation Header -->
    <header class="wa-top-bar text-white shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="p-2 hover:bg-black/10 rounded-full transition-colors text-white" title="Kembali ke Dashboard">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center font-bold">
                        <i class="fas fa-robot text-emerald-200"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-base sm:text-lg leading-tight">Manajemen Auto-Reply Bot</h1>
                        <p class="text-xs text-emerald-100">Nagari Sungai Aua · WhatsApp Pelayanan Publik</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Master Toggle Switch -->
                <div class="flex items-center bg-black/15 px-3 py-1.5 rounded-full border border-white/10">
                    <span class="text-xs mr-2 font-medium hidden sm:inline" id="masterToggleText">
                        Auto-Reply: <strong class="{{ $autoReplyEnabled ? 'text-emerald-300' : 'text-red-300' }}">{{ $autoReplyEnabled ? 'AKTIF' : 'NONAKTIF' }}</strong>
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="masterToggle" class="sr-only peer" {{ $autoReplyEnabled ? 'checked' : '' }} onchange="toggleMasterAutoReply(this.checked)">
                        <div class="w-9 h-5 bg-gray-400 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-400"></div>
                    </label>
                </div>

                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-1.5 border border-white/30 rounded-lg text-xs font-medium hover:bg-white/10 transition-colors">
                    <i class="fas fa-comments mr-1.5"></i> Dashboard Chat
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Flash Message Alerts -->
        @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm flex items-start justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-emerald-500 mr-3 text-lg"></i>
                    <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center mb-1">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <h3 class="text-sm font-semibold text-red-800">Terdapat kesalahan pada input:</h3>
                </div>
                <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Summary Statistics Cards -->
        <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <div class="glass-card rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between text-gray-500 text-xs font-medium">
                    <span>Total Aturan</span>
                    <i class="fas fa-list text-gray-400"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
                <span class="text-[11px] text-gray-400 mt-1">Seluruh kata kunci</span>
            </div>

            <div class="glass-card rounded-xl p-4 flex flex-col justify-between border-emerald-100 bg-emerald-50/30">
                <div class="flex items-center justify-between text-emerald-700 text-xs font-medium">
                    <span>Aturan Aktif</span>
                    <i class="fas fa-check-circle text-emerald-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $stats['active'] }}</div>
                <span class="text-[11px] text-emerald-600/80 mt-1">Siap membalas pesan</span>
            </div>

            <div class="glass-card rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between text-gray-500 text-xs font-medium">
                    <span>Nonaktif</span>
                    <i class="fas fa-pause-circle text-gray-400"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-gray-600">{{ $stats['inactive'] }}</div>
                <span class="text-[11px] text-gray-400 mt-1">Dihentikan sementara</span>
            </div>

            <div class="glass-card rounded-xl p-4 flex flex-col justify-between border-blue-50 bg-blue-50/20">
                <div class="flex items-center justify-between text-blue-700 text-xs font-medium">
                    <span>Persis (Exact)</span>
                    <i class="fas fa-bullseye text-blue-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-blue-700">{{ $stats['exact'] }}</div>
                <span class="text-[11px] text-blue-500 mt-1">Kecocokan 100%</span>
            </div>

            <div class="glass-card rounded-xl p-4 flex flex-col justify-between border-purple-50 bg-purple-50/20">
                <div class="flex items-center justify-between text-purple-700 text-xs font-medium">
                    <span>Mengandung</span>
                    <i class="fas fa-font text-purple-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-purple-700">{{ $stats['contains'] }}</div>
                <span class="text-[11px] text-purple-500 mt-1">Ada di dalam kalimat</span>
            </div>

            <div class="glass-card rounded-xl p-4 flex flex-col justify-between border-amber-50 bg-amber-50/20">
                <div class="flex items-center justify-between text-amber-700 text-xs font-medium">
                    <span>Diawali Kata</span>
                    <i class="fas fa-step-forward text-amber-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-700">{{ $stats['starts_with'] }}</div>
                <span class="text-[11px] text-amber-500 mt-1">Awal mula kalimat</span>
            </div>
        </section>

        <!-- Live Simulator & Test Widget -->
        <section class="glass-card rounded-2xl p-5 border-emerald-200 bg-gradient-to-br from-emerald-50/40 via-white to-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-3">
                <div>
                    <h2 class="text-base font-bold text-gray-800 flex items-center">
                        <i class="fas fa-flask text-emerald-600 mr-2"></i> Simulator Uji Coba Auto-Reply
                    </h2>
                    <p class="text-xs text-gray-500">Ketik pesan contoh seperti yang biasa dikirim warga (misal: "menu", "halo nagari", dsb.) untuk menguji aturan mana yang akan terpicu secara realtime.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                <div class="md:col-span-8 flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" id="simInput" placeholder="Ketik pesan simulasi (contoh: menu)..." class="w-full text-sm rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" onkeydown="if(event.key==='Enter') runSimulation()">
                    </div>
                    <button type="button" onclick="runSimulation()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center">
                        <i class="fas fa-play mr-1.5 text-xs"></i> Uji Pesan
                    </button>
                </div>

                <div class="md:col-span-4 flex flex-wrap gap-1.5 items-center">
                    <span class="text-xs text-gray-400">Contoh cepat:</span>
                    <button type="button" onclick="testExample('menu')" class="text-xs bg-gray-100 hover:bg-emerald-100 text-gray-700 hover:text-emerald-800 px-2.5 py-1 rounded-lg border border-gray-200 transition-colors">menu</button>
                    <button type="button" onclick="testExample('halo')" class="text-xs bg-gray-100 hover:bg-emerald-100 text-gray-700 hover:text-emerald-800 px-2.5 py-1 rounded-lg border border-gray-200 transition-colors">halo</button>
                    <button type="button" onclick="testExample('halo admin')" class="text-xs bg-gray-100 hover:bg-emerald-100 text-gray-700 hover:text-emerald-800 px-2.5 py-1 rounded-lg border border-gray-200 transition-colors">halo admin</button>
                </div>
            </div>

            <!-- Simulation Result Container -->
            <div id="simResultBox" class="mt-4 hidden p-4 rounded-xl border transition-all"></div>
        </section>

        <!-- Main Table & Control Bar -->
        <section class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <!-- Table Header Tools -->
            <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center space-x-2">
                    <h2 class="text-base font-bold text-gray-800">Daftar Aturan Auto-Reply</h2>
                    <span class="bg-gray-200 text-gray-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">{{ $rules->total() }}</span>
                </div>

                <!-- Action & Filter Bar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Input -->
                    <form method="GET" action="{{ route('autoreply.manage') }}" class="flex items-center flex-wrap gap-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari kata kunci / isi..." class="text-xs rounded-lg border border-gray-300 pl-8 pr-3 py-2 focus:ring-1 focus:ring-emerald-500 outline-none w-44 sm:w-56">
                        </div>

                        <!-- Filter Match Type -->
                        <select name="match_type" onchange="this.form.submit()" class="text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:ring-1 focus:ring-emerald-500 outline-none bg-white">
                            <option value="">Semua Tipe</option>
                            <option value="exact" {{ $matchType === 'exact' ? 'selected' : '' }}>Persis (Exact)</option>
                            <option value="contains" {{ $matchType === 'contains' ? 'selected' : '' }}>Mengandung (Contains)</option>
                            <option value="starts_with" {{ $matchType === 'starts_with' ? 'selected' : '' }}>Diawali (Starts With)</option>
                        </select>

                        <!-- Filter Status -->
                        <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:ring-1 focus:ring-emerald-500 outline-none bg-white">
                            <option value="">Semua Status</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>

                        @if($search !== '' || $matchType !== '' || $status !== '')
                            <a href="{{ route('autoreply.manage') }}" class="text-xs text-gray-500 hover:text-red-500 p-2" title="Reset Filter">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </form>

                    <!-- Add New Button -->
                    <button type="button" onclick="openCreateModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors flex items-center shadow-sm">
                        <i class="fas fa-plus mr-1.5"></i> Tambah Aturan
                    </button>
                </div>
            </div>

            <!-- Rules Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-100/70 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th scope="col" class="py-3.5 px-4 w-12 text-center">#</th>
                            <th scope="col" class="py-3.5 px-4 w-44">Kata Kunci (Keyword)</th>
                            <th scope="col" class="py-3.5 px-4 w-36">Tipe Kecocokan</th>
                            <th scope="col" class="py-3.5 px-4">Isi Pesan Balasan</th>
                            <th scope="col" class="py-3.5 px-4 w-28 text-center">Status</th>
                            <th scope="col" class="py-3.5 px-4 w-36 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($rules as $index => $rule)
                            <tr class="hover:bg-gray-50/80 transition-colors" id="row-{{ $rule->id }}">
                                <td class="py-3.5 px-4 text-center text-xs text-gray-400 font-mono">
                                    {{ $rules->firstItem() + $index }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 font-mono">
                                            <i class="fas fa-key mr-1.5 text-[10px] text-emerald-600"></i>{{ $rule->keyword }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($rule->match_type === 'exact')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-bullseye mr-1 text-[10px]"></i> Persis (Exact)
                                        </span>
                                    @elseif($rule->match_type === 'contains')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-font mr-1 text-[10px]"></i> Mengandung
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-100 text-amber-800">
                                            <i class="fas fa-step-forward mr-1 text-[10px]"></i> Diawali Kata
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="relative group">
                                        <p class="text-xs text-gray-700 line-clamp-2 font-sans leading-relaxed">
                                            {{ $rule->reply }}
                                        </p>
                                        @if(strlen($rule->reply) > 90)
                                            <button type="button" onclick="viewReplyModal({{ json_encode($rule) }})" class="text-[11px] text-emerald-600 hover:text-emerald-800 font-medium mt-1 inline-flex items-center">
                                                Lihat selengkapnya <i class="fas fa-chevron-right ml-1 text-[9px]"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="toggle-{{ $rule->id }}" {{ $rule->active ? 'checked' : '' }} onchange="toggleRuleStatus({{ $rule->id }})">
                                        <div class="w-8 h-4 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                    <div class="text-[10px] text-gray-400 mt-0.5" id="status-label-{{ $rule->id }}">
                                        {{ $rule->active ? 'Aktif' : 'Nonaktif' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1">
                                        <!-- Test button -->
                                        <button type="button" onclick="testExample('{{ addslashes($rule->keyword) }}')" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Uji aturan ini">
                                            <i class="fas fa-play text-xs"></i>
                                        </button>

                                        <!-- Edit button -->
                                        <button type="button" onclick="openEditModal({{ json_encode($rule) }})" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit aturan">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        <!-- Delete button -->
                                        <button type="button" onclick="confirmDeleteRule({{ $rule->id }}, '{{ addslashes($rule->keyword) }}')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus aturan">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fas fa-robot text-4xl mb-3 text-gray-300"></i>
                                        <p class="text-sm font-medium text-gray-600">Belum ada aturan auto-reply yang ditemukan.</p>
                                        <p class="text-xs text-gray-400 mt-1">Gunakan tombol "Tambah Aturan" untuk membuat pemicu balasan otomatis baru.</p>
                                        <button type="button" onclick="openCreateModal()" class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">
                                            <i class="fas fa-plus mr-1.5"></i> Buat Aturan Pertama
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($rules->hasPages())
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    {{ $rules->links() }}
                </div>
            @endif
        </section>

    </main>

    <!-- Modal: Form Tambah / Edit Aturan -->
    <div id="ruleModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <i class="fas fa-robot text-sm"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800" id="modalTitle">Tambah Aturan Auto-Reply</h3>
                </div>
                <button type="button" onclick="closeRuleModal()" class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="ruleForm" method="POST" action="{{ route('autoreply.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="id" id="ruleId" value="">

                <!-- Keyword -->
                <div>
                    <label for="inputKeyword" class="block text-xs font-semibold text-gray-700 mb-1">
                        Kata Kunci (Keyword) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="keyword" id="inputKeyword" required placeholder="Contoh: menu, halo, info" class="w-full text-sm rounded-xl border border-gray-300 px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none font-mono">
                    <p class="text-[11px] text-gray-400 mt-1">Kata atau frasa yang diketikkan oleh warga (case-insensitive).</p>
                </div>

                <!-- Match Type -->
                <div>
                    <label for="inputMatchType" class="block text-xs font-semibold text-gray-700 mb-1">
                        Tipe Kecocokan <span class="text-red-500">*</span>
                    </label>
                    <select name="match_type" id="inputMatchType" required class="w-full text-sm rounded-xl border border-gray-300 px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                        <option value="exact">Persis (Exact) — Pesan harus sama persis dengan kata kunci</option>
                        <option value="contains">Mengandung (Contains) — Membalas jika ada kata kunci di dalam kalimat</option>
                        <option value="starts_with">Diawali (Starts With) — Membalas jika pesan diawali kata kunci</option>
                    </select>
                </div>

                <!-- Reply Message Textarea -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="inputReply" class="block text-xs font-semibold text-gray-700">
                            Isi Pesan Balasan WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <!-- Formatting Helpers -->
                        <div class="flex items-center space-x-1 text-[11px] text-gray-500">
                            <span class="text-gray-400">Format:</span>
                            <button type="button" onclick="insertFormatting('*tebal*')" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 font-bold" title="Tebal">*B*</button>
                            <button type="button" onclick="insertFormatting('_miring_')" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 italic" title="Miring">_I_</button>
                            <button type="button" onclick="insertFormatting('~coret~')" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 line-through" title="Coret">~S~</button>
                            <button type="button" onclick="insertFormatting('```kode```')" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 font-mono" title="Monospace">```</button>
                        </div>
                    </div>
                    <textarea name="reply" id="inputReply" rows="6" required placeholder="Tuliskan teks balasan lengkap yang akan dikirimkan bot..." class="w-full text-sm rounded-xl border border-gray-300 p-3.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none leading-relaxed"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Mendukung emoji WhatsApp dan format teks bintang (*) untuk tebal, underscore (_) untuk miring.</p>
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center justify-between pt-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-700">Status Aturan</div>
                        <div class="text-[11px] text-gray-400">Aktifkan agar bot langsung merespon saat kata kunci diterima</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" value="1" id="inputActive" class="sr-only peer" checked>
                        <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeRuleModal()" class="px-4 py-2 rounded-xl text-xs font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-sm flex items-center">
                        <i class="fas fa-save mr-1.5"></i> Simpan Aturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Lihat Balasan Lengkap -->
    <div id="viewModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 font-mono" id="viewKeyword">
                        Keyword
                    </span>
                    <span class="text-xs text-gray-400" id="viewMatchType">Tipe</span>
                </div>
                <button type="button" onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tampilan Pesan WhatsApp:</div>
                <div class="p-4 rounded-xl bg-[#efeae2] border border-[#d9d5ce] text-gray-900 text-sm shadow-inner">
                    <pre class="chat-bubble" id="viewReplyText"></pre>
                </div>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="button" onclick="closeViewModal()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-200 hover:bg-gray-300 text-gray-800 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Konfirmasi Hapus -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 mx-auto flex items-center justify-center text-xl mb-4">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Hapus Aturan Auto-Reply?</h3>
            <p class="text-xs text-gray-500 mb-6">
                Apakah Anda yakin ingin menghapus aturan untuk kata kunci <strong id="deleteKeywordLabel" class="text-gray-800 font-mono"></strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-center space-x-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-xl text-xs font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition-colors shadow-sm">
                        Ya, Hapus Aturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Element -->
    <div id="toastBox" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 max-w-sm"></div>

    <!-- Scripts -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Toast Helper
        function showToast(message, type = 'success') {
            const box = document.getElementById('toastBox');
            const bgClass = type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            box.className = `fixed bottom-5 right-5 z-50 transform transition-all duration-300 max-w-sm ${bgClass} px-4 py-3 rounded-xl shadow-lg flex items-center space-x-2 text-xs font-medium`;
            box.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;

            setTimeout(() => {
                box.classList.remove('translate-y-20', 'opacity-0');
            }, 50);

            setTimeout(() => {
                box.classList.add('translate-y-20', 'opacity-0');
            }, 3500);
        }

        // Toggle Master Switch
        async function toggleMasterAutoReply(enabled) {
            try {
                const res = await fetch('{{ route("autoreply.toggle_global") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ enabled })
                });
                const data = await res.json();
                if (data.success) {
                    const textEl = document.getElementById('masterToggleText');
                    textEl.innerHTML = `Auto-Reply: <strong class="${enabled ? 'text-emerald-300' : 'text-red-300'}">${enabled ? 'AKTIF' : 'NONAKTIF'}</strong>`;
                    showToast(data.message);
                } else {
                    showToast('Gagal mengubah pengaturan master.', 'error');
                }
            } catch (e) {
                showToast('Terjadi kesalahan koneksi.', 'error');
            }
        }

        // Toggle Single Rule Status
        async function toggleRuleStatus(id) {
            try {
                const res = await fetch(`/autoreply/manage/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    const label = document.getElementById(`status-label-${id}`);
                    label.textContent = data.active ? 'Aktif' : 'Nonaktif';
                    showToast(data.message);
                } else {
                    showToast('Gagal mengubah status aturan.', 'error');
                }
            } catch (e) {
                showToast('Terjadi kesalahan koneksi.', 'error');
            }
        }

        // Run Live Simulation
        async function runSimulation() {
            const input = document.getElementById('simInput');
            const message = input.value.trim();
            const resultBox = document.getElementById('simResultBox');

            if (!message) {
                input.focus();
                return;
            }

            resultBox.classList.remove('hidden');
            resultBox.className = 'mt-4 p-4 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center text-xs text-gray-500';
            resultBox.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menguji kecocokan dengan seluruh aturan aktif...';

            try {
                const res = await fetch('{{ route("autoreply.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message })
                });
                const data = await res.json();

                if (data.matched) {
                    const typeLabel = data.rule.match_type === 'exact' ? 'Persis (Exact)' : (data.rule.match_type === 'contains' ? 'Mengandung Kata' : 'Diawali Kata');
                    resultBox.className = 'mt-4 p-4 rounded-xl border border-emerald-300 bg-emerald-50/60 transition-all';
                    resultBox.innerHTML = `
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex items-center space-x-2">
                                <span class="bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    <i class="fas fa-check mr-1"></i> Cocok (Rule Terpicu)
                                </span>
                                <span class="text-xs text-gray-600">Kata Kunci: <strong class="font-mono text-emerald-900 font-bold">${escapeHtml(data.rule.keyword)}</strong> (${typeLabel})</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-gray-500 mb-1 font-semibold">Pesan Balasan yang Akan Dikirim Bot ke WhatsApp Warga:</div>
                        <div class="p-3 rounded-lg bg-[#efeae2] border border-[#d9d5ce] text-gray-900 text-xs shadow-inner">
                            <pre class="chat-bubble">${escapeHtml(data.reply)}</pre>
                        </div>
                    `;
                } else {
                    resultBox.className = 'mt-4 p-4 rounded-xl border border-amber-300 bg-amber-50/80 transition-all';
                    resultBox.innerHTML = `
                        <div class="flex items-center space-x-2 text-amber-900 text-xs">
                            <i class="fas fa-info-circle text-amber-600 text-sm"></i>
                            <span>${escapeHtml(data.message)}</span>
                        </div>
                        <p class="text-[11px] text-amber-700 mt-1">Bot tidak akan membalas otomatis untuk pesan ini karena belum ada kata kunci aktif yang cocok.</p>
                    `;
                }
            } catch (e) {
                resultBox.className = 'mt-4 p-4 rounded-xl border border-red-200 bg-red-50 text-xs text-red-700';
                resultBox.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Gagal menjalankan pengujian aturan.';
            }
        }

        function testExample(text) {
            const input = document.getElementById('simInput');
            input.value = text;
            runSimulation();
            window.scrollTo({ top: input.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
        }

        // Modal Controls
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Aturan Auto-Reply Baru';
            document.getElementById('ruleForm').action = '{{ route("autoreply.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('ruleId').value = '';
            document.getElementById('inputKeyword').value = '';
            document.getElementById('inputMatchType').value = 'exact';
            document.getElementById('inputReply').value = '';
            document.getElementById('inputActive').checked = true;

            document.getElementById('ruleModal').classList.remove('hidden');
        }

        function openEditModal(rule) {
            document.getElementById('modalTitle').textContent = 'Edit Aturan Auto-Reply';
            document.getElementById('ruleForm').action = `/autoreply/manage/${rule.id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('ruleId').value = rule.id;
            document.getElementById('inputKeyword').value = rule.keyword;
            document.getElementById('inputMatchType').value = rule.match_type;
            document.getElementById('inputReply').value = rule.reply;
            document.getElementById('inputActive').checked = Boolean(rule.active);

            document.getElementById('ruleModal').classList.remove('hidden');
        }

        function closeRuleModal() {
            document.getElementById('ruleModal').classList.add('hidden');
        }

        function viewReplyModal(rule) {
            document.getElementById('viewKeyword').textContent = rule.keyword;
            document.getElementById('viewMatchType').textContent = rule.match_type === 'exact' ? 'Persis' : (rule.match_type === 'contains' ? 'Mengandung' : 'Diawali');
            document.getElementById('viewReplyText').textContent = rule.reply;
            document.getElementById('viewModal').classList.remove('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        function confirmDeleteRule(id, keyword) {
            document.getElementById('deleteKeywordLabel').textContent = keyword;
            document.getElementById('deleteForm').action = `/autoreply/manage/${id}`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function insertFormatting(pattern) {
            const textarea = document.getElementById('inputReply');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;

            if (start !== end) {
                const selected = text.substring(start, end);
                const firstChar = pattern[0];
                const lastChar = pattern[pattern.length - 1];
                textarea.value = text.substring(0, start) + firstChar + selected + lastChar + text.substring(end);
            } else {
                textarea.value = text.substring(0, start) + pattern + text.substring(end);
            }
            textarea.focus();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeRuleModal();
                closeViewModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
