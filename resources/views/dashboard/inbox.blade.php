@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h1>Dashboard Bot WhatsApp</h1>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Pesan Terkirim</div>
            <div class="card-body">
                <h5 class="card-title">{{ $totalSent }}</h5>
                <p class="card-text">Total pesan yang berhasil dikirim.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Pesan Diterima</div>
            <div class="card-body">
                <h5 class="card-title">{{ $totalReceived }}</h5>
                <p class="card-text">Total pesan masuk yang diproses.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">Pesan Gagal</div>
            <div class="card-body">
                <h5 class="card-title">{{ $totalFailed }}</h5>
                <p class="card-text">Total pesan yang gagal dikirim.</p>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                Kotak Masuk (Inbox)
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse ($uniqueContacts as $contact)
                    <li class="list-group-item">
                        <a href="{{ route('conversation', ['phone' => $contact['phone']]) }}">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Kontak: {{ $contact['phone'] }}</h5>
                                <small>{{ \Carbon\Carbon::createFromTimestamp($contact['timestamp'])->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1">{{ Str::limit($contact['latest_message'], 100) }}</p>
                        </a>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">Belum ada pesan masuk.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
