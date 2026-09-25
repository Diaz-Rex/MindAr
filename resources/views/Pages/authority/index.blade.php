@extends('layouts.app')

@section('title', 'Authority Management')

@section('content')
    <style>
        .authority-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
            gap: 24px;
        }

        .authority-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .authority-header h1,
        .authority-header h2 { margin: 0; }

        .authority-table-wrap { overflow-x: auto; }
        .access-list { color: #475569; line-height: 1.6; }
        .empty-access { color: #94a3b8; font-style: italic; }
        .search-input {
            width: min(280px, 100%);
            min-height: 46px;
            padding: 11px 14px;
        }

        .link-form {
            display: none;
            gap: 10px;
            margin-bottom: 18px;
        }

        .link-form.open { display: flex; }
        .link-form input {
            flex: 1;
            min-width: 0;
            min-height: 46px;
            padding: 11px 14px;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            place-items: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
        }

        .modal-backdrop.open { display: grid; }

        .access-modal {
            width: min(520px, 100%);
            padding: 0;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }

        .modal-header,
        .modal-body,
        .modal-footer { padding: 20px 24px; }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 { margin: 0; }

        .modal-close {
            width: 34px;
            height: 34px;
            border: 1px solid #cbd5e1;
            background: white;
            cursor: pointer;
        }

        .modal-body label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .modal-body select {
            width: 100%;
            min-height: 50px;
            padding: 12px 14px;
        }

        .selected-user {
            margin: 0 0 18px;
            color: #475569;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .secondary-button { background: #64748b; }

        @media (max-width: 900px) {
            .authority-grid { grid-template-columns: 1fr; }
            .authority-header { align-items: flex-start; flex-direction: column; }
        }
    </style>

    <div class="authority-grid">
        <section class="card">
            <div class="authority-header">
                <div>
                    <h1>Authority</h1>
                    <p>Assign or remove URL access for a user.</p>
                </div>

                <input class="search-input" id="user-search" type="search" placeholder="Search users...">
            </div>

            <div class="authority-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Access list</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="user-list">
                        @forelse ($users as $user)
                            <tr data-user-row data-search="{{ strtolower($user->name . ' ' . $user->email) }}">
                                <td>{{ $user->id }}</td>
                                <td>
                                    <strong>{{ $user->name }}</strong><br>
                                    <small>{{ $user->email }}</small>
                                </td>
                                <td class="access-list">
                                    @if ($user->permissions->isEmpty())
                                        <span class="empty-access">No access assigned</span>
                                    @else
                                        {{ $user->permissions->pluck('linkName')->implode(', ') }}
                                    @endif
                                </td>
                                <td>
                                    <button
                                        class="button open-access-modal"
                                        type="button"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                    >
                                        Add access
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="authority-header">
                <div>
                    <h2>Links</h2>
                    <p>URL prefixes available for assignment.</p>
                </div>

                <button class="button" id="toggle-link-form" type="button">Add</button>
            </div>

            <form class="link-form" id="link-form" method="POST" action="{{ route('superadmin.authority') }}">
                @csrf
                <input type="hidden" name="type" value="link">
                <input type="text" name="name" placeholder="Example: reports" required>
                <button class="button" type="submit">Save</button>
            </form>

            <div class="authority-table-wrap">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $permission)
                            <tr>
                                <td>{{ $permission->id }}</td>
                                <td>{{ $permission->linkName }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No links created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="modal-backdrop" id="access-modal" aria-hidden="true">
        <form class="access-modal" method="POST" action="{{ route('superadmin.authority') }}">
            @csrf
            <input type="hidden" id="access-user-id" name="id">

            <div class="modal-header">
                <h2>Manage access</h2>
                <button class="modal-close" id="close-access-modal" type="button" aria-label="Close">×</button>
            </div>

            <div class="modal-body">
                <p class="selected-user">User: <strong id="access-user-name"></strong></p>

                <label for="linkName_id">Access list</label>
                <select id="linkName_id" name="linkName_id" required>
                    <option value="">Select link name</option>
                    @foreach ($data as $permission)
                        <option value="{{ $permission->id }}">{{ $permission->linkName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="modal-footer">
                <button class="button secondary-button" id="cancel-access-modal" type="button">Close</button>
                <button class="button" type="submit" name="type" value="add_access">Add</button>
                <button class="button danger-button" type="submit" name="type" value="remove_access">Remove</button>
            </div>
        </form>
    </div>

    <script>
        const accessModal = document.getElementById('access-modal');
        const accessUserId = document.getElementById('access-user-id');
        const accessUserName = document.getElementById('access-user-name');

        function closeAccessModal() {
            accessModal.classList.remove('open');
            accessModal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.open-access-modal').forEach((button) => {
            button.addEventListener('click', () => {
                accessUserId.value = button.dataset.userId;
                accessUserName.textContent = button.dataset.userName;
                accessModal.classList.add('open');
                accessModal.setAttribute('aria-hidden', 'false');
            });
        });

        document.getElementById('close-access-modal').addEventListener('click', closeAccessModal);
        document.getElementById('cancel-access-modal').addEventListener('click', closeAccessModal);
        accessModal.addEventListener('click', (event) => {
            if (event.target === accessModal) closeAccessModal();
        });

        document.getElementById('toggle-link-form').addEventListener('click', () => {
            document.getElementById('link-form').classList.toggle('open');
        });

        document.getElementById('user-search').addEventListener('input', (event) => {
            const search = event.target.value.trim().toLowerCase();

            document.querySelectorAll('[data-user-row]').forEach((row) => {
                row.hidden = !row.dataset.search.includes(search);
            });
        });
    </script>
@endsection
