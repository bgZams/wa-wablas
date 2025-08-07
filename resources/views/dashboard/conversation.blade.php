@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h1>Percakapan dengan {{ $phone }}</h1>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary mb-3">Kembali ke Dasbor</a>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body" style="height: 500px; overflow-y: scroll;">
                @forelse ($conversationLogs as $log)
                <div class="message-container mb-3 d-flex @if($log['type'] == 'outgoing') justify-content-end @else justify-content-start @endif">
                    <div class="card @if($log['type'] == 'outgoing') bg-primary text-white @else bg-light @endif" style="max-width: 70%;">
                        <div class="card-body p-2">
                            <p class="mb-0">{{ $log['message'] }}</p>
                            <small class="d-block text-end @if($log['type'] == 'outgoing') text-light @else text-muted @endif">{{ \Carbon\Carbon::createFromTimestamp($log['timestamp'])->format('H:i') }}</small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted">Belum ada percakapan.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                Balas Pesan
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <form action="{{ route('reply.message') }}" method="POST">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">
                    <div class="input-group">
                        <textarea class="form-control" name="message" rows="1" placeholder="Ketik balasan Anda..." required></textarea>
                        <button class="btn btn-primary" type="submit">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
