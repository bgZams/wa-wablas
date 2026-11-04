@extends('layouts.app')

@section('content')
    <div class="pt-20">

    </div>
<main class="container mx-auto px-4 md:px-8 py-8">
    <header class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 fade-in">
        <h1 class="text-3xl font-extrabold text-slate-800">Percakapan dengan {{ $phone }}</h1>
        <a href="{{ route('dashboard') }}" class="mt-3 md:mt-0 px-6 py-2 rounded-full text-white bg-slate-600 hover:bg-slate-700 transition-colors duration-300">
            &larr; Kembali ke Dasbor
        </a>
    </header>

    <hr class="my-6 border-t border-slate-300">

    <section class="bg-white/40 backdrop-blur-lg rounded-xl shadow-xl p-4 md:p-6 overflow-hidden fade-in">
        <div id="chat-box" class="h-[500px] overflow-y-auto pr-2 custom-scrollbar">
            @php
                $myPhoneNumber = "6282284571236"; // Ganti dengan nomor Anda
            @endphp

            @forelse ($conversationLogs as $log)
                @php
                    // Logika yang diperbaiki: HANYA PERIKSA NOMOR TELEPON.
                    $isMyMessage = ($log['phone'] == $myPhoneNumber);
                    $bubbleClass = $isMyMessage ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-slate-200 text-slate-800 rounded-tl-none';
                    $textColor = $isMyMessage ? 'text-white/80' : 'text-slate-500';
                    $justifyClass = $isMyMessage ? 'justify-end' : 'justify-start';
                @endphp
                <div class="flex mb-4 {{ $justifyClass }}">
                    <div class="max-w-[70%] rounded-xl p-3 shadow-md {{ $bubbleClass }}">
                        <p class="mb-1 text-sm md:text-base">{{ $log['message'] }}</p>
                        <small class="block text-right text-xs {{ $textColor }}">{{ \Carbon\Carbon::createFromTimestamp($log['timestamp'])->format('H:i') }}</small>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center h-full text-slate-500">
                    <p>Belum ada percakapan.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mt-6 fade-in">
        <div class="bg-white/40 backdrop-blur-lg rounded-xl shadow-xl p-4">
            <h3 class="font-semibold mb-3 text-lg text-slate-800">Balas Pesan</h3>
            @if (session('success'))
                <div class="p-3 mb-3 text-sm text-green-800 rounded-lg bg-green-50">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-3 mb-3 text-sm text-red-800 rounded-lg bg-red-50">{{ session('error') }}</div>
            @endif
            <form action="{{ route('reply.message') }}" method="POST" class="flex items-end space-x-2">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                <textarea class="flex-1 min-h-[40px] p-3 rounded-xl border border-slate-300 focus:outline-none focus:border-blue-500 resize-none overflow-hidden" name="message" rows="1" placeholder="Ketik balasan Anda..." required></textarea>
                <button type="submit" class="self-end px-6 py-3 rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-300 focus:outline-none">
                    Kirim
                </button>
            </form>
        </div>
    </section>
</main>

<script>
    // Scroll to the bottom of the chat box on page load
    document.addEventListener('DOMContentLoaded', function() {
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;

        // Auto-resize the textarea
        const textarea = document.querySelector('textarea[name="message"]');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>

<style>
    /* Custom scrollbar for webkit browsers (Chrome, Safari) */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }
</style>
@endsection
