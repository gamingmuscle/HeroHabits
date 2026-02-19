@extends('layouts.child')

@section('title', 'My Calendar')

@section('content')
<div class="content-box">
    <h2 style="color: var(--purple); margin-bottom: 20px;">My Calendar</h2>
    <p>This page will load your quest completion calendar using the API endpoints.</p>
    <p><strong>API Endpoint:</strong> GET /api/child/calendar/current</p>

    <div id="calendar-container">
        <p>Loading calendar...</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fetch calendar using API client
api.child.getCurrentCalendar()
    .then(data => {
        const container = document.getElementById('calendar-container');
        if (data.success) {
            const cal = data.calendar;
            container.innerHTML = '<p>Calendar for ' + cal.month_name + '. You have ' + cal.pending_count + ' pending approvals. Full calendar UI implementation coming in next phase.</p>';
        } else {
            container.innerHTML = '<p>Error loading calendar.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('calendar-container').innerHTML = '<p>Error loading calendar: ' + error.message + '</p>';
    });
</script>
@endpush
