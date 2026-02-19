@extends('layouts.child')

@section('title', 'My Quests')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>🎯 My Quests</h2>
        <p class="page-subtitle">Complete quests to earn gold!</p>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('available')">📋 Available</button>
        <button class="tab" onclick="switchTab('history')">📜 History</button>
        <button class="tab" onclick="switchTab('stats')">📊 Stats</button>
    </div>

    <div id="tab-available" class="tab-content active">
        <div id="quests-container" class="loading">
            Loading your quests...
        </div>
    </div>

    <div id="tab-history" class="tab-content">
        <div id="history-container" class="loading">
            Loading history...
        </div>
    </div>

    <div id="tab-stats" class="tab-content">
        <div id="stats-container" class="loading">
            Loading stats...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let quests = [];
let history = [];
let stats = {};
let currentTab = 'available';

document.addEventListener('DOMContentLoaded', () => {
    loadQuests();
});

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');

    currentTab = tab;

    // Load data if not loaded
    if (tab === 'history' && history.length === 0) {
        loadHistory();
    } else if (tab === 'stats' && !stats.total_completed) {
        loadStats();
    }
}

async function loadQuests() {
    try {
        const result = await api.child.getQuests();
        quests = result.quests;
        renderQuests();
    } catch (error) {
        notify.error('Failed to load quests: ' + error.message);
        document.getElementById('quests-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load quests</p></div>';
    }
}

function renderQuests() {
    const container = document.getElementById('quests-container');

    if (quests.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🎯</div>
                <h3>No Quests Available</h3>
                <p>Ask your parent to create some quests for you!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="quests-grid">' +
        quests.map(quest => createQuestCard(quest)).join('') +
        '</div>';
}

function createQuestCard(quest) {
    const cardClass = quest.completed_today
        ? (quest.today_status === 'Pending' ? 'quest-card pending-today' : 'quest-card completed-today')
        : 'quest-card';

    let statusBadge = '';
    if (quest.completed_today) {
        if (quest.today_status === 'Pending') {
            statusBadge = '<div class="status-badge badge-pending">⏳ Pending</div>';
        } else if (quest.today_status === 'Accepted') {
            statusBadge = '<div class="status-badge badge-completed">✓ Done</div>';
        }
    }

    return `
        <div class="${cardClass}">
            <div class="quest-title-section">
                <h3 class="quest-title">${escapeHtml(quest.title)}</h3>
                ${statusBadge}
            </div>
            <div class="quest-description-section">
                <p class="quest-description">${quest.description ? escapeHtml(quest.description) : 'No description'}</p>
            </div>
            <div class="quest-action-section">
                <div class="quest-reward">
                    ${quest.gold_reward} <img src="{{ asset('Assets/Icons & Logo/gold_coin.png')}}" Alt="Gold" class="gold-icon">
                </div>
                <button
                    class="btn-complete"
                    onclick="completeQuest(${quest.id})"
                    ${quest.completed_today ? 'disabled' : ''}>
                    ${quest.completed_today ? 'Done' : 'Complete'}
                </button>
            </div>
        </div>
    `;
}

async function completeQuest(id) {
    try {
        const result = await api.child.completeQuest(id);
        notify.success(result.message || 'Quest submitted for approval!');
        await loadQuests();

        // Show encouragement
        const quest = quests.find(q => q.id === id);
        if (quest) {
            setTimeout(() => {
                notify.info(`You'll earn ${quest.gold_reward} gold when approved! 🎉`);
            }, 1000);
        }
    } catch (error) {
        notify.error(error.message || 'Failed to complete quest');
    }
}

async function loadHistory() {
    try {
        const result = await api.child.getHistory();
        history = result.history || [];
        renderHistory();
    } catch (error) {
        notify.error('Failed to load history: ' + error.message);
        document.getElementById('history-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load history</p></div>';
    }
}

function renderHistory() {
    const container = document.getElementById('history-container');

    if (history.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📜</div>
                <h3>No History Yet</h3>
                <p>Complete some quests to see your history!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = history.map(item => `
        <div class="history-item">
            <div class="history-info">
                <div class="history-quest-title">${escapeHtml(item.quest?.title || 'Quest')}</div>
                <div class="history-date">${formatDate(item.completion_date)} - ${item.status}</div>
            </div>
            <div class="history-gold">⭐ ${item.gold_earned}</div>
        </div>
    `).join('');
}

async function loadStats() {
    try {
        const result = await api.child.getStats();
        stats = result.stats;
        renderStats();
    } catch (error) {
        notify.error('Failed to load stats: ' + error.message);
        document.getElementById('stats-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load stats</p></div>';
    }
}

function renderStats() {
    const container = document.getElementById('stats-container');

    container.innerHTML = `
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">${stats.total_completed || 0}</div>
                <div class="stat-label">Total Quests Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${stats.completed_today || 0}</div>
                <div class="stat-label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${stats.completed_this_week || 0}</div>
                <div class="stat-label">This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">⭐ ${stats.total_gold_earned || 0}</div>
                <div class="stat-label">Total Gold Earned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${stats.pending || 0}</div>
                <div class="stat-label">Pending Approval</div>
            </div>
        </div>

        ${stats.total_completed > 0 ? `
            <div style="text-align: center; margin-top: 40px; padding: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🏆</div>
                <h3 style="color: var(--purple); margin: 0 0 10px 0;">Amazing Work!</h3>
                <p style="font-size: 1.2rem; color: #666;">Keep completing quests to earn more gold!</p>
            </div>
        ` : ''}
    `;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
