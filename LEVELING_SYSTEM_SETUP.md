# Leveling System Setup Guide

## Overview

The leveling system adds RPG-style progression to Hero Habits, where children earn experience points (XP) and level up both their overall character and individual character traits through quest completion.

## Features

### 1. Child Overall Level
- Each child starts at **Level 1** with **0 XP**
- XP is awarded when parents approve quest completions
- XP required per level: `level² × 100` (exponential curve)
  - Level 1 → 2: 100 XP
  - Level 2 → 3: 300 XP
  - Level 3 → 4: 500 XP
  - Level 4 → 5: 700 XP
  - And so on...

### 2. Character Traits
Five core character traits that children can develop:
- **🦁 Bravery** - The courage to face challenges and take risks despite fear
- **💖 Kindness** - Showing compassion and empathy towards others
- **🎯 Responsibility** - Being accountable for one's actions and duties
- **💪 Perseverance** - Finishing tasks, not giving up
- **🔍 Curiosity** - Asking questions, learning and exploring

### 3. Trait Leveling
- Each trait also has its own level (starts at Level 1)
- Trait XP required per level: `level² × 50`
  - Level 1 → 2: 50 XP
  - Level 2 → 3: 150 XP
  - Level 3 → 4: 250 XP
  - And so on...

### 4. Quest-Trait Tagging
- Parents can tag quests with 1 or more traits they reinforce
- When a quest is approved, XP is split equally among tagged traits
- Example: 100 XP quest tagged with Bravery and Responsibility awards 50 XP to each trait

### 5. XP Award Formula
- **XP Awarded** = Gold Reward × 10
- Example: A quest worth 10 gold awards 100 XP
- This XP goes to:
  1. Child's overall level (full amount)
  2. Each tagged trait (split equally)

## Installation Steps

### Step 1: Run Database Migrations

Open your terminal in the project root directory and run:

```bash
php artisan migrate
```

This will create/update the following:
1. Add `level` and `experience_points` columns to `children` table
2. Create `traits` table for trait definitions
3. Create `child_traits` pivot table for child trait progress
4. Create `quest_traits` pivot table for quest-trait associations
5. Add `experience_points_awarded` column to `quest_completions` table

### Step 2: Seed Initial Traits

Run the trait seeder to populate the 5 character traits:

```bash
php artisan db:seed --class=TraitSeeder
```

You should see: `✅ 5 traits seeded successfully!`

### Step 3: Verify Installation

1. **Check Traits API**
   - Navigate to: `http://127.0.0.1/hero-habits-laravel-full/api/parent/traits`
   - You should see JSON with 5 traits

2. **Check Child Profiles**
   - Log in as a parent
   - Go to Profiles page
   - You should see a "Level" stat box on each child profile card

3. **Check Quest Form**
   - Go to Quests page
   - Click "Create New Quest" or edit an existing quest
   - You should see a "Character Traits" section with checkboxes

## How It Works

### For Parents

#### Creating/Editing Quests:
1. Navigate to the Quests page
2. Create a new quest or edit an existing one
3. Fill in the quest details (title, description, gold reward)
4. **NEW:** Select one or more character traits this quest reinforces
5. Save the quest

#### Approving Quest Completions:
1. Navigate to the Approvals page
2. Review pending quest completions
3. Click "Accept" to approve a completion
4. **NEW:** The system will:
   - Award gold to the child
   - Award XP to the child's overall level
   - Split XP among the quest's tagged traits
   - Display level-up notifications if any levels were gained

#### Viewing Child Progress:
1. Navigate to the Profiles page
2. Each child card now displays:
   - **Level** - Child's overall character level
   - **Gold Balance** - Current gold available
   - **Character Traits** - Progress bars showing each trait's level and XP progress

### For Children

Children will see their level and trait progression reflected in:
- Their profile stats (visible in the child portal)
- Quest completion feedback showing XP earned

## Database Schema

### Tables Modified/Created:

#### `children` table (modified)
```sql
- level (integer, default: 1)
- experience_points (integer, default: 0)
```

#### `traits` table (new)
```sql
- id
- name (string, unique)
- description (text)
- icon (string, emoji)
- sort_order (integer)
- created_at, updated_at
```

#### `child_traits` table (new)
```sql
- id
- child_id (foreign key)
- trait_id (foreign key)
- level (integer, default: 1)
- experience_points (integer, default: 0)
- created_at, updated_at
UNIQUE(child_id, trait_id)
```

