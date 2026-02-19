# Hero Habits Laravel Application Guide

A comprehensive guide to understanding the Laravel application structure, how components interact, and where to find and modify files.

---

## Table of Contents

1. [Laravel MVC Architecture Overview](#laravel-mvc-architecture-overview)
2. [Directory Structure](#directory-structure)
3. [How the UI Interacts with the API](#how-the-ui-interacts-with-the-api)
4. [Request Flow](#request-flow)
5. [File Organization by Feature](#file-organization-by-feature)
6. [Common Patterns](#common-patterns)
7. [Adding New Features](#adding-new-features)
8. [Database Interactions](#database-interactions)
9. [Authentication System](#authentication-system)
10. [Configuration Files](#configuration-files)

---

## Laravel MVC Architecture Overview

Laravel follows the **Model-View-Controller (MVC)** pattern:

### Model (M)
**Location**: `app/Models/`

- Represents database tables as PHP classes
- Handles database queries and relationships
- Contains business logic related to data

**Example**: `app/Models/Quest.php` represents the `quests` table

### View (V)
**Location**: `resources/views/`

- HTML templates using Blade templating engine
- Displays data to users
- File extension: `.blade.php`

**Example**: `resources/views/child/quests.blade.php` displays the child quest page

### Controller (C)
**Location**: `app/Http/Controllers/`

- Handles user requests
- Coordinates between Models and Views
- Returns responses (views or JSON)

**Example**: `app/Http/Controllers/Api/Child/QuestController.php` handles quest API requests

---

## Directory Structure

```
hero-habits-laravel-full/
│
├── app/                          # Core application code
│   ├── Console/                  # Artisan commands
│   │   └── Commands/             # Custom CLI commands
│   ├── Http/                     # HTTP layer
│   │   ├── Controllers/          # Request handlers
│   │   │   ├── Api/              # API controllers (return JSON)
│   │   │   │   ├── Parent/       # Parent API endpoints
│   │   │   │   └── Child/        # Child API endpoints
│   │   │   ├── Auth/             # Authentication controllers
│   │   │   └── Web/              # Web view controllers (return HTML)
│   │   │       ├── Parent/       # Parent view pages
│   │   │       └── Child/        # Child view pages
│   │   ├── Middleware/           # Request filters
│   │   ├── Requests/             # Form validation classes
│   │   │   ├── Auth/             # Login/registration validation
│   │   │   ├── Parent/           # Parent request validation
│   │   │   └── Child/            # Child request validation
│   │   ├── Resources/            # API response transformers
│   │   └── Traits/               # Reusable code snippets
│   └── Models/                   # Database models
│
├── config/                       # Configuration files
│   ├── app.php                   # Application settings
│   ├── auth.php                  # Authentication guards
│   ├── database.php              # Database connections
│   └── herohabits.php            # Custom app configuration
│
├── database/                     # Database files
│   └── migrations/               # Database schema definitions
│
├── public/                       # Publicly accessible files
│   ├── js/                       # JavaScript files
│   │   ├── api-client.js         # API communication
│   │   └── notifications.js      # Toast notifications
│   └── Assets/                   # Images, fonts, CSS
│
├── resources/                    # Frontend resources
│   └── views/                    # Blade templates
│       ├── layouts/              # Page layouts
│       │   ├── parent.blade.php  # Parent portal layout
│       │   ├── child.blade.php   # Child portal layout
│       │   └── auth.blade.php    # Login page layout
│       ├── components/           # Reusable UI components
│       ├── parent/               # Parent portal pages
│       ├── child/                # Child portal pages
│       └── auth/                 # Login/registration pages
│
├── routes/                       # URL routing
│   ├── web.php                   # Web routes (return HTML)
│   └── api.php                   # API routes (return JSON)
│
└── storage/                      # Application storage
    └── logs/                     # Application logs
```

---

## How the UI Interacts with the API

### Two-Layer Architecture

The app uses a **two-layer approach**:

1. **Web Layer**: Serves HTML pages
2. **API Layer**: Provides data as JSON

### Example: Child Quest Page

#### Step 1: User visits `/child/quests`

**Route** (`routes/web.php`):
```php
Route::get('/quests', [ChildQuestsViewController::class, 'index'])->name('quests');
```

**View Controller** (`app/Http/Controllers/Web/Child/QuestsViewController.php`):
```php
public function index(Request $request)
{
    $child = \Auth::guard('child')->user();

    return view('child.quests', [
        'pageTitle' => 'My Quests',
        'child' => $child
    ]);
}
```

**What happens**:
- Renders the Blade template with basic page structure
- Passes the authenticated child to the view
- Returns HTML to the browser

#### Step 2: Page loads and JavaScript fetches data

**Blade Template** (`resources/views/child/quests.blade.php`):
```html
<div id="quests-container" class="loading">
    Loading your quests...
</div>

<script>
async function loadQuests() {
    const result = await api.child.getQuests();
    quests = result.quests;
    renderQuests();
}
</script>
```

**What happens**:
- Page displays loading message
- JavaScript calls API to fetch quests
- Renders quests dynamically

#### Step 3: JavaScript calls API

**API Client** (`public/js/api-client.js`):
```javascript
child = {
    getQuests: () => this.get('/child/quests'),
    // ...
}
```

**What happens**:
- Makes AJAX request to `/api/child/quests`
- Sends authentication cookies automatically

#### Step 4: API returns data

**API Route** (`routes/api.php`):
```php
Route::get('/quests', [ChildQuestController::class, 'index']);
```

**API Controller** (`app/Http/Controllers/Api/Child/QuestController.php`):
```php
public function index(Request $request)
{
    $child = \Auth::guard('child')->user();

    $quests = Quest::where('user_id', $child->user_id)
        ->active()
        ->get();

    return response()->json([
        'success' => true,
        'quests' => $quests,
    ]);
}
```

**What happens**:
- Gets authenticated child
- Queries database for quests
- Returns JSON response

#### Step 5: JavaScript receives and displays data

```javascript
function renderQuests() {
    container.innerHTML = quests.map(quest =>
        createQuestCard(quest)
    ).join('');
}
```

**What happens**:
- Replaces loading message with quest cards
- User sees their quests

---

## Request Flow

### Visual Flow Diagram

```
Browser
   │
   ├─ GET /child/quests
   │     │
   │     ▼
   │  routes/web.php ──→ ChildQuestsViewController
   │     │                        │
   │     │                        ▼
   │     │                  Returns Blade view
   │     │                        │
   │     ◀────────────────────────┘
   │     │
   │  Browser renders HTML
   │     │
   │     ├─ JavaScript executes
   │     │     │
   │     │     ├─ AJAX: GET /api/child/quests
   │     │     │     │
   │     │     │     ▼
   │     │     │  routes/api.php ──→ ChildQuestController::index()
   │     │     │     │                        │
   │     │     │     │                        ▼
   │     │     │     │                   Query database
   │     │     │     │                        │
   │     │     │     │                        ▼
   │     │     │     │                   Quest Model
   │     │     │     │                        │
   │     │     │     │                        ▼
   │     │     │     │                   Returns data
   │     │     │     │                        │
   │     │     │     ◀────────────────────────┘
   │     │     │     │
   │     │     │  Returns JSON response
   │     │     │     │
   │     │     ◀─────┘
   │     │     │
   │     │  JavaScript updates DOM
   │     │     │
   │     ▼     ▼
   │  User sees quests
```

---

## File Organization by Feature

### Quest Management Feature

```
Quests Feature Files:
│
├── Database
│   ├── migrations/2024_01_01_000003_create_quests_table.php
│   └── migrations/2024_01_01_000005_create_quest_completions_table.php
│
├── Model
│   ├── app/Models/Quest.php
│   └── app/Models/QuestCompletion.php
│
├── Controllers
│   ├── API Controllers
│   │   ├── app/Http/Controllers/Api/Parent/QuestController.php
│   │   └── app/Http/Controllers/Api/Child/QuestController.php
│   └── View Controllers
│       ├── app/Http/Controllers/Web/Parent/QuestsViewController.php
│       └── app/Http/Controllers/Web/Child/QuestsViewController.php
│
├── Validation
│   ├── app/Http/Requests/Parent/StoreQuestRequest.php
│   ├── app/Http/Requests/Parent/UpdateQuestRequest.php
│   └── app/Http/Requests/Child/CompleteQuestRequest.php
│
├── Views
│   ├── resources/views/parent/quests.blade.php
│   └── resources/views/child/quests.blade.php
│
└── Routes
    ├── routes/api.php (API endpoints)
    └── routes/web.php (page URLs)
```

---

## Common Patterns

### Pattern 1: Creating a New Page

**Example**: Adding a "Reports" page for parents

#### 1. Create the route (`routes/web.php`)
```php
Route::get('/reports', [ReportsViewController::class, 'index'])
    ->name('reports');
```

#### 2. Create the view controller (`app/Http/Controllers/Web/Parent/ReportsViewController.php`)
```php
<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportsViewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('parent.reports', [
            'pageTitle' => 'Reports',
            'currentPage' => 'reports',
        ]);
    }
}
```

#### 3. Create the view (`resources/views/parent/reports.blade.php`)
```blade
@extends('layouts.parent')

@section('title', 'Reports')

@section('content')
<div class="content-box">
    <h2>Reports</h2>
    <div id="reports-container">
        Loading reports...
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadReports);

async function loadReports() {
    const result = await api.parent.getReports();
    renderReports(result.reports);
}

function renderReports(reports) {
    // Render logic here
}
</script>
@endpush
```

#### 4. Create API endpoint (`routes/api.php`)
```php
Route::get('/reports', [ReportsController::class, 'index'])
    ->name('reports.index');
```

#### 5. Create API controller (`app/Http/Controllers/Api/Parent/ReportsController.php`)
```php
<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Query database
        $reports = []; // Your query here

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }
}
```

#### 6. Add to API client (`public/js/api-client.js`)
```javascript
parent = {
    // ... existing methods
    getReports: () => this.get('/parent/reports'),
}
```

---

### Pattern 2: Adding an API Endpoint

**Example**: Delete a quest

#### 1. Add route (`routes/api.php`)
```php
Route::delete('/quests/{id}', [QuestController::class, 'destroy'])
    ->name('quests.destroy');
```

#### 2. Add controller method
```php
public function destroy(Request $request, $id)
{
    $user = $request->user();

    $quest = Quest::where('id', $id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    $quest->delete();

    return response()->json([
        'success' => true,
        'message' => 'Quest deleted successfully',
    ]);
}
```

#### 3. Add to API client
```javascript
deleteQuest: (id) => this.delete(`/parent/quests/${id}`)
```

#### 4. Call from JavaScript
```javascript
async function deleteQuest(id) {
    if (!confirm('Delete this quest?')) return;

    try {
        await api.parent.deleteQuest(id);
        notify.success('Quest deleted');
        loadQuests(); // Refresh list
    } catch (error) {
        notify.error('Failed to delete quest');
    }
}
```

---

### Pattern 3: Form Validation

When accepting user input, create a **Form Request** class:

#### 1. Create request class (`app/Http/Requests/Parent/StoreQuestRequest.php`)
```php
<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or check permissions
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'gold_reward' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Quest title is required.',
            'gold_reward.min' => 'Gold reward must be at least 1.',
        ];
    }
}
```

#### 2. Use in controller
```php
use App\Http\Requests\Parent\StoreQuestRequest;

public function store(StoreQuestRequest $request)
{
    // $request->validated() contains only valid data
    $quest = Quest::create($request->validated());

    return response()->json([
        'success' => true,
        'quest' => $quest,
    ]);
}
```

**Benefits**:
- Validation happens automatically before controller runs
- Invalid requests get rejected with error messages
- Controller only receives valid data

---

## Database Interactions

### Using Eloquent (Recommended)

Laravel's **Eloquent ORM** lets you interact with the database using PHP objects instead of SQL.

#### Basic Queries

```php
// Get all active quests
$quests = Quest::where('is_active', true)->get();

// Get one quest by ID
$quest = Quest::find(1);

// Get quest or throw 404 error
$quest = Quest::findOrFail(1);

// Create new quest
$quest = Quest::create([
    'user_id' => 1,
    'title' => 'Clean your room',
    'gold_reward' => 10,
]);

// Update quest
$quest->update([
    'title' => 'Clean your room thoroughly',
]);

// Delete quest
$quest->delete();
```

#### Relationships

```php
// Get all quests for a user
$quests = $user->quests;

// Get quest's owner
$owner = $quest->user;

// Query with relationships
$quests = Quest::with('completions')->get();

// Count related records
$quest->completions()->count();
```

#### Complex Queries

```php
// Multiple conditions
$quests = Quest::where('user_id', 1)
    ->where('is_active', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Query relationships
$quests = Quest::whereHas('completions', function ($query) {
    $query->where('status', 'Pending');
})->get();

// Aggregates
$totalGold = QuestCompletion::where('child_id', 1)
    ->sum('gold_earned');
```

---

## Authentication System

### Two Guards

The app uses **two separate authentication guards**:

1. **'web' guard**: For parents (default)
2. **'child' guard**: For children

### Configuration (`config/auth.php`)

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',  // Uses User model
    ],
    'child' => [
        'driver' => 'session',
        'provider' => 'children',  // Uses Child model
    ],
],
```

### Getting Authenticated User

```php
// In parent controllers (web guard is default)
$user = $request->user();
$user = Auth::user();

// In child controllers (must specify guard)
$child = \Auth::guard('child')->user();
```

### Middleware

**Parent routes**:
```php
Route::middleware(['auth', 'parent'])->group(function () {
    // Parent-only routes
});
```

**Child routes**:
```php
Route::middleware(['child'])->group(function () {
    // Child-only routes
});
```

### Login Process

**Parent Login** (`app/Http/Controllers/Auth/ParentAuthController.php`):
```php
if (Auth::attempt($credentials)) {
    $request->session()->regenerate();
    return redirect()->route('parent.dashboard');
}
```

**Child Login** (`app/Http/Controllers/Auth/ChildAuthController.php`):
```php
Auth::guard('child')->login($child);
$request->session()->regenerate();
return redirect()->route('child.quests');
```

---

## Configuration Files

### `config/herohabits.php`

Custom application settings:

```php
return [
    'pagination' => [
        'default' => 20,
        'max' => 100,
    ],

    'quests' => [
        'max_gold_reward' => 1000,
    ],

    'registration' => [
        'invitation_only' => env('REGISTRATION_INVITATION_ONLY', false),
    ],
];
```

**Usage**:
```php
$maxReward = config('herohabits.quests.max_gold_reward');
```

### Environment Variables (`.env`)

Contains sensitive configuration:

```env
APP_NAME="Hero Habits"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=hero_habits
DB_USERNAME=root
DB_PASSWORD=secret

REGISTRATION_INVITATION_ONLY=false
```

**Usage**:
```php
$appName = env('APP_NAME');
```

---

## Adding New Features

### Complete Example: Adding a "Badges" Feature

#### 1. Create Migration

```bash
php artisan make:migration create_badges_table
```

```php
Schema::create('badges', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('description');
    $table->string('icon');
    $table->integer('requirement');
    $table->timestamps();
});
```

Run: `php artisan migrate`

#### 2. Create Model

```bash
php artisan make:model Badge
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'requirement'];
}
```

#### 3. Create API Controller

```php
<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Badge;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = Badge::all();

        return response()->json([
            'success' => true,
            'badges' => $badges,
        ]);
    }
}
```

#### 4. Add Routes

**API** (`routes/api.php`):
```php
Route::get('/badges', [BadgeController::class, 'index']);
```

**Web** (`routes/web.php`):
```php
Route::get('/badges', [BadgesViewController::class, 'index']);
```

#### 5. Create View

```blade
@extends('layouts.parent')

@section('content')
<div id="badges-container"></div>
@endsection

@push('scripts')
<script>
async function loadBadges() {
    const result = await api.parent.getBadges();
    renderBadges(result.badges);
}
</script>
@endpush
```

#### 6. Update API Client

```javascript
parent = {
    getBadges: () => this.get('/parent/badges'),
}
```

---

## Key Takeaways

### 1. Separation of Concerns

- **Models**: Database logic
- **Controllers**: Request handling
- **Views**: Display logic
- **Requests**: Validation logic

### 2. Two-Layer Architecture

- **Web Layer**: Serves HTML pages (routes/web.php)
- **API Layer**: Provides data as JSON (routes/api.php)

### 3. Always Use the Correct Guard

```php
// Parent
$user = $request->user();

// Child
$child = \Auth::guard('child')->user();
```

### 4. Follow Laravel Conventions

- Controllers in `app/Http/Controllers/`
- Models in `app/Models/`
- Views in `resources/views/`
- Routes in `routes/`

### 5. Validation in Request Classes

Don't validate in controllers - create dedicated Request classes.

---

## Common Tasks Reference

### Find where a page is rendered
1. Look in `routes/web.php` for the URL
2. Find the controller method
3. Check what view it returns

### Find where data comes from
1. Look in browser DevTools Network tab
2. Find the API endpoint URL
3. Look in `routes/api.php`
4. Find the controller method
5. Check the database query

### Add a new field to a table
1. Create migration: `php artisan make:migration add_field_to_table`
2. Add field in migration
3. Run: `php artisan migrate`
4. Add to model's `$fillable` array
5. Update forms/validation

### Debug issues
1. Check `storage/logs/laravel.log`
2. Use browser DevTools Console/Network tabs
3. Add `dd($variable)` in controller to dump data
4. Check database directly

---

## Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Blade Templates**: https://laravel.com/docs/blade
- **Eloquent ORM**: https://laravel.com/docs/eloquent
- **Routing**: https://laravel.com/docs/routing
- **Validation**: https://laravel.com/docs/validation

---

**Version**: 1.0
**Last Updated**: 2024
