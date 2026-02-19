@extends('layouts.parent')

@section('title', 'Manage Profiles')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>Manage Child Profiles</h2>
        <button class="btn btn-primary" onclick="openCreateModal()">
            + Add New Child
        </button>
    </div>

    <div id="profiles-container" class="loading">
        Loading profiles...
    </div>
</div>

{{-- Create/Edit Profile Modal --}}
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Child</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form id="profileForm" onsubmit="saveProfile(event)">
            <input type="hidden" id="childId">

            <div class="form-group">
                <label for="name">Child's Name *</label>
                <input type="text" id="name" name="name" required>
                <div class="help-text">Enter your child's first name</div>
            </div>

            <div class="form-group">
                <label for="age">Age *</label>
                <input type="number" id="age" name="age" min="2" max="18" required>
                <div class="help-text">Age (2-18 years)</div>
            </div>

            <div class="form-group">
                <label for="pin">PIN Code *</label>
                <input type="text" id="pin" name="pin" maxlength="4" pattern="[0-9]{4}" required>
                <div class="help-text">4-digit PIN for child login (e.g., 1234)</div>
            </div>

            <div class="form-group">
                <label>Choose Avatar *</label>
                <div class="avatar-grid" id="avatarGrid">
                    <!-- Avatars will be populated here -->
                </div>
                <input type="hidden" id="avatar_image" name="avatar_image" required>
                <div class="help-text">Click to select an avatar</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let children = [];
let editingChildId = null;
let avatars = [];

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
    loadProfiles();
    loadAvatars();
});

async function loadProfiles() {
    try {
        const result = await api.parent.getChildren();
        children = result.children;
        renderProfiles();
    } catch (error) {
        notify.error('Failed to load profiles: ' + error.message);
        document.getElementById('profiles-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load profiles</p></div>';
    }
}

async function loadAvatars() {
    try {
        const result = await api.parent.getAvatars();
        avatars = result.avatars;
    } catch (error) {
        console.error('Failed to load avatars:', error);
        // Fallback to default avatars if API fails
        avatars = [
            'princess_2.png',
            'princess_3.png',
            'princess_3tr.png',
            'princess_laugh.png',
            'knight_girl_2.png',
            'knight_girl_3.png',
            'knight_girl_4.png'
        ];
    }
}

function renderProfiles() {
    const container = document.getElementById('profiles-container');

    if (children.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">👨‍👩‍👧‍👦</div>
                <p>No child profiles yet. Add your first child to get started!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="profiles-grid">' +
        children.map(child => createProfileCard(child)).join('') +
        '</div>';
}

function createProfileCard(child) {
    const traitsHtml = child.traits && child.traits.length > 0
        ? `<div class="profile-traits">
                <h4>Character Traits</h4>
                ${child.traits.map(trait => `
                    <div class="trait-row">
                        <div class="trait-header">
                            <span class="trait-label">
                                ${trait.icon || '⭐'} ${escapeHtml(trait.name)}
                            </span>
                            <span class="trait-level">Level ${trait.level || 1}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${trait.progress_percentage || 0}%"></div>
                        </div>
                    </div>
                `).join('')}
           </div>`
        : '';

    return `
        <div class="profile-card">
            <div class="profile-header">
                <img src="${child.avatar_image ? '/hero-habits-laravel-full/public/Assets/Profile/' + child.avatar_image : '/hero-habits-laravel-full/public/Assets/Profile/princess_3tr.png'}"
                     alt="${escapeHtml(child.name)}"
                     class="profile-avatar">
                <div class="profile-info">
                    <h3 class="profile-name">${escapeHtml(child.name)}</h3>
                    <p class="profile-age">${child.age} years old</p>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-label">Level</div>
                    <div class="stat-value level">⭐ ${child.level || 1}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Gold Balance</div>
                    <div class="stat-value">💰 ${child.gold_balance || 0}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">PIN Code</div>
                    <div class="stat-value" style="font-size: 1.2rem; color: #666;">****</div>
                </div>
            </div>

            ${traitsHtml}

            <div class="profile-actions">
                <button class="btn btn-secondary btn-small" onclick="editProfile(${child.id})">
                    ✏️ Edit
                </button>
                <button class="btn btn-secondary btn-small" onclick="viewHistory(${child.id})">
                    📊 History
                </button>
                <button class="btn btn-danger btn-small" onclick="deleteProfile(${child.id})">
                    🗑️ Delete
                </button>
            </div>
        </div>
    `;
}

function openCreateModal() {
    editingChildId = null;
    document.getElementById('modalTitle').textContent = 'Add New Child';
    document.getElementById('profileForm').reset();
    document.getElementById('childId').value = '';
    renderAvatarGrid();
    document.getElementById('profileModal').classList.add('show');
}

function editProfile(id) {
    const child = children.find(c => c.id === id);
    if (!child) return;

    editingChildId = id;
    document.getElementById('modalTitle').textContent = 'Edit Profile';
    document.getElementById('childId').value = child.id;
    document.getElementById('name').value = child.name;
    document.getElementById('age').value = child.age;
    document.getElementById('pin').value = child.pin || '';
    document.getElementById('avatar_image').value = child.avatar_image || '';
    renderAvatarGrid(child.avatar_image);
    document.getElementById('profileModal').classList.add('show');
}

function renderAvatarGrid(selectedAvatar = null) {
    const grid = document.getElementById('avatarGrid');
    grid.innerHTML = avatars.map(avatar => {
        const isSelected = avatar === selectedAvatar;
        return `
            <img src="/hero-habits-laravel-full/public/Assets/Profile/${avatar}"
                 alt="${avatar}"
                 class="avatar-option ${isSelected ? 'selected' : ''}"
                 onclick="selectAvatar('${avatar}')">
        `;
    }).join('');
}

function selectAvatar(avatar) {
    document.getElementById('avatar_image').value = avatar;
    document.querySelectorAll('.avatar-option').forEach(el => {
        el.classList.remove('selected');
    });
    event.target.classList.add('selected');
}

function closeModal() {
    document.getElementById('profileModal').classList.remove('show');
    editingChildId = null;
}

async function saveProfile(event) {
    event.preventDefault();

    const formData = {
        name: document.getElementById('name').value,
        age: parseInt(document.getElementById('age').value),
        pin: document.getElementById('pin').value,
        avatar_image: document.getElementById('avatar_image').value
    };

    if (!formData.avatar_image) {
        notify.error('Please select an avatar');
        return;
    }

    try {
        if (editingChildId) {
            await api.parent.updateChild(editingChildId, formData);
            notify.success('Profile updated successfully!');
        } else {
            await api.parent.createChild(formData);
            notify.success('Child profile created successfully!');
        }

        closeModal();
        await loadProfiles();
    } catch (error) {
        notify.error('Failed to save profile: ' + error.message);
    }
}

function viewHistory(id) {
    window.location.href = `/parent/children/${id}/history`;
}

async function deleteProfile(id) {
    const child = children.find(c => c.id === id);
    if (!confirm(`Are you sure you want to delete ${child.name}'s profile? This will also delete all their quest history and cannot be undone.`)) {
        return;
    }

    try {
        await api.parent.deleteChild(id);
        notify.success('Profile deleted successfully');
        await loadProfiles();
    } catch (error) {
        notify.error('Failed to delete profile: ' + error.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// PIN input validation (numbers only)
document.addEventListener('DOMContentLoaded', () => {
    const pinInput = document.getElementById('pin');
    if (pinInput) {
        pinInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
        });
    }
});
</script>
@endpush