#### `quest_traits` table (new)
```sql
- id
- quest_id (foreign key)
- trait_id (foreign key)
- created_at, updated_at
UNIQUE(quest_id, trait_id)
```

#### `quest_completions` table (modified)
```sql
- experience_points_awarded (integer, nullable)
```

## API Endpoints

### New Endpoints:

1. **GET** `/api/parent/traits`
   - Returns all available traits
   - Used in quest form to display trait selection

2. **GET** `/api/parent/children/{id}/traits`
   - Returns a child's trait progress
   - Includes level, XP, and progress percentage for each trait

### Modified Endpoints:

1. **POST** `/api/parent/quests` (store)
   - Now accepts `trait_ids` array parameter
   - Syncs traits with the quest

2. **PUT** `/api/parent/quests/{id}` (update)
   - Now accepts `trait_ids` array parameter
   - Syncs traits with the quest

3. **GET** `/api/parent/quests` (index)
   - Now includes `traits` relationship in response

4. **POST** `/api/parent/approvals/{id}/accept`
   - Now returns `level_ups` data showing:
     - Child level-up information
     - Trait level-up information

5. **POST** `/api/parent/approvals/bulk-accept`
   - Now returns `level_ups` array with all level-up notifications

6. **GET** `/api/parent/children` (index)
   - Now includes `traits` array for each child with progress data

## Models

### New Models:

1. **`App\Models\Trait`** - Represents a character trait
   - Methods: `experienceForLevel(int $level)` - Calculates XP needed for a trait level

2. **`App\Models\ChildTrait`** - Pivot model for child trait progress
   - Methods: `addExperience(int $amount)` - Adds XP and handles level-ups

### Modified Models:

1. **`App\Models\Child`**
   - Added `level` and `experience_points` fillable fields
   - Added `traits()` relationship
   - Added `experienceForLevel(int $level)` static method
   - Added `addExperience(int $amount)` method
   - Added `experienceToNextLevel()` method
   - Added `progressPercentage()` method

2. **`App\Models\Quest`**
   - Added `traits()` relationship

3. **`App\Models\QuestCompletion`**
   - Modified `accept()` method to award XP and handle leveling
   - Added `awardTraitExperience(int $totalXp)` protected method

## Troubleshooting

### Migrations fail with "table already exists"

If you get errors about tables already existing, you can:
1. Check which migrations have run: `php artisan migrate:status`
2. Roll back the last batch: `php artisan migrate:rollback`
3. Run migrations again: `php artisan migrate`

### Traits not showing in quest form

1. Verify traits were seeded: Check `/api/parent/traits`
2. Clear browser cache and refresh the page
3. Check browser console for JavaScript errors

### Child level not updating

1. Verify the migration added the columns: Check `children` table in database
2. Ensure quest has traits tagged
3. Check that quest completion was approved (not just completed)

### Trait XP not splitting correctly

- Verify quest has traits tagged
- Check `quest_traits` table for the quest-trait associations
- XP is only split among traits when quest is **approved**, not when completed by child

## Example Workflow

### Complete Quest Completion Flow:

1. **Parent creates quest**: "Clean your room" for 10 gold
   - Tags it with **Responsibility** and **Perseverance** traits

2. **Child completes quest**:
   - Status changes to "Pending"
   - No XP awarded yet

3. **Parent approves quest**:
   - Child receives: 10 gold
   - Child receives: 100 XP (10 gold × 10) to overall level
   - Responsibility trait receives: 50 XP (100 ÷ 2 traits)
   - Perseverance trait receives: 50 XP (100 ÷ 2 traits)

4. **Level-up notifications** (if thresholds reached):
   ```json
   {
     "level_ups": {
       "child_level_up": {
         "leveled_up": true,
         "levels_gained": 1,
         "old_level": 1,
         "new_level": 2
       },
       "trait_level_ups": [
         {
           "leveled_up": true,
           "levels_gained": 1,
           "new_level": 2,
           "trait_name": "Responsibility"
         }
       ]
     }
   }
   ```

## Future Enhancements (Not Yet Implemented)

Ideas for extending the system:
- Display level-up animations in the UI
- Add achievement badges for reaching certain levels
- Show trait progress in child portal dashboard
- Add trait-based rewards or special abilities
- Leaderboards comparing trait levels between siblings
- Parent dashboard showing trait development over time

## Support

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify database structure matches the schema above
4. Ensure all migrations completed successfully

---

**Setup Complete!** Your Hero Habits leveling system is now ready to use. Start tagging quests with traits and watch children develop their character as they complete tasks!
