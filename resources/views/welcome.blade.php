<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wabot Nagari Sungai Aua - Pelayanan Publik Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .bg-gradient { background: radial-gradient(circle at top left, #4f46e5, #7c3aed, #2563eb); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
    </style>
</head>
<body class="bg-gradient min-h-screen text-white overflow-x-hidden">

    <nav class="container mx-auto px-6 py-6 flex justify-between items-center relative z-10">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-robot text-indigo-600 text-xl"></i>
            </div>
            <span class="text-xl font-bold tracking-tight">NagariHub <span class="font-light text-indigo-200">Bot</span></span>
        </div>
        <div class="hidden md:flex space-x-8 text-sm font-medium">
            <a href="#" class="hover:text-indigo-200 transition">Beranda</a>
            <a href="#" class="hover:text-indigo-200 transition">Tentang Kami</a>
            <a href="#" class="hover:text-indigo-200 transition">Kontak</a>
        </div>
        <button onclick="toggleModal()" class="bg-white text-indigo-600 px-6 py-2.5 rounded-full font-bold text-sm hover:bg-indigo-50 transition shadow-xl">
            Login Admin
        </button>
    </nav>

    <main class="container mx-auto px-6 pt-10 pb-20 flex flex-col md:flex-row items-center relative">
        <div class="md:w-1/2 space-y-8 z-10">
            <div class="inline-block px-4 py-2 glass rounded-full text-xs font-bold tracking-widest uppercase text-indigo-200">
                🚀 Sistem Pelayanan Digital Nagari
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold leading-tight">
                Pelayanan Publik <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-200 to-white">Lebih Cepat & Mudah.</span>
            </h1>
            <p class="text-lg text-indigo-100 max-w-lg leading-relaxed">
                Automasi pengurusan surat menyurat Nagari Sungai Aua menggunakan WhatsApp Bot. Pantau percakapan dan kelola statistik layanan dalam satu dashboard terintegrasi.
            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 pt-4">
                <button onclick="toggleModal()" class="bg-white text-indigo-700 px-8 py-4 rounded-2xl font-bold shadow-2xl hover:scale-105 transition duration-300">
                    Masuk Dashboard
                </button>
                <button class="glass px-8 py-4 rounded-2xl font-bold hover:bg-white/20 transition duration-300">
                    Pelajari Fitur
                </button>
            </div>
        </div>

        <div class="md:w-1/2 mt-16 md:mt-0 relative flex justify-center">
            <div class="animate-float glass p-8 rounded-3xl w-full max-w-md shadow-2xl relative z-10">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center">
                        <i class="fab fa-whatsapp text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold">Wabot Sungai Aua</p>
                        <p class="text-xs text-green-400">● Online</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="glass p-3 rounded-xl rounded-tl-none max-w-[80%] text-xs">Halo! Saya ingin mengurus SKU.</div>
                    <div class="bg-indigo-600 p-3 rounded-xl rounded-tr-none ml-auto max-w-[80%] text-xs">Siap! Silakan masukkan NIK Anda...</div>
                </div>
            </div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
        </div>
    </main>

    <div id="accessModal" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-indigo-900/60 backdrop-blur-md"></div>
        <div class="glass p-8 rounded-3xl w-full max-w-sm relative z-10 shadow-2xl transform transition-all">
            <div class="text-center space-y-4">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold">Kunci Akses</h3>
                <p class="text-indigo-200 text-sm">Masukkan kunci untuk melanjutkan ke dashboard manajemen pesan.</p>
                <div class="space-y-4 pt-4">
                    <input type="password" id="accessKey" placeholder="Ketik kunci pintu..." 
                        class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-center text-white focus:outline-none focus:ring-2 focus:ring-white/50 transition">
                    <button onclick="checkAccess()" class="w-full bg-white text-indigo-700 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition active:scale-95">
                        Buka Dashboard
                    </button>
                    <button onclick="toggleModal()" class="text-indigo-300 text-xs hover:text-white transition">Tutup</button>
                </div>
                <p id="errorMsg" class="text-red-400 text-xs hidden pt-2">Kunci salah! Silakan hubungi admin.</p>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk buka/tutup Modal
        function toggleModal() {
            const modal = document.getElementById('accessModal');
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                document.getElementById('accessKey').focus();
            }
        }

        // Fungsi Cek Kunci (Ubah 'admin123' dengan kunci pilihan Anda)
       function checkAccess() {
    const key = document.getElementById('accessKey').value;
    const errorMsg = document.getElementById('errorMsg');

    fetch("{{ route('verify.access') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ key: key })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "{{ route('dashboard') }}";
        } else {
            errorMsg.classList.remove('hidden');
            document.getElementById('accessKey').classList.add('border-red-500');
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
}

        // Support Enter key
        document.getElementById('accessKey').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                checkAccess();
            }
        });
    </script>
</body>
</html>