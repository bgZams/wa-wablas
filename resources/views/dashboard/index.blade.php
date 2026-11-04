<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WA Bot — WhatsApp Web</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        :root{
            --wa-green:#00a884;
            --wa-green-dark:#008069;
            --wa-panel:#f0f2f5;
            --wa-panel-dark:#111b21;
            --wa-chat-bg:#efeae2;
            --wa-bubble-out:#d9fdd3;
            --wa-bubble-in:#ffffff;
            --wa-border:#e9edef;
            --wa-text:#111b21;
            --wa-muted:#667781;
            --wa-hover:#f5f6f6;
            --wa-active:#f0f2f5;
        }
        *{font-family:'Inter',-apple-system,sans-serif}
        body{margin:0;background:#d1d7db;color:var(--wa-text);overflow:hidden}
        .wa-root{display:flex;height:100vh;width:100vw;background:#d1d7db;position:relative}
        .wa-root::before{content:'';position:absolute;top:0;left:0;right:0;height:127px;background:var(--wa-green-dark);z-index:0}
        .wa-shell{position:relative;z-index:1;display:flex;width:100%;max-width:1600px;margin:19px auto;border-radius:0;box-shadow:0 1px 3px rgba(11,20,26,.08),0 2px 12px rgba(11,20,26,.13);overflow:hidden;height:calc(100vh - 38px);background:#fff}
        @media (max-width:1024px){.wa-shell{margin:0;height:100vh;max-width:100%}.wa-root::before{display:none}}

        /* Sidebar */
        .wa-sidebar{width:30%;min-width:320px;max-width:420px;border-right:1px solid var(--wa-border);display:flex;flex-direction:column;background:#fff}
        .wa-sidebar-header{height:59px;padding:10px 16px;background:var(--wa-panel);display:flex;align-items:center;justify-content:space-between}
        .wa-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128c7e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;flex-shrink:0;overflow:hidden}
        .wa-icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#54656f;cursor:pointer;transition:background .15s;border:none;background:transparent}
        .wa-icon-btn:hover{background:rgba(134,150,160,.15)}
        .wa-tabs{display:flex;gap:2px;padding:8px 12px;background:#fff;border-bottom:1px solid var(--wa-border);overflow-x:auto;scrollbar-width:none}
        .wa-tabs::-webkit-scrollbar{display:none}
        .wa-tab{padding:6px 14px;border-radius:999px;font-size:13px;font-weight:500;color:#54656f;cursor:pointer;white-space:nowrap;transition:all .15s;border:1px solid transparent}
        .wa-tab:hover{background:var(--wa-panel)}
        .wa-tab.active{background:#d9fdd3;color:#027d52;border-color:#d9fdd3}
        .wa-search{padding:8px 12px;background:#fff}
        .wa-search-box{background:var(--wa-panel);border-radius:8px;display:flex;align-items:center;padding:8px 12px;gap:12px}
        .wa-search-box input{flex:1;border:none;background:transparent;outline:none;font-size:14px;color:var(--wa-text)}
        .wa-contact-list{flex:1;overflow-y:auto}
        .wa-contact{display:flex;align-items:center;padding:10px 16px;cursor:pointer;gap:12px;border-bottom:1px solid var(--wa-border);transition:background .12s}
        .wa-contact:hover{background:var(--wa-hover)}
        .wa-contact.active{background:var(--wa-active)}
        .wa-contact-body{flex:1;min-width:0}
        .wa-contact-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:3px}
        .wa-contact-name{font-weight:500;font-size:15px;color:var(--wa-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .wa-contact-time{font-size:11px;color:var(--wa-muted);flex-shrink:0;margin-left:8px}
        .wa-contact-preview{font-size:13px;color:var(--wa-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .wa-badge{background:var(--wa-green);color:#fff;border-radius:999px;padding:2px 7px;font-size:11px;font-weight:600}

        /* Chat panel */
        .wa-main{flex:1;display:flex;flex-direction:column;background:var(--wa-chat-bg);position:relative}
        .wa-main-bg{position:absolute;inset:0;opacity:.06;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath d='M20 20h60v60H20z' fill='none' stroke='%23000' stroke-width='1'/%3E%3C/svg%3E");pointer-events:none}
        .wa-chat-header{height:59px;padding:10px 16px;background:var(--wa-panel);display:flex;align-items:center;justify-content:space-between;gap:12px;border-left:1px solid var(--wa-border);position:relative;z-index:1;flex-shrink:0}
        .wa-chat-title{font-weight:500;font-size:16px;color:var(--wa-text)}
        .wa-chat-sub{font-size:12px;color:var(--wa-muted)}
        .wa-messages{flex:1;overflow-y:auto;padding:20px 5% 12px;position:relative;z-index:1}
        .wa-msg-row{display:flex;margin-bottom:4px}
        .wa-msg-row.out{justify-content:flex-end}
        .wa-msg{max-width:65%;padding:6px 8px 8px 10px;border-radius:8px;position:relative;box-shadow:0 1px .5px rgba(11,20,26,.13);font-size:14.2px;line-height:19px;color:var(--wa-text);word-wrap:break-word;white-space:pre-wrap}
        .wa-msg.in{background:var(--wa-bubble-in);border-top-left-radius:0}
        .wa-msg.out{background:var(--wa-bubble-out);border-top-right-radius:0}
        .wa-msg-meta{display:inline-flex;align-items:center;gap:3px;font-size:11px;color:var(--wa-muted);float:right;margin-left:8px;margin-top:6px}
        .wa-msg-meta .check{color:#53bdeb}
        .wa-date-divider{text-align:center;margin:16px 0;position:relative}
        .wa-date-divider span{background:#e1f2fa;color:#54656f;font-size:12px;padding:5px 12px;border-radius:8px;box-shadow:0 1px .5px rgba(11,20,26,.13)}
        .wa-composer{padding:10px 16px;background:var(--wa-panel);display:flex;align-items:flex-end;gap:10px;position:relative;z-index:1;flex-shrink:0}
        .wa-composer-input{flex:1;background:#fff;border-radius:8px;padding:9px 12px;font-size:15px;line-height:20px;max-height:120px;min-height:20px;outline:none;border:none;resize:none;color:var(--wa-text)}
        .wa-send-btn{width:42px;height:42px;border-radius:50%;background:var(--wa-green);color:#fff;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all .15s}
        .wa-send-btn:hover{background:var(--wa-green-dark)}
        .wa-send-btn:disabled{opacity:.5;cursor:not-allowed}

        /* Welcome screen */
        .wa-welcome{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--wa-panel);padding:40px;border-left:1px solid var(--wa-border)}
        .wa-welcome-icon{width:280px;height:280px;max-width:60%;opacity:.7;margin-bottom:20px}
        .wa-welcome h2{font-size:32px;font-weight:300;color:#41525d;margin-bottom:12px;text-align:center}
        .wa-welcome p{color:var(--wa-muted);font-size:14px;text-align:center;max-width:560px;line-height:20px}

        /* Right panel (features) */
        .wa-rightbar{width:380px;border-left:1px solid var(--wa-border);background:#fff;display:flex;flex-direction:column;overflow:hidden;transform:translateX(100%);transition:transform .25s ease;position:absolute;right:0;top:0;bottom:0;z-index:10;box-shadow:-4px 0 16px rgba(0,0,0,.08)}
        .wa-rightbar.open{transform:translateX(0)}
        .wa-rightbar-header{height:59px;padding:10px 16px;background:var(--wa-panel);display:flex;align-items:center;gap:16px;flex-shrink:0}
        .wa-rightbar-body{flex:1;overflow-y:auto;padding:16px}
        .wa-section-title{font-size:13px;font-weight:600;color:#667781;text-transform:uppercase;letter-spacing:.5px;margin:14px 0 8px}
        .wa-field{display:block;margin-bottom:10px}
        .wa-field label{display:block;font-size:12px;color:#54656f;margin-bottom:4px;font-weight:500}
        .wa-field input, .wa-field select, .wa-field textarea{width:100%;padding:9px 11px;border:1px solid #e9edef;border-radius:6px;font-size:14px;outline:none;background:#fff;transition:border-color .15s}
        .wa-field input:focus, .wa-field select:focus, .wa-field textarea:focus{border-color:var(--wa-green)}
        .wa-btn{padding:9px 14px;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;border:none;transition:all .15s;display:inline-flex;align-items:center;gap:6px}
        .wa-btn-primary{background:var(--wa-green);color:#fff}
        .wa-btn-primary:hover{background:var(--wa-green-dark)}
        .wa-btn-ghost{background:var(--wa-panel);color:#54656f}
        .wa-btn-ghost:hover{background:#e5e7ea}
        .wa-btn-danger{background:#dc2626;color:#fff}
        .wa-card{background:#f7f8fa;border:1px solid #eef1f3;border-radius:8px;padding:12px;margin-bottom:10px}
        .wa-stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px}
        .wa-stat{background:var(--wa-panel);border-radius:8px;padding:10px}
        .wa-stat-val{font-size:22px;font-weight:600;color:var(--wa-text)}
        .wa-stat-label{font-size:11px;color:var(--wa-muted);text-transform:uppercase;letter-spacing:.4px}
        .wa-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#111b21;color:#fff;padding:10px 18px;border-radius:8px;font-size:14px;z-index:1000;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s}
        .wa-toast.show{opacity:1;transform:translateX(-50%) translateY(-4px)}
        .wa-switch{position:relative;display:inline-block;width:38px;height:22px}
        .wa-switch input{opacity:0;width:0;height:0}
        .wa-switch-slider{position:absolute;inset:0;background:#ccc;border-radius:22px;cursor:pointer;transition:.2s}
        .wa-switch-slider:before{content:"";position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
        .wa-switch input:checked + .wa-switch-slider{background:var(--wa-green)}
        .wa-switch input:checked + .wa-switch-slider:before{transform:translateX(16px)}
        .wa-modal{position:fixed;inset:0;background:rgba(11,20,26,.6);display:none;align-items:center;justify-content:center;z-index:100;padding:16px}
        .wa-modal.show{display:flex}
        .wa-modal-box{background:#fff;border-radius:12px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
        .wa-modal-header{padding:14px 18px;border-bottom:1px solid var(--wa-border);display:flex;justify-content:space-between;align-items:center}
        .wa-modal-body{padding:18px}
        .wa-dropdown-menu{position:absolute;top:48px;right:16px;background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(11,20,26,.2);z-index:20;min-width:220px;overflow:hidden;display:none}
        .wa-dropdown-menu.open{display:block}
        .wa-dropdown-item{padding:10px 16px;font-size:14px;color:var(--wa-text);cursor:pointer;display:flex;align-items:center;gap:12px}
        .wa-dropdown-item:hover{background:var(--wa-hover)}

        /* Scrollbars */
        ::-webkit-scrollbar{width:6px;height:6px}
        ::-webkit-scrollbar-thumb{background:rgba(0,0,0,.15);border-radius:3px}
        ::-webkit-scrollbar-thumb:hover{background:rgba(0,0,0,.3)}

        /* Responsive */
        .wa-back-btn{display:none}
        @media (max-width:768px){
            .wa-sidebar{width:100%;min-width:0;max-width:100%}
            .wa-main, .wa-welcome{display:none}
            .wa-root.chat-open .wa-sidebar{display:none}
            .wa-root.chat-open .wa-main{display:flex}
            .wa-back-btn{display:flex}
            .wa-rightbar{width:100%}
            .wa-msg{max-width:85%}
        }

        .status-pending{color:#9ca3af}
        .status-sent{color:#53bdeb}
        .status-failed{color:#dc2626}
        .typing{display:inline-flex;gap:3px}
        .typing span{width:5px;height:5px;border-radius:50%;background:#8696a0;animation:typ 1.2s infinite}
        .typing span:nth-child(2){animation-delay:.15s}
        .typing span:nth-child(3){animation-delay:.3s}
        @keyframes typ{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-4px)}}
        .spin{animation:spn 1s linear infinite}
        @keyframes spn{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
<div class="wa-root" id="waRoot">
    <div class="wa-shell">

        <!-- SIDEBAR -->
        <aside class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-avatar"><i class="fab fa-whatsapp text-xl"></i></div>
                <div class="flex items-center gap-1">
                    <button class="wa-icon-btn" title="Pesan Terjadwal" onclick="openPanel('scheduled')">
                        <i class="far fa-clock"></i>
                    </button>
                    <button class="wa-icon-btn" title="Sinkronisasi" onclick="syncMessages()">
                        <i class="fas fa-sync-alt" id="syncIcon"></i>
                    </button>
                    <button class="wa-icon-btn" title="Menu" onclick="toggleMenu(event)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="wa-dropdown-menu" id="mainMenu">
                        <div class="wa-dropdown-item" onclick="openPanel('newchat')"><i class="fas fa-pen w-4"></i>Pesan Baru</div>
                        <div class="wa-dropdown-item" onclick="openPanel('location')"><i class="fas fa-map-marker-alt w-4"></i>Kirim Lokasi</div>
                        <div class="wa-dropdown-item" onclick="openPanel('link')"><i class="fas fa-link w-4"></i>Kirim Link Preview</div>
                        <div class="wa-dropdown-item" onclick="openPanel('check')"><i class="fas fa-search w-4"></i>Cek Status Nomor</div>
                        <div class="wa-dropdown-item" onclick="openPanel('group')"><i class="fas fa-users w-4"></i>Kirim Grup Kontak</div>
                        <div class="wa-dropdown-item" onclick="openPanel('import')"><i class="fas fa-file-import w-4"></i>Import Kontak</div>
                        <div class="wa-dropdown-item" onclick="openPanel('autoreply')"><i class="fas fa-robot w-4"></i>Auto Reply (Quick)</div>
                        <a href="{{ route('autoreply.manage') }}" class="wa-dropdown-item"><i class="fas fa-reply-all w-4"></i>Kelola Auto-Reply (Penuh)</a>
                        <div class="wa-dropdown-item" onclick="openPanel('webhook')"><i class="fas fa-plug w-4"></i>Webhook</div>
                        <div class="wa-dropdown-item" onclick="openPanel('stats')"><i class="fas fa-chart-bar w-4"></i>Statistik</div>
                        <a href="{{ route('report.contacts_messages') }}" class="wa-dropdown-item"><i class="fas fa-download w-4"></i>Download Laporan</a>
                        <a href="{{ route('usage') }}" class="wa-dropdown-item"><i class="fas fa-book w-4"></i>Panduan Penggunaan</a>
                        <div class="wa-dropdown-item" style="color:#dc2626" onclick="logout()"><i class="fas fa-sign-out-alt w-4"></i>Keluar</div>
                    </div>
                </div>
            </div>

            <div class="wa-tabs">
                <div class="wa-tab active" data-filter="all">Semua</div>
                <div class="wa-tab" data-filter="unread">Belum Dibaca</div>
                <div class="wa-tab" data-filter="groups">Grup</div>
                <div class="wa-tab" data-filter="scheduled" onclick="openPanel('scheduled')">
                    <i class="far fa-clock mr-1"></i>Terjadwal <span class="wa-badge ml-1" id="schedBadge">{{ $stats['total_scheduled'] }}</span>
                </div>
            </div>

            <div class="wa-search">
                <div class="wa-search-box">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                    <input type="text" id="contactSearch" placeholder="Cari atau mulai chat baru">
                </div>
            </div>

            <div class="wa-contact-list" id="contactList"></div>
        </aside>

        <!-- MAIN CHAT -->
        <main class="wa-main" id="chatMain" style="display:none">
            <div class="wa-main-bg"></div>
            <div class="wa-chat-header">
                <div class="flex items-center gap-3">
                    <button class="wa-icon-btn wa-back-btn" onclick="closeChatMobile()"><i class="fas fa-arrow-left"></i></button>
                    <div class="wa-avatar" id="chatAvatar"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="wa-chat-title" id="chatName">-</div>
                        <div class="wa-chat-sub" id="chatPhone">-</div>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button class="wa-icon-btn" title="Jadwalkan" onclick="openScheduleForActive()"><i class="far fa-clock"></i></button>
                    <button class="wa-icon-btn" title="Kirim Lokasi" onclick="openPanel('location', activePhone)"><i class="fas fa-map-marker-alt"></i></button>
                    <button class="wa-icon-btn" title="Refresh" onclick="loadConversation(activePhone, true)"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>

            <div class="wa-messages" id="messages"></div>

            <form class="wa-composer" id="composer" onsubmit="return sendReply(event)">
                <button type="button" class="wa-icon-btn" onclick="toggleEmoji()"><i class="far fa-smile"></i></button>
                <button type="button" class="wa-icon-btn" onclick="openPanel('link', activePhone)" title="Kirim link"><i class="fas fa-link"></i></button>
                <textarea class="wa-composer-input" id="msgInput" rows="1" placeholder="Ketik pesan"></textarea>
                <button type="button" class="wa-icon-btn" onclick="openScheduleForActive()" title="Jadwalkan"><i class="far fa-clock"></i></button>
                <button type="submit" class="wa-send-btn" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
            </form>
        </main>

        <!-- WELCOME SCREEN -->
        <section class="wa-welcome" id="welcome">
            <svg class="wa-welcome-icon" viewBox="0 0 303 172"><path fill="#DAF7DC" d="M229.2 160H74.8c-9.8 0-17.7-8-17.7-17.8V29.8c0-9.8 7.9-17.8 17.7-17.8h154.4c9.8 0 17.7 8 17.7 17.8v112.4c0 9.8-7.9 17.8-17.7 17.8z"/><path fill="#FFF" d="M193.3 77.6H110.7c-3 0-5.4-2.4-5.4-5.4s2.4-5.4 5.4-5.4h82.6c3 0 5.4 2.4 5.4 5.4s-2.4 5.4-5.4 5.4zM153.8 105.2h-43.1c-3 0-5.4-2.4-5.4-5.4s2.4-5.4 5.4-5.4h43.1c3 0 5.4 2.4 5.4 5.4s-2.4 5.4-5.4 5.4z"/></svg>
            <h2>WA Bot — WhatsApp Web</h2>
            <p>Kelola percakapan, kirim pesan terjadwal, broadcast grup, cek status nomor, dan atur auto-reply — semua dari satu dasbor. Pilih kontak untuk mulai chat.</p>
            <div class="wa-stat-grid mt-8" style="max-width:480px;width:100%">
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_sent'] }}</div><div class="wa-stat-label">Terkirim</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_received'] }}</div><div class="wa-stat-label">Diterima</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_contacts'] }}</div><div class="wa-stat-label">Kontak</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_scheduled'] }}</div><div class="wa-stat-label">Terjadwal</div></div>
            </div>
            <p class="mt-6 text-xs">📦 Pesan Tak Terbatas · 🗓️ Riwayat 5 Hari · 🔔 Webhook Tracking · 🤖 Auto Reply</p>
        </section>

        <!-- RIGHT PANEL -->
        <aside class="wa-rightbar" id="rightbar">
            <div class="wa-rightbar-header">
                <button class="wa-icon-btn" onclick="closePanel()"><i class="fas fa-arrow-left"></i></button>
                <div class="font-medium" id="panelTitle">Panel</div>
            </div>
            <div class="wa-rightbar-body" id="panelBody"></div>
        </aside>

    </div>
</div>

<div class="wa-toast" id="toast"></div>

<!-- Schedule Modal -->
<div class="wa-modal" id="schedModal">
    <div class="wa-modal-box">
        <div class="wa-modal-header">
            <div class="font-semibold flex items-center gap-2"><i class="far fa-clock"></i>Jadwalkan Pesan</div>
            <button class="wa-icon-btn" onclick="closeSchedModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="wa-modal-body">
            <div class="wa-field"><label>Nomor / ID Grup</label><input type="text" id="schedPhone" placeholder="628xxxxxxx"></div>
            <div class="wa-field">
                <label class="flex items-center gap-2"><input type="checkbox" id="schedGroup"> Kirim ke Grup</label>
            </div>
            <div class="wa-field"><label>Jenis</label>
                <select id="schedKind" onchange="toggleSchedKind()">
                    <option value="text">Teks</option>
                    <option value="location">Lokasi</option>
                    <option value="link">Link Preview</option>
                </select>
            </div>
            <div class="wa-field" id="fldText"><label>Pesan</label><textarea id="schedMsg" rows="3"></textarea></div>
            <div class="wa-field hidden" id="fldLat"><label>Latitude</label><input type="text" id="schedLat"></div>
            <div class="wa-field hidden" id="fldLng"><label>Longitude</label><input type="text" id="schedLng"></div>
            <div class="wa-field hidden" id="fldUrl"><label>URL</label><input type="url" id="schedUrl"></div>
            <div class="wa-field"><label>Waktu Pengiriman</label><input type="datetime-local" id="schedAt"></div>
            <div class="flex gap-2 mt-4">
                <button class="wa-btn wa-btn-primary flex-1" onclick="saveSchedule()"><i class="fas fa-check"></i>Jadwalkan</button>
                <button class="wa-btn wa-btn-ghost" onclick="closeSchedModal()">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    let CSRF = document.querySelector('meta[name="csrf-token"]').content;
    async function refreshCsrf(){
        try{
            const r = await fetch('/csrf-token',{credentials:'same-origin',headers:{'Accept':'application/json'}});
            if(r.ok){const d = await r.json(); CSRF = d.token; return true}
        }catch(e){}
        return false;
    }
    let contacts = @json($contacts);
    let autoReplies = @json($autoReplies);
    let scheduled = @json($scheduled);
    let settings = @json($settings);
    const WEBHOOK_URL = @json($webhookUrl);
    let activePhone = null;
    let messagesCache = {};
    let currentFilter = 'all';
    let searchQuery = '';

    // ============ helpers ============
    function toast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2400)}
    function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
    function linkify(s){return escapeHtml(s).replace(/(https?:\/\/[^\s]+)/g,'<a href="$1" target="_blank" class="text-blue-600 underline">$1</a>')}
    async function rawFetch(url, opts){
        return fetch(url, {
            credentials: 'same-origin',
            ...opts,
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(opts.headers || {}),
            },
        });
    }
    async function api(url, opts={}){
        let r = await rawFetch(url, opts);
        if(r.status === 419){
            // CSRF expired — refresh & retry once
            const ok = await refreshCsrf();
            if(ok) r = await rawFetch(url, opts);
        }
        if(!r.ok && r.status !== 422){
            let detail = '';
            try{ detail = (await r.clone().text()).slice(0,200) }catch(e){}
            throw new Error('HTTP ' + r.status + (detail ? ' — ' + detail : ''));
        }
        return r.json();
    }
    function postJson(url,body){return api(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})}

    // ============ contacts ============
    function renderContacts(){
        const list = document.getElementById('contactList');
        const q = searchQuery.toLowerCase();
        const filtered = contacts.filter(c=>{
            if(currentFilter==='groups' && !c.is_group) return false;
            if(q && !(c.name||'').toLowerCase().includes(q) && !(c.phone||'').includes(q)) return false;
            return true;
        });
        if(!filtered.length){
            list.innerHTML = '<div class="p-6 text-center text-sm text-gray-400">Tidak ada kontak</div>';
            return;
        }
        list.innerHTML = filtered.map(c => `
            <div class="wa-contact ${c.phone===activePhone?'active':''}" onclick="openChat('${c.phone}')">
                <div class="wa-avatar">${c.is_group?'<i class="fas fa-users"></i>':'<i class="fas fa-user"></i>'}</div>
                <div class="wa-contact-body">
                    <div class="wa-contact-top">
                        <div class="wa-contact-name">${escapeHtml(c.name||c.phone)}</div>
                        <div class="wa-contact-time">${c.time_label||''}</div>
                    </div>
                    <div class="wa-contact-preview">
                        ${c.latest_type==='outgoing'?'<i class="fas fa-check text-xs mr-1"></i>':''}
                        ${escapeHtml((c.latest_message||'').slice(0,60))}
                    </div>
                </div>
            </div>
        `).join('');
    }

    document.getElementById('contactSearch').addEventListener('input', e=>{searchQuery=e.target.value;renderContacts()});
    document.querySelectorAll('.wa-tab[data-filter]').forEach(t => t.addEventListener('click', e=>{
        if(t.dataset.filter==='scheduled') return;
        document.querySelectorAll('.wa-tab').forEach(x=>x.classList.remove('active'));
        t.classList.add('active');
        currentFilter = t.dataset.filter;
        renderContacts();
    }));

    // ============ chat ============
    async function openChat(phone){
        activePhone = phone;
        document.getElementById('welcome').style.display = 'none';
        document.getElementById('chatMain').style.display = 'flex';
        document.getElementById('waRoot').classList.add('chat-open');
        const c = contacts.find(x=>x.phone===phone);
        document.getElementById('chatName').textContent = c?.name || phone;
        document.getElementById('chatPhone').textContent = phone + (c?.is_group?' · Grup':'');
        document.getElementById('chatAvatar').innerHTML = c?.is_group ? '<i class="fas fa-users"></i>' : '<i class="fas fa-user"></i>';
        renderContacts();
        await loadConversation(phone);
    }

    function closeChatMobile(){
        document.getElementById('waRoot').classList.remove('chat-open');
    }

    async function loadConversation(phone, silent=false){
        if(!silent) document.getElementById('messages').innerHTML = '<div class="text-center py-8 text-gray-400 text-sm"><div class="typing inline-flex"><span></span><span></span><span></span></div></div>';
        try{
            const data = await api(`/conversation/${phone}`);
            const oldMsgs = messagesCache[phone] || [];
            const newMsgs = data.messages || [];

            // Hanya re-render jika ada pesan baru atau perubahan status untuk menghindari flicker
            const isDifferent = oldMsgs.length !== newMsgs.length || 
                (newMsgs.length > 0 && oldMsgs.length > 0 && (
                    oldMsgs[oldMsgs.length-1].id !== newMsgs[newMsgs.length-1].id ||
                    oldMsgs[oldMsgs.length-1].status !== newMsgs[newMsgs.length-1].status
                ));

            if(isDifferent || !silent){
                messagesCache[phone] = newMsgs;
                renderMessages(phone);
            }
        }catch(e){if(!silent) toast('Gagal memuat pesan')}
    }

    function renderMessages(phone){
        const el = document.getElementById('messages');
        const msgs = (messagesCache[phone] || []).slice().sort((a,b)=>{
            if(a.timestamp !== b.timestamp) return a.timestamp - b.timestamp;
            // Jika timestamp tie → incoming (user) selalu tampil di atas outgoing (bot)
            const wa = a.type === 'incoming' ? 0 : 1;
            const wb = b.type === 'incoming' ? 0 : 1;
            if(wa !== wb) return wa - wb;
            return (a.id||0) - (b.id||0);
        });
        if(!msgs.length){
            el.innerHTML = '<div class="text-center py-12 text-gray-400 text-sm">Belum ada pesan. Mulai percakapan.</div>';
            return;
        }
        let lastDate = null, html = '';
        msgs.forEach(m=>{
            if(m.date !== lastDate){
                html += `<div class="wa-date-divider"><span>${m.date_label}</span></div>`;
                lastDate = m.date;
            }
            const check = m.type==='outgoing' ? (
                m.status==='sent' ? '<i class="fas fa-check-double check"></i>' :
                m.status==='failed' ? '<i class="fas fa-exclamation-circle status-failed"></i>' :
                '<i class="fas fa-clock status-pending"></i>'
            ) : '';
            let body = linkify(m.message);
            if(m.kind==='image' && m.metadata?.url){
                body = `<div class="mb-1"><a href="${m.metadata.url}" target="_blank"><img src="${m.metadata.url}" class="rounded-lg max-w-full max-h-60 object-cover cursor-pointer hover:opacity-95 shadow-sm" alt="Foto WhatsApp" loading="lazy"></a></div>` + (m.message && m.message !== '📷 [Foto / Gambar]' ? `<div>${body}</div>` : '');
            } else if(m.kind==='location' && (m.metadata?.latitude || m.metadata?.lat)){
                const lat = m.metadata.latitude || m.metadata.lat;
                const lng = m.metadata.longitude || m.metadata.lng;
                body = `<a class="text-blue-600 underline font-medium" target="_blank" href="https://maps.google.com/?q=${lat},${lng}"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Lihat Lokasi di Google Maps</a><br>${body}`;
            } else if((m.kind==='audio' || m.kind==='voice') && m.metadata?.url){
                body = `<div class="my-1"><audio controls class="max-w-full"><source src="${m.metadata.url}" type="${m.metadata.mimetype||'audio/ogg'}"></audio></div>` + (m.message && !m.message.includes('[Pesan Suara]') ? `<div>${body}</div>` : '');
            } else if(m.kind==='video' && m.metadata?.url){
                body = `<div class="my-1"><video controls class="max-w-full max-h-60 rounded-lg"><source src="${m.metadata.url}" type="${m.metadata.mimetype||'video/mp4'}"></video></div>` + (m.message && !m.message.includes('[Video]') ? `<div>${body}</div>` : '');
            } else if(m.kind==='document' && m.metadata?.url){
                body = `<div class="my-1"><a href="${m.metadata.url}" target="_blank" class="flex items-center gap-2 p-2 bg-gray-100 rounded text-blue-700 underline font-medium"><i class="fas fa-file-alt text-lg"></i> Unduh Dokumen (${m.metadata.filename||'File'})</a></div>` + (m.message && !m.message.includes('[Dokumen]') ? `<div>${body}</div>` : '');
            }
            html += `<div class="wa-msg-row ${m.type==='outgoing'?'out':''}"><div class="wa-msg ${m.type==='outgoing'?'out':'in'}">${body}<span class="wa-msg-meta">${m.time} ${check}</span></div></div>`;
        });
        el.innerHTML = html;
        el.scrollTop = el.scrollHeight;
    }

    async function sendReply(e){
        e.preventDefault();
        const input = document.getElementById('msgInput');
        const text = input.value.trim();
        if(!text || !activePhone) return false;
        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        try{
            const c = contacts.find(x=>x.phone===activePhone);
            const res = await postJson('/reply', {phone: activePhone, message: text, is_group: c?.is_group?1:0});
            console.log('sendReply response', res);
            if(res.success){
                input.value='';autoresize(input);
                await loadConversation(activePhone, true);
                await refreshContacts();
            }else toast('Gagal kirim pesan');
        }catch(e){toast('Error: '+e.message)}
        btn.disabled=false;
        return false;
    }

    const msgInput = document.getElementById('msgInput');
    msgInput.addEventListener('keydown', e=>{
        if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); document.getElementById('composer').requestSubmit(); }
    });
    msgInput.addEventListener('input', e=>autoresize(e.target));
    function autoresize(t){t.style.height='20px';t.style.height=Math.min(t.scrollHeight,120)+'px'}

    async function refreshContacts(){
        try{
            const data = await api('/contacts');
            const oldJson = JSON.stringify(contacts.map(c=>({p:c.phone,m:c.latest_message,t:c.timestamp})));
            const newJson = JSON.stringify(data.map(c=>({p:c.phone,m:c.latest_message,t:c.timestamp})));
            if(oldJson !== newJson){
                contacts = data;
                renderContacts();
            }
        }catch(e){}
    }

    async function syncMessages(){
        const icon = document.getElementById('syncIcon');
        icon.classList.add('spin');
        try{
            const res = await postJson('/dashboard/sync', {});
            if(res.success){
                toast(`Sinkron: ${res.synced} baru, ${res.updated||0} diperbarui, ${res.skipped} dilewati`);
                await refreshContacts();
                if(activePhone) await loadConversation(activePhone, true);
            } else {
                const err = res.error || 'respons API tidak valid';
                toast('Sinkron gagal: ' + (typeof err === 'string' ? err : JSON.stringify(err)).slice(0,140));
                console.error('Sync error detail:', res);
            }
        }catch(e){
            toast('Gagal sinkron: ' + e.message);
            console.error(e);
        }
        icon.classList.remove('spin');
    }

    // ============ menus / panels ============
    function toggleMenu(e){e.stopPropagation();document.getElementById('mainMenu').classList.toggle('open')}
    document.addEventListener('click', ()=>document.getElementById('mainMenu').classList.remove('open'));

    const panels = {
        newchat: {title:'Pesan Baru', render:()=>`
            <div class="wa-field"><label>Nomor Tujuan</label><input type="text" id="newPhone" placeholder="628xxxxxxx"></div>
            <div class="wa-field"><label class="flex items-center gap-2"><input type="checkbox" id="newGroup"> Kirim ke Grup</label></div>
            <div class="wa-field"><label>Pesan</label><textarea id="newMsg" rows="4"></textarea></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="sendNewChat()"><i class="fas fa-paper-plane"></i>Kirim Sekarang</button>`},
        location: {title:'Kirim Lokasi', render:(phone)=>`
            <div class="wa-field"><label>Nomor Tujuan</label><input type="text" id="locPhone" value="${phone||''}"></div>
            <div class="grid grid-cols-2 gap-2">
                <div class="wa-field"><label>Latitude</label><input type="text" id="locLat" placeholder="-6.2088"></div>
                <div class="wa-field"><label>Longitude</label><input type="text" id="locLng" placeholder="106.8456"></div>
            </div>
            <div class="wa-field"><label>Caption (opsional)</label><textarea id="locCap" rows="2"></textarea></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="sendLocation()"><i class="fas fa-map-marker-alt"></i>Kirim Lokasi</button>`},
        link: {title:'Kirim Link Preview', render:(phone)=>`
            <div class="wa-field"><label>Nomor Tujuan</label><input type="text" id="lkPhone" value="${phone||''}"></div>
            <div class="wa-field"><label>URL</label><input type="url" id="lkUrl" placeholder="https://..."></div>
            <div class="wa-field"><label>Pesan (opsional)</label><textarea id="lkMsg" rows="2"></textarea></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="sendLink()"><i class="fas fa-link"></i>Kirim Link</button>`},
        check: {title:'Cek Status Nomor WhatsApp', render:()=>`
            <div class="wa-field"><label>Nomor</label><input type="text" id="chkPhone" placeholder="628xxxxxxx"></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="checkNumber()"><i class="fas fa-search"></i>Cek Status</button>
            <div id="chkResult" class="mt-4"></div>`},
        group: {title:'Kirim ke Grup Kontak', render:()=>{
            const tags = [...new Set(contacts.map(c=>c.group_tag).filter(Boolean))];
            return `
            <p class="text-xs text-gray-500 mb-3">Broadcast pesan ke semua kontak dengan tag grup tertentu.</p>
            <div class="wa-field"><label>Tag Grup</label>
                <select id="grpTag">${tags.map(t=>`<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('')}</select>
                ${tags.length?'':'<p class="text-xs text-red-500 mt-1">Belum ada kontak bertag. Gunakan Import Kontak.</p>'}
            </div>
            <div class="wa-field"><label>Pesan</label><textarea id="grpMsg" rows="4"></textarea></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="sendByGroup()" ${tags.length?'':'disabled'}><i class="fas fa-users"></i>Kirim Broadcast</button>`}},
        import: {title:'Import Kontak', render:()=>`
            <p class="text-xs text-gray-500 mb-3">Unggah file CSV dengan format: <code>nomor,nama</code> per baris.</p>
            <div class="wa-field"><label>File CSV</label><input type="file" id="impFile" accept=".csv,.txt"></div>
            <div class="wa-field"><label>Tag Grup (opsional)</label><input type="text" id="impTag" placeholder="mis: pelanggan-vip"></div>
            <button class="wa-btn wa-btn-primary w-full" onclick="importContacts()"><i class="fas fa-file-import"></i>Import</button>
            <div id="impResult" class="mt-4"></div>`},
        autoreply: {title:'Auto Reply (Text Only)', render:()=>`
            <div class="flex items-center justify-between mb-3">
                <div><div class="font-medium">Aktifkan Auto Reply</div><div class="text-xs text-gray-500">Balas otomatis pesan masuk</div></div>
                <label class="wa-switch"><input type="checkbox" id="arEnabled" ${settings.auto_reply_enabled==='1'?'checked':''} onchange="toggleSetting('auto_reply_enabled', this.checked)"><span class="wa-switch-slider"></span></label>
            </div>
            <a href="{{ route('autoreply.manage') }}" class="wa-btn wa-btn-primary w-full mb-3 text-center block text-xs" style="text-decoration:none"><i class="fas fa-external-link-alt mr-1"></i> Buka Halaman Penuh Auto-Reply</a>
            <div class="wa-section-title">Tambah / Edit Aturan</div>
            <input type="hidden" id="arId">
            <div class="wa-field"><label>Keyword</label><input type="text" id="arKey" placeholder="contoh: halo"></div>
            <div class="wa-field"><label>Cocok</label>
                <select id="arMatch"><option value="contains">Mengandung</option><option value="exact">Persis</option><option value="starts_with">Diawali</option></select>
            </div>
            <div class="wa-field"><label>Balasan</label><textarea id="arReply" rows="3"></textarea></div>
            <div class="flex gap-2 mb-3">
                <button class="wa-btn wa-btn-primary flex-1" onclick="saveAutoReply()"><i class="fas fa-save"></i>Simpan</button>
                <button class="wa-btn wa-btn-ghost" onclick="resetAutoReplyForm()">Reset</button>
            </div>
            <div class="wa-section-title">Daftar Aturan</div>
            <div id="arList"></div>`},
        webhook: {title:'Webhook', render:()=>`
            <div class="wa-section-title">URL Webhook (Text)</div>
            <div class="wa-card">
                <div class="flex items-center gap-2">
                    <code class="text-xs flex-1 break-all">${WEBHOOK_URL}</code>
                    <button class="wa-btn wa-btn-ghost" onclick="navigator.clipboard.writeText('${WEBHOOK_URL}').then(()=>toast('Disalin'))"><i class="fas fa-copy"></i></button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Pasang URL ini di WAHA Webhook untuk terima pesan & status tracking.</p>
            </div>
            <div class="flex items-center justify-between mt-4">
                <div><div class="font-medium">Webhook Tracking Status</div><div class="text-xs text-gray-500">Log status setiap pesan masuk</div></div>
                <label class="wa-switch"><input type="checkbox" id="wtEnabled" ${settings.webhook_tracking==='1'?'checked':''} onchange="toggleSetting('webhook_tracking', this.checked)"><span class="wa-switch-slider"></span></label>
            </div>
            <div class="wa-section-title">Log Terbaru</div>
            <div id="wlList" class="text-xs">Memuat...</div>`},
        scheduled: {title:'Pesan Terjadwal', render:()=>`
            <button class="wa-btn wa-btn-primary w-full mb-3" onclick="openSchedModal()"><i class="fas fa-plus"></i>Jadwal Baru</button>
            <div id="schedList"></div>`},
        stats: {title:'Statistik', render:()=>`
            <div class="wa-stat-grid">
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_sent'] }}</div><div class="wa-stat-label">Terkirim</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_received'] }}</div><div class="wa-stat-label">Diterima</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_failed'] }}</div><div class="wa-stat-label">Gagal</div></div>
                <div class="wa-stat"><div class="wa-stat-val">{{ $stats['total_contacts'] }}</div><div class="wa-stat-label">Kontak</div></div>
            </div>
            <div class="wa-card">
                <div class="font-medium mb-2">Paket Fitur Aktif</div>
                <ul class="text-xs space-y-1 text-gray-700">
                    <li>✓ Pesan Tak Terbatas (Unlimited)</li>
                    <li>✓ Riwayat Pesan Disimpan 5 Hari</li>
                    <li>✓ Kirim Pesan Teks</li>
                    <li>✓ Tanpa Watermark</li>
                    <li>✓ Kirim Pesan Grup</li>
                    <li>✓ Cek Status Nomor WhatsApp</li>
                    <li>✓ Kirim Pesan Lokasi</li>
                    <li>✓ Kirim Link Preview</li>
                    <li>✓ Auto Reply (Text Only)</li>
                    <li>✓ Webhook Text</li>
                    <li>✓ Webhook Tracking Status</li>
                    <li>✓ Import Kontak</li>
                    <li>✓ Kirim Berdasar Grup Kontak</li>
                </ul>
            </div>`},
    };

    function openPanel(name, arg){
        const p = panels[name]; if(!p) return;
        document.getElementById('panelTitle').textContent = p.title;
        document.getElementById('panelBody').innerHTML = p.render(arg);
        document.getElementById('rightbar').classList.add('open');
        document.getElementById('mainMenu').classList.remove('open');
        if(name==='autoreply') renderARList();
        if(name==='webhook') loadWebhookLogs();
        if(name==='scheduled') renderSchedList();
    }
    function closePanel(){document.getElementById('rightbar').classList.remove('open')}

    // ============ new chat ============
    async function sendNewChat(){
        const phone = document.getElementById('newPhone').value.trim();
        const msg = document.getElementById('newMsg').value.trim();
        const grp = document.getElementById('newGroup').checked;
        if(!phone || !msg) return toast('Isi nomor dan pesan');
        const r = await postJson('/reply', {phone, message: msg, is_group: grp?1:0});
        if(r.success){ toast('Terkirim'); closePanel(); await refreshContacts(); openChat(phone); }
        else toast('Gagal kirim');
    }

    async function sendLocation(){
        const phone = document.getElementById('locPhone').value.trim();
        const lat = document.getElementById('locLat').value.trim();
        const lng = document.getElementById('locLng').value.trim();
        const cap = document.getElementById('locCap').value.trim();
        if(!phone || !lat || !lng) return toast('Lengkapi data');
        const r = await postJson('/send-location', {phone, latitude:lat, longitude:lng, caption:cap});
        if(r.success){toast('Lokasi terkirim'); closePanel(); if(activePhone===phone) loadConversation(phone, true); await refreshContacts();}
        else toast('Gagal kirim lokasi');
    }

    async function sendLink(){
        const phone = document.getElementById('lkPhone').value.trim();
        const url = document.getElementById('lkUrl').value.trim();
        const msg = document.getElementById('lkMsg').value.trim();
        if(!phone || !url) return toast('Lengkapi nomor & URL');
        const r = await postJson('/send-link', {phone, url, message:msg});
        if(r.success){toast('Link terkirim'); closePanel(); if(activePhone===phone) loadConversation(phone, true); await refreshContacts();}
        else toast('Gagal kirim link');
    }

    async function checkNumber(){
        const phone = document.getElementById('chkPhone').value.trim();
        if(!phone) return;
        const out = document.getElementById('chkResult');
        out.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';
        try{
            const r = await postJson('/check-number', {phone});
            out.innerHTML = `<div class="wa-card"><pre class="text-xs whitespace-pre-wrap">${escapeHtml(JSON.stringify(r, null, 2))}</pre></div>`;
        }catch(e){out.innerHTML = '<div class="text-red-500 text-sm">Error cek nomor</div>'}
    }

    async function sendByGroup(){
        const tag = document.getElementById('grpTag').value;
        const msg = document.getElementById('grpMsg').value.trim();
        if(!tag || !msg) return toast('Lengkapi tag dan pesan');
        if(!confirm(`Broadcast ke grup "${tag}"?`)) return;
        const r = await postJson('/send-group', {group_tag:tag, message:msg});
        if(r.success){toast(`Terkirim: ${r.sent}/${r.total}, gagal ${r.failed}`); closePanel(); await refreshContacts();}
        else toast('Broadcast gagal');
    }

    async function importContacts(){
        const f = document.getElementById('impFile').files[0];
        const tag = document.getElementById('impTag').value.trim();
        if(!f) return toast('Pilih file CSV');
        const fd = new FormData(); fd.append('file', f); if(tag) fd.append('group_tag', tag);
        try{
            const r = await fetch('/import-contacts',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:fd});
            const d = await r.json();
            document.getElementById('impResult').innerHTML = `<div class="wa-card text-sm">Imported: <b>${d.imported}</b><br>Skipped: <b>${d.skipped}</b></div>`;
            await refreshContacts();
        }catch(e){toast('Import gagal')}
    }

    // ============ auto reply ============
    function renderARList(){
        const el = document.getElementById('arList');
        if(!autoReplies.length){el.innerHTML = '<div class="text-xs text-gray-400">Belum ada aturan.</div>';return}
        el.innerHTML = autoReplies.map(r=>`
            <div class="wa-card">
                <div class="flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500">${r.match_type} · ${r.active?'<span class="text-green-600">aktif</span>':'<span class="text-gray-400">nonaktif</span>'}</div>
                        <div class="font-medium truncate">${escapeHtml(r.keyword)}</div>
                        <div class="text-xs text-gray-600 mt-1 line-clamp-2">${escapeHtml(r.reply)}</div>
                    </div>
                    <div class="flex gap-1">
                        <button class="wa-icon-btn" onclick='editAR(${JSON.stringify(r)})'><i class="fas fa-edit text-xs"></i></button>
                        <button class="wa-icon-btn" onclick="deleteAR(${r.id})"><i class="fas fa-trash text-xs text-red-500"></i></button>
                    </div>
                </div>
            </div>`).join('');
    }
    function editAR(r){
        document.getElementById('arId').value = r.id;
        document.getElementById('arKey').value = r.keyword;
        document.getElementById('arMatch').value = r.match_type;
        document.getElementById('arReply').value = r.reply;
    }
    function resetAutoReplyForm(){['arId','arKey','arReply'].forEach(i=>document.getElementById(i).value='')}
    async function saveAutoReply(){
        const body = {
            id: document.getElementById('arId').value || null,
            keyword: document.getElementById('arKey').value.trim(),
            match_type: document.getElementById('arMatch').value,
            reply: document.getElementById('arReply').value.trim(),
            active: 1,
        };
        if(!body.keyword || !body.reply) return toast('Lengkapi keyword & balasan');
        const r = await postJson('/autoreply', body);
        if(r.success){toast('Tersimpan');resetAutoReplyForm();autoReplies = await api('/autoreply');renderARList()}
    }
    async function deleteAR(id){
        if(!confirm('Hapus aturan ini?')) return;
        await api('/autoreply/'+id,{method:'DELETE'});
        autoReplies = autoReplies.filter(x=>x.id!==id); renderARList();
    }

    async function toggleSetting(k, val){
        await postJson('/settings',{[k]: val?1:0});
        settings[k] = val?'1':'0';
        toast('Pengaturan disimpan');
    }

    async function loadWebhookLogs(){
        try{
            const logs = await api('/webhook/logs');
            const el = document.getElementById('wlList');
            if(!logs.length){el.innerHTML = '<div class="text-gray-400">Belum ada log.</div>';return}
            el.innerHTML = logs.slice(0,30).map(l=>`
                <div class="wa-card">
                    <div class="flex justify-between"><b>${escapeHtml(l.phone||'-')}</b><span>${escapeHtml(l.status||'-')}</span></div>
                    <div class="text-gray-500">${new Date(l.created_at).toLocaleString('id-ID')}</div>
                </div>`).join('');
        }catch(e){}
    }

    // ============ schedule ============
    function renderSchedList(){
        const el = document.getElementById('schedList');
        if(!scheduled.length){el.innerHTML = '<div class="text-xs text-gray-400">Belum ada pesan terjadwal.</div>';return}
        el.innerHTML = scheduled.map(s=>`
            <div class="wa-card">
                <div class="flex justify-between items-start">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500">${s.kind} · ${escapeHtml(s.status)}</div>
                        <div class="font-medium truncate">${escapeHtml(s.phone)}</div>
                        <div class="text-xs text-gray-600 truncate">${escapeHtml(s.message||s.link_url||(s.latitude+','+s.longitude))}</div>
                        <div class="text-xs text-blue-600 mt-1"><i class="far fa-clock"></i> ${new Date(s.scheduled_at).toLocaleString('id-ID')}</div>
                    </div>
                    ${s.status==='pending'?`<button class="wa-icon-btn" onclick="cancelSched(${s.id})"><i class="fas fa-times text-red-500"></i></button>`:''}
                </div>
            </div>`).join('');
    }
    async function cancelSched(id){
        if(!confirm('Batalkan jadwal ini?')) return;
        await postJson('/schedule/cancel/'+id,{});
        scheduled = await api('/schedule');
        renderSchedList();
        document.getElementById('schedBadge').textContent = scheduled.filter(s=>s.status==='pending').length;
    }
    function openSchedModal(){document.getElementById('schedModal').classList.add('show')}
    function closeSchedModal(){document.getElementById('schedModal').classList.remove('show')}
    function openScheduleForActive(){
        if(activePhone) document.getElementById('schedPhone').value = activePhone;
        openSchedModal();
    }
    function toggleSchedKind(){
        const k = document.getElementById('schedKind').value;
        document.getElementById('fldText').classList.toggle('hidden', k!=='text');
        document.getElementById('fldLat').classList.toggle('hidden', k!=='location');
        document.getElementById('fldLng').classList.toggle('hidden', k!=='location');
        document.getElementById('fldUrl').classList.toggle('hidden', k!=='link');
    }
    async function saveSchedule(){
        const body = {
            phone: document.getElementById('schedPhone').value.trim(),
            is_group: document.getElementById('schedGroup').checked?1:0,
            kind: document.getElementById('schedKind').value,
            message: document.getElementById('schedMsg').value.trim(),
            latitude: document.getElementById('schedLat').value.trim()||null,
            longitude: document.getElementById('schedLng').value.trim()||null,
            link_url: document.getElementById('schedUrl').value.trim()||null,
            scheduled_at: document.getElementById('schedAt').value,
        };
        if(!body.phone || !body.scheduled_at) return toast('Lengkapi nomor & waktu');
        const r = await postJson('/schedule', body);
        if(r.success){
            toast('Terjadwal');
            closeSchedModal();
            scheduled = await api('/schedule');
            renderSchedList();
            document.getElementById('schedBadge').textContent = scheduled.filter(s=>s.status==='pending').length;
        }else toast('Gagal menjadwalkan');
    }

    async function logout(){
        if(!confirm('Keluar?')) return;
        const f = document.createElement('form');
        f.method='POST';f.action='/logout';
        const t = document.createElement('input');t.name='_token';t.value=CSRF;f.appendChild(t);
        document.body.appendChild(f);f.submit();
    }

    // ============ init ============
    renderContacts();

    // Auto-refresh contact list every 3s and active conversation every 2s for near realtime chat experience
    setInterval(async ()=>{
        try{
            await refreshContacts();
            if(activePhone) await loadConversation(activePhone, true);
        }catch(e){}
    }, 2500);
</script>
</body>
</html>
