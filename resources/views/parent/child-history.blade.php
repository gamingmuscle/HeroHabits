@extends('layouts.parent')

@section('title', $child->name . ' - Quest History')

@section('content')
<div class="content-box">
    <h2 style="color: var(--purple); margin-bottom: 20px;">{{ $child->name }}'s Quest History</h2>
    <p>This page will load quest history using the API endpoints.</p>
    <p><strong>API Endpoint:</strong> GET /api/parent/children/{{ $child->id }}/quest-history</p>

    <div id="history-container">
        <p>Loading quest history...</p>
    </div>

    <button class="btn btn-purple" onclick="window.location.href='{{ route('parent.dashboard') }}'">
        Back to Dashboard
    </button>
</div>
@endsection

@push('scripts')
<script>
// Fetch quest history using API client
api.parent.getChildHistory({{ $child->id }})
    .then(data => {
        const container = document.getElementById('history-container');
        if (data.success) {
            container.innerHTML = '<p>Found ' + data.completions.length + ' quest completions. Full UI implementation coming in next phase.</p>';
        } else {
            container.innerHTML = '<p>Error loading quest history.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('history-container').innerHTML = '<p>Error loading history: ' + error.message + '</p>';
    });
</script>
@endpush


