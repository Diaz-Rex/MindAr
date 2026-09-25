@extends('layouts.app')

@section('title', '3D Playground')

@section('content')
    <style>
.lobby-heading {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:24px
}
.lobby-heading h1 {
    margin-bottom:4px
}
.lobby-heading p {
    margin:0;
    color:#64748b;
    font-size:14px
}
.lobby-button {
    min-height:40px;
    padding:0 16px;
    border:1px solid #2563eb;
    border-radius:10px;
    background:#2563eb;
    color:#fff;
    cursor:pointer;
    font:inherit;
    font-size:13px;
    font-weight:800
}
.lobby-button:hover {
    background:#1d4ed8
}
.lobby-button.secondary {
    border-color:#c7d9f4;
    background:#fff;
    color:#17417e
}
.lobby-button.secondary:hover {
    border-color:#2563eb;
    background:#eaf2ff
}
.qr-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,260px));
    gap:22px
}
.qr-card {
    position:relative;
    overflow:hidden;
    padding:16px;
    border:1px solid #d5e4f7;
    border-radius:16px;
    background:#fff;
    box-shadow:0 10px 28px rgba(30,88,171,.08);
    transition:.18s ease
}
.qr-card:hover {
    border-color:#8eb7f2;
    box-shadow:0 16px 34px rgba(30,88,171,.15);
    transform:translateY(-3px)
}
.qr-card-link {
    display:block;
    padding-top:26px;
    color:inherit;
    text-decoration:none
}
.qr-image {
    display:grid;
    min-height:208px;
    padding:20px;
    place-items:center;
    overflow:hidden;
    border-radius:12px;
    background:linear-gradient(145deg,#f8fbff,#edf5ff)
}
.qr-image img {
    display:block;
    width:100%;
    max-width:190px;
    max-height:240px;
    object-fit:contain;
    border-radius:8px;
    background:#fff
}
.qr-hover-preview {
    position:fixed;
    z-index:1000;
    inset:0;
    display:grid;
    visibility:hidden;
    place-items:center;
    padding:24px;
    background:rgba(15,23,42,.72);
    opacity:0;
    pointer-events:none;
    transition:opacity .16s ease,visibility .16s ease
}
.qr-hover-preview.visible {
    visibility:visible;
    opacity:1
}
.qr-hover-preview img {
    display:block;
    width:min(88vmin,900px);
    height:min(88vmin,900px);
    object-fit:contain;
    background:#fff;
    box-shadow:0 24px 80px rgba(0,0,0,.4)
}
.qr-details {
    padding:15px 2px 2px
}
.qr-card-actions {
    position:absolute;
    z-index:2;
    top:20px;
    right:20px;
    display:flex;
    gap:7px
}
.qr-icon-button {
    display:grid;
    width:36px;
    height:36px;
    padding:0;
    place-items:center;
    border:1px solid #c7d9f4;
    border-radius:10px;
    background:rgba(255,255,255,.96);
    box-shadow:0 5px 14px rgba(30,88,171,.16);
    color:#174cb1;
    cursor:pointer;
    font-size:18px;
    font-weight:900
}
.qr-icon-button:hover {
    border-color:#2563eb;
    background:#eaf2ff
}
.qr-delete-button {
    color:#dc2626
}
.qr-delete-button:hover {
    border-color:#ef4444;
    background:#fef2f2
}
.qr-icon-button svg {
    width:18px;
    height:18px;
    fill:none;
    stroke:currentColor;
    stroke-linecap:round;
    stroke-linejoin:round;
    stroke-width:2
}
.qr-name {
    display:block;
    overflow:hidden;
    color:#17376e;
    font-size:15px;
    font-weight:800;
    text-overflow:ellipsis;
    white-space:nowrap
}
.qr-note {
    display:block;
    margin-top:4px;
    color:#71839e;
    font-size:11px
}
.empty-lobby {
    display:grid;
    min-height:360px;
    padding:38px;
    place-items:center;
    border:1px dashed #b9d1ef;
    border-radius:18px;
    background:rgba(255,255,255,.65);
    text-align:center
}
.empty-lobby-icon {
    display:grid;
    width:70px;
    height:70px;
    margin:0 auto 16px;
    place-items:center;
    border-radius:20px;
    background:#e7f1ff;
    color:#2563eb;
    font-size:32px
}
.empty-lobby h2 {
    margin:0 0 6px;
    color:#17376e;
    font-size:20px
}
.empty-lobby p {
    max-width:400px;
    margin:0 0 18px;
    color:#64748b;
    font-size:13px;
    line-height:1.55
}
.create-dialog {
    width:min(440px,calc(100vw - 28px));
    padding:0;
    border:1px solid #c9dcf5;
    border-radius:16px;
    box-shadow:0 24px 70px rgba(22,54,101,.3)
}
.create-dialog::backdrop {
    background:rgba(20,39,67,.48);
    backdrop-filter:blur(2px)
}
.dialog-heading {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    padding:18px 20px;
    border-bottom:1px solid #dce8f8
}
.dialog-heading h2 {
    margin:0;
    color:#17376e;
    font-size:18px
}
.dialog-close {
    width:34px;
    height:34px;
    padding:0;
    border:0;
    border-radius:8px;
    background:#eef5ff;
    color:#29466b;
    cursor:pointer;
    font-size:22px
}
.create-form {
    padding:20px
}
.create-form label {
    display:block;
    margin-bottom:7px;
    color:#29466b;
    font-size:13px;
    font-weight:800
}
.create-form input {
    width:100%;
    height:44px;
    padding:0 12px;
    border:1px solid #c7d9f4;
    border-radius:9px;
    color:#16213a;
    font:inherit;
    font-size:14px
}
.create-form input:focus {
    border-color:#2563eb;
    outline:3px solid #dbeafe
}
.field-error {
    margin:7px 0 0;
    color:#dc2626;
    font-size:12px
}
.dialog-actions {
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:20px
}
@media(max-width:620px) {
    .lobby-heading {
        align-items:flex-start;
        flex-direction:column
    }
    .lobby-heading .lobby-button {
        width:100%
    }
    .qr-grid {
        grid-template-columns:1fr
    }
}
    </style>

    <div class="lobby-heading">
        <div>
            <h1>3D Playground</h1>
            <p>Create a QR playground or open one you already made.</p>
        </div>
        <button class="lobby-button" type="button" data-open-create>+ Create playground</button>
    </div>

    @if ($playgrounds->isEmpty())
        <section class="empty-lobby">
            <div>
                <div class="empty-lobby-icon" aria-hidden="true">&#9638;</div>
                <h2>No playgrounds yet</h2>
                <p>Create your first playground. A unique QR code will be generated automatically for the MindAR camera page.</p>
                <button class="lobby-button" type="button" data-open-create>Create playground</button>
            </div>
        </section>
    @else
        <section class="qr-grid" aria-label="Your playgrounds">
            @foreach ($playgrounds as $playground)
                <article class="qr-card">
                    <div class="qr-card-actions">
                        <form method="POST" action="{{ route('mind-ar.playground') }}">
                            @csrf
                            <input name="type" type="hidden" value="downloadQr">
                            <input name="qr_token" type="hidden" value="{{ $playground->qr_token }}">
                            <button class="qr-icon-button" type="submit" title="Download QR as PNG" aria-label="Download {{ $playground->name }} QR as PNG">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/></svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('mind-ar.playground') }}" data-delete-playground="{{ $playground->name }}">
                            @csrf
                            <input name="type" type="hidden" value="delete">
                            <input name="qr_token" type="hidden" value="{{ $playground->qr_token }}">
                            <button class="qr-icon-button qr-delete-button" type="submit" title="Delete playground" aria-label="Delete {{ $playground->name }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
                            </button>
                        </form>
                    </div>
                    <a class="qr-card-link" href="{{ route('mind-ar.playground.build', $playground) }}" aria-label="Open {{ $playground->name }}">
                        <span class="qr-image">
                            <img src="{{ $playground->qr_code }}" alt="QR code for {{ $playground->name }}" data-qr-preview>
                        </span>
                        <span class="qr-details">
                            <span class="qr-name">{{ $playground->name }}</span>
                            <span class="qr-note">Click to edit &middot; Scan to open AR</span>
                        </span>
                    </a>
                </article>
            @endforeach
        </section>
    @endif

    <div id="qr-hover-preview" class="qr-hover-preview" aria-hidden="true">
        <img src="" alt="">
    </div>

    <dialog id="create-playground-dialog" class="create-dialog" aria-labelledby="create-playground-title">
        <div class="dialog-heading">
            <h2 id="create-playground-title">Create playground</h2>
            <button class="dialog-close" type="button" data-close-create aria-label="Close">&times;</button>
        </div>
        <form class="create-form" method="POST" action="{{ route('mind-ar.playground') }}">
            @csrf
            <input name="type" type="hidden" value="store">
            <label for="playground-name">Playground name</label>
            <input id="playground-name" name="name" type="text" value="{{ old('name') }}" maxlength="100" required autofocus placeholder="Example: Product display">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
            <div class="dialog-actions">
                <button class="lobby-button secondary" type="button" data-close-create>Cancel</button>
                <button class="lobby-button" type="submit">Create and open</button>
            </div>
        </form>
    </dialog>

    <script>
        const createDialog = document.getElementById('create-playground-dialog');
        const nameInput = document.getElementById('playground-name');
        const qrHoverPreview = document.getElementById('qr-hover-preview');
        const qrHoverPreviewImage = qrHoverPreview.querySelector('img');

        document.querySelectorAll('[data-open-create]').forEach(button => {
            button.addEventListener('click', () => {
                createDialog.showModal();
                nameInput.focus();
            });
        });

        document.querySelectorAll('[data-close-create]').forEach(button => {
            button.addEventListener('click', () => createDialog.close());
        });

        createDialog.addEventListener('click', event => {
            if (event.target === createDialog) createDialog.close();
        });

        document.querySelectorAll('[data-delete-playground]').forEach(form => {
            form.addEventListener('submit', event => {
                const playgroundName = form.dataset.deletePlayground;

                if (!window.confirm(`Delete "${playgroundName}" and all of its models and MindAR data?`)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-qr-preview]').forEach(qrImage => {
            qrImage.addEventListener('mouseenter', () => {
                qrHoverPreviewImage.src = qrImage.src;
                qrHoverPreviewImage.alt = qrImage.alt;
                qrHoverPreview.classList.add('visible');
            });

            qrImage.addEventListener('mouseleave', () => {
                qrHoverPreview.classList.remove('visible');
                qrHoverPreviewImage.src = '';
                qrHoverPreviewImage.alt = '';
            });
        });

        @if ($errors->has('name'))
            createDialog.showModal();
        @endif
    </script>
@endsection
