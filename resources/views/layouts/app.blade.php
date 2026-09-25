<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'BlueSky')</title><style>
:root{
    --navy:#0b1f4f;
    --blue:#2563eb;
    --sky:#eff8ff;
    --line:#dbeafe;
    --text:#16213a;
    --muted:#64748b
}
*{
    box-sizing:border-box
}
body{
    margin:0;
    background:linear-gradient(135deg,#f6fbff,#eaf5ff);
    color:var(--text);
    font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif
}
.application{
    display:flex;
    min-height:100vh
}
.sidebar{
    position:sticky;
    top:0;
    display:flex;
    flex-direction:column;
    width:266px;
    height:100vh;
    padding:26px 16px;
    background:linear-gradient(165deg,var(--navy),#123a88 62%,#2563eb);
    color:#dbeafe;
    box-shadow:8px 0 30px rgba(15,46,116,.12)
}
.brand{
    display:flex;
    align-items:center;
    gap:11px;
    margin:0 10px 34px;
    color:white;
    font-size:20px;
    font-weight:800;
    text-decoration:none
}
.brand-mark{
    display:grid;
    width:35px;
    height:35px;
    place-items:center;
    border-radius:11px;
    background:linear-gradient(135deg,#7dd3fc,#3b82f6);
    font-size:17px
}
.nav-caption{
    margin:0 11px 10px;
    color:#93c5fd;
    font-size:11px;
    font-weight:800;
    letter-spacing:.11em;
    text-transform:uppercase
}
.nav-link{
    display:flex;
    align-items:center;
    gap:12px;
    padding:11px 12px;
    margin:4px 0;
    border:1px solid transparent;
    border-radius:11px;
    color:#dbeafe;
    font-size:14px;
    font-weight:650;
    text-decoration:none;
    transition:.18s
}
.nav-link:hover{
    color:white;
    background:rgba(255,255,255,.10);
    transform:translateX(2px)
}
.nav-link.active{
    color:#0d3c91;
    background:white;
    box-shadow:0 8px 20px rgba(5,29,87,.18)
}
.nav-icon{
    display:grid;
    width:21px;
    place-items:center;
    font-size:16px
}
.sidebar-note{
    margin-top:auto;
    padding:14px;
    border:1px solid rgba(191,219,254,.18);
    border-radius:12px;
    background:rgba(10,35,94,.23);
    color:#bfdbfe;
    font-size:12px;
    line-height:1.5
}
.content{
    flex:1;
    min-width:0;
    padding:28px clamp(20px,4vw,54px) 44px
}
.topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:25px;
    padding:13px 16px 13px 20px;
    border:1px solid rgba(219,234,254,.85);
    border-radius:16px;
    background:rgba(255,255,255,.82);
    box-shadow:0 8px 26px rgba(51,100,176,.06)
}
.topbar-label{
    margin:0 0 2px;
    color:var(--muted);
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase
}
.topbar-name{
    font-size:14px;
    font-weight:750
}
.user-chip{
    display:flex;
    align-items:center;
    gap:10px
}
.user-avatar{
    display:grid;
    width:34px;
    height:34px;
    place-items:center;
    border-radius:50%;
    background:#e5f3ff;
    color:#164eab;
    font-size:13px;
    font-weight:800
}
.user-chip form{
    padding:5px;
    border:1px solid #cfe1fb;
    border-radius:15px;
    background:#eff8ff
}
.logout-button{
    min-height:42px;
    padding:0 18px;
    border:1px solid #bfdbfe;
    border-radius:10px;
    background:white;
    color:#164eab;
    cursor:pointer;
    font:inherit;
    font-size:14px;
    font-weight:800
}
.logout-button:hover{
    background:#f8fbff
}
.card{
    padding:26px;
    border:1px solid rgba(219,234,254,.95);
    border-radius:18px;
    background:rgba(255,255,255,.93);
    box-shadow:0 14px 36px rgba(30,88,171,.10)
}
h1{
    margin:0 0 8px;
    color:#102a5b;
    font-size:clamp(25px,3vw,34px);
    letter-spacing:-.04em
}
h2{
    color:#17376e
}
p{
    color:var(--muted);
    line-height:1.6
}
input,select,textarea{
    border:1px solid #cfe1fb;
    border-radius:10px;
    background:#fbfdff;
    color:var(--text);
    font:inherit
}
input:focus,select:focus,textarea:focus{
    outline:0;
    border-color:#3b82f6;
    box-shadow:0 0 0 4px rgba(59,130,246,.13)
}
.button{
    display:inline-flex;
    min-height:42px;
    align-items:center;
    justify-content:center;
    padding:0 19px;
    border:0;
    border-radius:12px;
    background:linear-gradient(135deg,#2563eb,#3b82f6);
    box-shadow:0 8px 16px rgba(37,99,235,.22);
    color:white;
    cursor:pointer;
    font:inherit;
    font-size:14px;
    font-weight:800;
    letter-spacing:.005em;
    transition:.18s
}
.button:hover{
    box-shadow:0 11px 22px rgba(37,99,235,.28);
    transform:translateY(-1px)
}
.danger-button{
    background:linear-gradient(135deg,#ef4444,#fb7185)
}
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    overflow:hidden;
    border:1px solid var(--line);
    border-radius:12px
}
th{
    padding:12px;
    background:var(--sky);
    color:#54709e;
    font-size:11px;
    letter-spacing:.06em;
    text-transform:uppercase
}
td{
    padding:14px 12px;
    border-top:1px solid #e8f1fd;
    font-size:14px;
    vertical-align:middle
}
.success-message,.error-message{
    margin-bottom:18px;
    padding:13px 15px;
    border-radius:12px;
    font-size:14px
}
.success-message{
    border:1px solid #bbf7d0;
    background:#f0fdf4;
    color:#166534
}
.error-message{
    border:1px solid #fecaca;
    background:#fff5f5;
    color:#b91c1c
}
@media(max-width:760px){
    .application{
        display:block
    }
    .sidebar{
        position:relative;
        width:100%;
        height:auto;
        padding:16px
    }
    .brand{
        margin:0 5px 14px
    }
    .sidebar nav{
        display:flex;
        gap:6px;
        overflow:auto
    }
    .nav-caption,.sidebar-note{
        display:none
    }
    .nav-link{
        white-space:nowrap;
        margin:0
    }
    .content{
        padding:18px
    }
    .topbar{
        padding:12px
    }
    .topbar-label{
        display:none
    }
}
</style></head><body><div class="application">@include('layouts.components.sidebar')<main class="content"><div class="topbar"><div><div class="topbar-label">Workspace</div><div class="topbar-name">@yield('title', 'Dashboard')</div></div><div class="user-chip"><div class="user-avatar">{{ strtoupper(substr(Auth::user()->name,0,1)) }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Sign out</button></form></div></div>@if(session('success'))<div class="success-message">{{ session('success') }}</div>@endif @if($errors->any())<div class="error-message">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif @yield('content')</main></div></body></html>
