/**
 * Hero Habits API Client
 * Centralized API communication utilities
 */

class HeroHabitsAPI {
    constructor(prefix) {
        this.baseUrl = prefix+'/api';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
		console.log(this.baseUrl);
    }

    /**
     * Make API request
     */
    async request(method, endpoint, data = null) {
        const url = `${this.baseUrl}${endpoint}`;
		console.log('URL'+url);
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            credentials: 'same-origin'
        };

        if (data && method.toUpperCase() !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
			console.log('URL'+url);
            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Convenience methods
    async get(endpoint) {
		console.log('GET '+endpoint);
        return this.request('GET', endpoint);
    }

    async post(endpoint, data) {
		console.log('POST '+endpoint);
        return this.request('POST', endpoint, data);
    }

    async put(endpoint, data) {
		console.log('PUT '+endpoint);
        return this.request('PUT', endpoint, data);
    }

    async delete(endpoint) {
		console.log('DELETE '+endpoint);
        return this.request('DELETE', endpoint);
    }

    // Parent API Methods
    parent = {
        // Quests
        getQuests: () => this.get('/parent/quests'),
        createQuest: (data) => this.post('/parent/quests', data),
        updateQuest: (id, data) => this.put(`/parent/quests/${id}`, data),
        deleteQuest: (id) => this.delete(`/parent/quests/${id}`),
        toggleQuest: (id) => this.post(`/parent/quests/${id}/toggle`),

        // Treasures
        getTreasures: () => this.get('/parent/treasures'),
        createTreasure: (data) => this.post('/parent/treasures', data),
        updateTreasure: (id, data) => this.put(`/parent/treasures/${id}`, data),
        deleteTreasure: (id) => this.delete(`/parent/treasures/${id}`),
        toggleTreasure: (id) => this.post(`/parent/treasures/${id}/toggle`),
        getPurchases: () => this.get('/parent/treasures/purchases'),

        // Children
        getChildren: () => this.get('/parent/children'),
        createChild: (data) => this.post('/parent/children', data),
        updateChild: (id, data) => this.put(`/parent/children/${id}`, data),
        deleteChild: (id) => this.delete(`/parent/children/${id}`),
        getChildHistory: (id) => this.get(`/parent/children/${id}/quest-history`),
        getAvatars: () => this.get('/parent/children/avatars'),

        // Approvals
        getApprovals: () => this.get('/parent/approvals'),
        acceptApproval: (id) => this.post(`/parent/approvals/${id}/accept`),
        denyApproval: (id) => this.post(`/parent/approvals/${id}/deny`),
        bulkAccept: (ids) => this.post('/parent/approvals/bulk-accept', { completion_ids: ids }),
        bulkDeny: (ids) => this.post('/parent/approvals/bulk-deny', { completion_ids: ids }),

        // Dashboard
        getDashboard: () => this.get('/parent/dashboard'),
        getChartData: (period = 7) => this.get(`/parent/dashboard/chart-data?period=${period}`),
        getStats: () => this.get('/parent/dashboard/stats'),

        // Traits
        getTraits: () => this.get('/parent/traits'),
        getChildTraits: (id) => this.get(`/parent/children/${id}/traits`)
    };

    // Child API Methods
    child = {
        // Quests
        getQuests: () => this.get('/child/quests'),
        completeQuest: (id) => this.post(`/child/quests/${id}/complete`),
        getHistory: () => this.get('/child/quests/history'),
        getPending: () => this.get('/child/quests/pending'),
        getStats: () => this.get('/child/quests/stats'),

        // Traits
        getTraits: () => this.get('/child/traits'),

        // Treasures
        getTreasures: () => this.get('/child/treasures'),
        getTreasure: (id) => this.get(`/child/treasures/${id}`),
        purchaseTreasure: (id) => this.post(`/child/treasures/${id}/purchase`),
        getPurchases: () => this.get('/child/treasures/purchases/history'),
        getPurchaseStats: () => this.get('/child/treasures/purchases/stats'),

        // Calendar
        getCurrentCalendar: () => this.get('/child/calendar/current'),
        getCalendar: (year, month) => this.get(`/child/calendar/${year}/${month}`),
        getDay: (year, month, day) => this.get(`/child/calendar/${year}/${month}/${day}`),
        getRange: (startDate, endDate) => this.get(`/child/calendar/range?start_date=${startDate}&end_date=${endDate}`)
    };
}

// Global instance
const api = new HeroHabitsAPI('/hero-habits-laravel-full/public');
