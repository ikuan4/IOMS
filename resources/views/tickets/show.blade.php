@extends('layouts.dashboard')

@section('title', 'Ticket Details')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <h2 style="margin:0;">Ticket Details</h2>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    @if(!empty($ticket->ticket_number))
                        <span style="background:#111827;color:#fff;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:900;letter-spacing:0.2px;">{{ $ticket->ticket_number }}</span>
                    @endif
                    <span style="background:#f3f4f6;color:#374151;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:900;">ID: {{ $ticket->id }}</span>
                </div>
            </div>
            <p class="muted">View history, comments, and movements.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card" style="margin-top:12px; border-left:4px solid #22c55e;">
            <div style="font-weight:700;">{{ session('status') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="card" style="margin-top:12px; border-left:4px solid #ef4444;">
            <div style="font-weight:700;">{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('tickets.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 18px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
            <span data-feather="arrow-left"></span>
            Back to Tickets
        </a>

        @if(auth()->user() && (auth()->user()->isSuperAdmin() || auth()->user()->can('update', $ticket)))
            <a href="{{ route('tickets.edit', $ticket) }}" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 18px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
                <span data-feather="edit"></span>
                Edit Ticket
            </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:14px;">
            <div>
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Ticket</div>
                <div style="font-size:18px;font-weight:900;">{{ $ticket->subject ?? '—' }}</div>
                <div style="margin-top:8px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    @if(!empty($ticket->ticket_number))
                        <span style="background:#111827;color:#fff;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:900;letter-spacing:0.2px;">{{ $ticket->ticket_number }}</span>
                    @endif
                    <span style="background:#f3f4f6;color:#374151;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:900;">ID: {{ $ticket->id }}</span>
                </div>
            </div>

            <div>
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Type / Module</div>
                <div style="font-weight:800;">{{ $ticket->ticketType->name ?? 'N/A' }} / {{ $ticket->ticketModule->name ?? 'N/A' }}</div>
                <div style="margin-top:6px;color:#6b7280;font-weight:700;">Branch: {{ $ticket->branch->name ?? 'N/A' }}</div>
            </div>

            <div>
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Status / Priority</div>
                <div style="font-weight:800;">{{ strtoupper(str_replace('_',' ', (string) $ticket->status)) }}</div>
                <div style="margin-top:6px;color:#6b7280;font-weight:700;">Priority: {{ strtoupper(str_replace('_',' ', (string) $ticket->priority)) }}</div>
            </div>

            <div>
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Assignee / Due</div>
                <div style="font-weight:800;">{{ $ticket->assignee->name ?? 'Unassigned' }}</div>
                <div style="margin-top:6px;color:#6b7280;font-weight:700;">Due: {{ $ticket->due_at ? $ticket->due_at->format('Y-m-d') : '—' }}</div>
            </div>
        </div>

        @if(!empty($ticket->description))
            <div style="margin-top:14px;">
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Description</div>
                <div style="margin-top:6px;white-space:pre-wrap;line-height:1.6;">{{ $ticket->description }}</div>
            </div>
        @endif

        @if($ticket->relationLoaded('files') && $ticket->files->count() > 0)
            <div style="margin-top:14px;">
                <div style="font-size:12px;opacity:0.7;font-weight:800;">Attachments</div>

                <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:12px;">
                    @foreach($ticket->files as $tf)
                        @php
                            $sf = $tf->storedFile ?? null;
                            $mime = $sf->mime_type ?? '';
                            $isImage = is_string($mime) && str_starts_with($mime, 'image/');
                        @endphp
                        @if($sf)
                            <div style="border:1px solid #e5e7eb;border-radius:12px;padding:10px;min-width:220px;max-width:320px;">
                                <div style="font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sf->original_filename }}</div>
                                <div style="color:#6b7280;font-weight:700;font-size:12px;margin-top:2px;">{{ $sf->mime_type ?? 'file' }}</div>
                                @if($isImage)
                                    <div style="margin-top:8px;">
                                        <img src="{{ route('tickets.files.inline', ['ticket' => $ticket->getKey(), 'storedFile' => $sf->getKey()]) }}" alt="{{ $sf->original_filename }}" style="max-width:100%;max-height:180px;border-radius:10px;border:1px solid #e5e7eb;" />
                                    </div>
                                @endif
                                <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                                    <a class="btn no-spa" href="{{ route('tickets.files.inline', ['ticket' => $ticket->getKey(), 'storedFile' => $sf->getKey()]) }}" target="_blank" style="background:#111827;color:#fff;padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:900;font-size:12px;">View</a>
                                    <a class="btn no-spa" href="{{ route('tickets.files.download', ['ticket' => $ticket->getKey(), 'storedFile' => $sf->getKey()]) }}" style="background:#6b7280;color:#fff;padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:900;font-size:12px;">Download</a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="card" style="margin-top:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:16px;font-weight:900;">Timeline</div>
                <div style="color:#6b7280;font-weight:700;">All movements, changes, and comments.</div>
            </div>
        </div>

        <div style="margin-top:12px;display:flex;flex-direction:column;gap:12px;">
            @forelse($timeline as $item)
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="font-weight:900;">{{ $item['label'] ?? 'Update' }}</div>
                        <div style="color:#6b7280;font-weight:800;">
                            {{ !empty($item['created_at']) ? \Illuminate\Support\Carbon::parse($item['created_at'])->format('Y-m-d H:i') : '' }}
                        </div>
                    </div>
                    <div style="margin-top:4px;color:#6b7280;font-weight:800;">By: {{ $item['actor'] ?? 'System' }}</div>

                    @if(!empty($item['details']))
                        <div style="margin-top:10px;white-space:pre-wrap;line-height:1.6;">{{ $item['details'] }}</div>
                    @endif

                    @if(!empty($item['attachments']) && is_array($item['attachments']))
                        <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:12px;">
                            @foreach($item['attachments'] as $att)
                                @php
                                    $mime = $att['mime_type'] ?? '';
                                    $isImage = is_string($mime) && str_starts_with($mime, 'image/');
                                @endphp
                                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:10px;min-width:220px;max-width:320px;background:#fafafa;">
                                    <div style="font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att['filename'] ?? 'Attachment' }}</div>
                                    <div style="color:#6b7280;font-weight:700;font-size:12px;margin-top:2px;">{{ $mime ?: 'file' }}</div>
                                    @if($isImage && !empty($att['inline_url']))
                                        <div style="margin-top:8px;">
                                            <img src="{{ $att['inline_url'] }}" alt="{{ $att['filename'] ?? 'image' }}" style="max-width:100%;max-height:180px;border-radius:10px;border:1px solid #e5e7eb;" />
                                        </div>
                                    @endif
                                    <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                                        @if(!empty($att['inline_url']))
                                            <a class="btn no-spa" href="{{ $att['inline_url'] }}" target="_blank" style="background:#111827;color:#fff;padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:900;font-size:12px;">View</a>
                                        @endif
                                        @if(!empty($att['download_url']))
                                            <a class="btn no-spa" href="{{ $att['download_url'] }}" style="background:#6b7280;color:#fff;padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:900;font-size:12px;">Download</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div style="color:#6b7280;font-weight:800;">No timeline records yet.</div>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-top:12px;">
        <div style="font-size:16px;font-weight:900;">Add Comment</div>
        <div style="color:#6b7280;font-weight:700;">This will be visible in the ticket timeline.</div>

        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data" style="margin-top:12px;display:flex;flex-direction:column;gap:10px;" data-ticket-attachments-form>
            @csrf
            <input type="hidden" name="draft_key" value="{{ $commentDraftKey ?? '' }}">

            <textarea
                name="body"
                rows="4"
                required
                maxlength="5000"
                style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:14px;"
                placeholder="Write an update..."
                data-ticket-attachments-paste
                data-ticket-attachments-upload-url="{{ route('tickets.uploads.comment-draft', $ticket) }}"
                data-ticket-attachments-delete-url="{{ route('tickets.uploads.comment-draft-delete', $ticket) }}"
                data-ticket-attachments-draft-key="{{ $commentDraftKey ?? '' }}"
                data-ticket-attachments-list="#comment-attachments-list"
            >{{ old('body') }}</textarea>

            <div id="comment-attachments-list" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:10px;"></div>

            <div>
                <label for="comment_attachments" style="font-size:13px;font-weight:800;">Attachments</label><br>
                <div style="position:relative;margin-top:8px;">
                    <input
                        type="file"
                        name="attachments[]"
                        id="comment_attachments"
                        multiple
                        accept=".pdf,.doc,.docx,.xlsx,.xls,.txt,.jpg,.jpeg,.png"
                        style="display:none;"
                        data-file-label-id="comment_file_label"
                        data-file-count-id="comment_file_count"
                    />
                    <label for="comment_attachments" style="
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
                        <span id="comment_file_label">Choose Files</span>
                    </label>
                    <span id="comment_file_count" style="margin-left:20px;font-size:14px;color:#6b7280;"></span>
                </div>
                <div style="color:#6b7280;font-weight:700;font-size:12px;margin-top:6px;">Max 20 MB per file. Allowed: PDF, DOC/DOCX, XLS/XLSX, TXT, PNG, JPG, JPEG. You can also paste an image into the comment box.</div>
            </div>

            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:8px;font-weight:800;color:#374151;">
                    <input type="checkbox" name="is_internal" value="1" checked>
                    Internal comment
                </label>

                <button type="submit" class="btn" style="background:#111827;color:#fff;padding:12px 18px;border:none;border-radius:10px;font-size:14px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                    <span data-feather="message-square"></span>
                    Add Comment
                </button>
            </div>
        </form>
    </div>

    @if($canForward)
        <div class="card" style="margin-top:12px;">
            <div>
                <div style="font-size:16px;font-weight:900;">Forward Ticket</div>
                <div style="color:#6b7280;font-weight:700;">This records a movement event and updates the assignee.</div>
            </div>

            <form method="POST" action="{{ route('tickets.forward', $ticket) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:12px;">
                @csrf

                <div>
                    <label for="to_user_id" style="font-size:13px;font-weight:800;">Forward To <span style="color:#dc2626;">*</span></label><br>
                    <select name="to_user_id" id="to_user_id" required style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:14px;">
                        <option value="" disabled selected>Select user</option>
                        @foreach($assignees as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->email ? ' (' . $u->email . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reason" style="font-size:13px;font-weight:800;">Reason (optional)</label><br>
                    <input type="text" name="reason" id="reason" value="{{ old('reason') }}" maxlength="1000" placeholder="Why are you forwarding?" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:14px;" />
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn" style="background:#22c55e;color:#fff;padding:12px 18px;border:none;border-radius:10px;font-size:14px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                        <span data-feather="send"></span>
                        Forward
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
