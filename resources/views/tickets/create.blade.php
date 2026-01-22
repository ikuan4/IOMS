@extends('layouts.dashboard')

@section('title', 'Create Ticket')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Create Ticket</h2>
            <p class="muted">Log a new ticket.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" data-ticket-attachments-form>
        @csrf
        <input type="hidden" name="draft_key" value="{{ $draftKey ?? '' }}">

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:800px;">

                <div>
                    <label for="ticket_type_id" style="font-size:15px;font-weight:600;">Ticket Type <span style="color:#dc2626;">*</span></label><br>
                    <select name="ticket_type_id" id="ticket_type_id" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        <option value="" disabled {{ old('ticket_type_id') ? '' : 'selected' }}>Select a ticket type</option>
                        @foreach($ticketTypes as $type)
                            <option value="{{ $type->id }}" {{ (string) old('ticket_type_id') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('ticket_type_id')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="ticket_module_id" style="font-size:15px;font-weight:600;">Module Name <span style="color:#dc2626;">*</span></label><br>
                    <select name="ticket_module_id" id="ticket_module_id" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        <option value="" disabled {{ old('ticket_module_id') ? '' : 'selected' }}>Select a module</option>
                        @foreach($ticketModules as $module)
                            <option value="{{ $module->id }}" {{ (string) old('ticket_module_id') === (string) $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                        @endforeach
                    </select>
                    @error('ticket_module_id')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="subject" style="font-size:15px;font-weight:600;">Subject <span style="color:#dc2626;">*</span></label><br>
                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" placeholder="Short summary" />
                    @error('subject')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="description" style="font-size:15px;font-weight:600;">Description</label><br>
                    <textarea
                        name="description"
                        id="description"
                        rows="5"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="Optional details"
                        data-ticket-attachments-paste
                        data-ticket-attachments-upload-url="{{ route('tickets.uploads.draft') }}"
                        data-ticket-attachments-delete-url="{{ route('tickets.uploads.draft-delete') }}"
                        data-ticket-attachments-draft-key="{{ $draftKey ?? '' }}"
                        data-ticket-attachments-list="#ticket-attachments-list"
                    >{{ old('description') }}</textarea>

                    <div id="ticket-attachments-list" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:10px;"></div>
                    @error('description')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="attachments" style="font-size:15px;font-weight:600;">Attachments</label><br>
                    <div style="position:relative;margin-top:8px;">
                        <input
                            type="file"
                            name="attachments[]"
                            id="ticket_attachments"
                            multiple
                            accept=".pdf,.doc,.docx,.xlsx,.xls,.txt,.jpg,.jpeg,.png"
                            style="display:none;"
                            data-file-label-id="ticket_file_label"
                            data-file-count-id="ticket_file_count"
                        />
                        <label for="ticket_attachments" style="
                            display:inline-flex;
                            align-items:center;
                            gap:8px;
                            padding:14px 24px;
                            background:#22c55e;
                            color:white;
                            border-radius:10px;
                            font-weight:600;
                            font-size:15px;
                            cursor:pointer;
                            border:none;
                            transition:all 0.2s;
                        "
                        onmouseover="this.style.background='#16a34a'"
                        onmouseout="this.style.background='#22c55e'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                            </svg>
                            <span id="ticket_file_label">Choose Files</span>
                        </label>
                        <span id="ticket_file_count" style="margin-left:20px;font-size:14px;color:#6b7280;"></span>
                    </div>
                    <div style="color:#6b7280;font-weight:700;font-size:12px;margin-top:6px;">Max 20 MB per file. Allowed: PDF, DOC/DOCX, XLS/XLSX, TXT, PNG, JPG, JPEG. You can also paste an image into the Description box.</div>
                    @error('attachments')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;">
                    <div>
                        <label for="status" style="font-size:15px;font-weight:600;">Status <span style="color:#dc2626;">*</span></label><br>
                        <select name="status" id="status" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                            @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $k => $label)
                                <option value="{{ $k }}" {{ old('status', 'open') === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="priority" style="font-size:15px;font-weight:600;">Priority <span style="color:#dc2626;">*</span></label><br>
                        <select name="priority" id="priority" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $k => $label)
                                <option value="{{ $k }}" {{ old('priority', 'medium') === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="assigned_to" style="font-size:15px;font-weight:600;">Assignee</label><br>
                        <select name="assigned_to" id="assigned_to" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                            <option value="" {{ old('assigned_to') ? '' : 'selected' }}>Unassigned</option>
                            @foreach($assignees as $u)
                                <option value="{{ $u->id }}" {{ (string) old('assigned_to') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}{{ $u->email ? ' (' . $u->email . ')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="due_at" style="display:block;font-size:15px;font-weight:600;margin-bottom:8px;">Due Date</label>
                        <input
                            type="text"
                            name="due_at"
                            id="due_at"
                            value="{{ old('due_at') }}"
                            placeholder="Select due date"
                            data-ticket-date-picker
                            style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;cursor:pointer;background-color:white;"
                        />
                        @error('due_at')
                            <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;">
            <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;">
                Create Ticket
            </button>
            <a href="{{ route('tickets.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>
@endsection
