@extends('layouts.parent')

@section('title', 'Dashboard')



@section('content')
<div class="content-box">
  @if ($totalPending > 0)
    <div class="stats-grid">
      <div class="stat-card" style="border-left: 5px solid #ff6b6b;">
        <div class="stat-number">{{ $totalPending }}</div>
        <div class="stat-label">Pending Approvals</div>
        <button class="btn btn-gold" style="margin-top: 15px;" onclick="window.location.href='{{ route('parent.approvals') }}'">
          Review Now
        </button>
      </div>
    </div>
  @endif

  {{-- Quest Completion Chart --}}
  @if (count($children) > 0)
    <div class="chart-container">
      <div class="chart-header">
        <h3>Quest Completions</h3>
        <div class="period-selector">
          <button class="period-btn {{ $chartPeriod == 7 ? 'active' : '' }}"
                  onclick="window.location.href='?period=7'">
            Last 7 Days
          </button>
          <button class="period-btn {{ $chartPeriod == 30 ? 'active' : '' }}"
                  onclick="window.location.href='?period=30'">
            Last 30 Days
          </button>
        </div>
      </div>
      <canvas id="questChart"></canvas>
    </div>
  @endif

  <h3 style="color: var(--purple); margin-bottom: 20px;">Your Children</h3>

  @if (count($children) > 0)
    <div class="children-grid">
      @foreach ($children as $child)
        <div class="child-card">
          <div class="child-header">
            <img src="{{ asset('Assets/Profile/' . $child->avatar_image) }}"
                 alt="Avatar" class="child-avatar">
            <div class="child-info">
              <h3>{{ $child->name }}</h3>
              <p style="margin: 5px 0; color: #666;">Age: {{ $child->age }}</p>
            </div>
          </div>

          <div class="child-stats">
            <div class="child-stat">
              <span>Gold Balance:</span>
              <span class="gold">{{ $child->gold_balance }} ⭐</span>
            </div>
            <div class="child-stat">
              <span>Completed Quests:</span>
              <span>{{ $child->completed_quests }}</span>
            </div>
            <div class="child-stat">
              <span>Completed Today:</span>
              <span>{{ $child->completed_today }}</span>
            </div>
            @if ($child->pending_approvals > 0)
              <div class="child-stat">
                <span>Pending Approval:</span>
                <span class="pending-badge">{{ $child->pending_approvals }}</span>
              </div>
            @endif
          </div>

          <button class="btn btn-purple" style="width: 100%;"
                  onclick="window.location.href='{{ route('parent.child-history', $child->id) }}'">
            View Quest History
          </button>
        </div>
      @endforeach
    </div>
  @else
    <div class="stat-card">
      <p>No child profiles yet.</p>
      <button class="btn btn-purple" onclick="window.location.href='{{ route('parent.profiles') }}'">
        Add Your First Child
      </button>
    </div>
  @endif
</div>
@endsection

@push('scripts-before')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('scripts')
<script>
@if (count($children) > 0)
  const chartData = @json($chartData);
  const dateLabels = @json($dateLabels);

  // Generate colors for each child (alternating purple/gold shades)
  const colorPalette = [
    { bg: 'rgba(126, 87, 194, 0.8)', border: 'rgba(126, 87, 194, 1)' },
    { bg: 'rgba(255, 202, 40, 0.8)', border: 'rgba(255, 202, 40, 1)' },
    { bg: 'rgba(149, 117, 205, 0.8)', border: 'rgba(149, 117, 205, 1)' },
    { bg: 'rgba(255, 179, 0, 0.8)', border: 'rgba(255, 179, 0, 1)' },
    { bg: 'rgba(94, 53, 177, 0.8)', border: 'rgba(94, 53, 177, 1)' },
    { bg: 'rgba(255, 235, 59, 0.8)', border: 'rgba(255, 235, 59, 1)' }
  ];

  // Prepare datasets for Chart.js
  const datasets = chartData.map((child, index) => {
    const colors = colorPalette[index % colorPalette.length];
    return {
      label: child.name,
      data: child.data,
      backgroundColor: colors.bg,
      borderColor: colors.border,
      borderWidth: 2
    };
  });

  // Create the chart
  const ctx = document.getElementById('questChart').getContext('2d');
  const questChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dateLabels,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        x: {
          stacked: true,
          grid: { display: false }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            precision: 0
          },
          grid: { color: 'rgba(0, 0, 0, 0.05)' },
          title: {
            display: true,
            text: 'Number of Quests Completed',
            font: { size: 14, weight: 'bold' }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 15,
            font: { size: 13, weight: 'bold' },
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              label += context.parsed.y + ' quest' + (context.parsed.y !== 1 ? 's' : '');
              return label;
            }
          }
        }
      }
    }
  });
@endif
</script>
@endpush
