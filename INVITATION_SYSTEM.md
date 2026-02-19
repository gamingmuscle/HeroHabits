# Invitation-Only Registration System

This system allows you to restrict parent account registration to invitation-only mode, preventing unauthorized signups.

## Configuration

The invitation system is controlled by a single environment variable in your `.env` file:

```env
# Set to true to enable invitation-only registration
REGISTRATION_INVITATION_ONLY=false
```

### Default Settings

The following defaults are defined in `config/herohabits.php`:

```php
'registration' => [
    'invitation_only' => env('REGISTRATION_INVITATION_ONLY', false),
    'invitation_code_length' => 8,
    'invitation_expiry_days' => 30, // 0 = never expires
],
```

## Enabling Invitation-Only Mode

1. Add to your `.env` file:
   ```env
   REGISTRATION_INVITATION_ONLY=true
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. Run the migration to create the invitations table:
   ```bash
   php artisan migrate
   ```

## Managing Invitations

### Generate Invitation Codes

**Generate a single code (expires in 30 days):**
```bash
php artisan invitation:generate
```

**Generate multiple codes:**
```bash
php artisan invitation:generate --count=5
```

**Generate codes that never expire:**
```bash
php artisan invitation:generate --count=3 --days=0
```

**Generate codes with custom expiration:**
```bash
php artisan invitation:generate --count=10 --days=7
```

**Track who created the invitation:**
```bash
php artisan invitation:generate --email=admin@example.com
```

**Example Output:**
```
Generating 3 invitation code(s)...

+----------+------------------+
| Code     | Expires          |
+----------+------------------+
| A7K9M2XL | 2024-02-15 14:30 |
| P3QW8R5N | 2024-02-15 14:30 |
| Z1YH6T4C | 2024-02-15 14:30 |
+----------+------------------+

✅ Generated 3 invitation code(s) successfully!
Codes will expire in 30 days
```

### List Invitations

**List all invitations:**
```bash
php artisan invitation:list
```

**List only valid invitations:**
```bash
php artisan invitation:list --status=valid
```

**List used invitations:**
```bash
php artisan invitation:list --status=used
```

**List expired invitations:**
```bash
php artisan invitation:list --status=expired
```

**Example Output:**
```
Showing all invitations:

+----------+-----------+------------+----------+------------+
| Code     | Status    | Expires    | Used By  | Created    |
+----------+-----------+------------+----------+------------+
| A7K9M2XL | Valid     | 2024-02-15 | -        | 2024-01-16 |
| P3QW8R5N | ✓ Used    | 2024-02-15 | User #1  | 2024-01-16 |
| Z1YH6T4C | ✗ Expired | 2024-01-10 | -        | 2024-01-01 |
+----------+-----------+------------+----------+------------+

Total: 3 invitation(s)
```

## User Experience

### When Invitation Mode is OFF (Default)

- Registration form shows: Display Name, Username, Password fields
- Anyone can register without restrictions

### When Invitation Mode is ON

- Registration form shows: Display Name, **Invitation Code**, Username, Password fields
- Users must enter a valid invitation code to register
- Codes are case-insensitive (automatically converted to uppercase)
- Each code can only be used once
- Expired codes are rejected

### Error Messages

Users will see helpful error messages:
- **"Invalid invitation code."** - Code doesn't exist
- **"This invitation code has already been used."** - Code was already redeemed
- **"This invitation code has expired."** - Code past expiration date

## Database Schema

The `invitations` table stores:

| Field              | Type      | Description                              |
|--------------------|-----------|------------------------------------------|
| `id`               | bigint    | Primary key                              |
| `code`             | string    | Unique invitation code (e.g., "A7K9M2XL")|
| `expires_at`       | timestamp | Expiration date/time (null = never)      |
| `used_by`          | bigint    | User ID who used this code (nullable)    |
| `used_at`          | timestamp | When the code was used (nullable)        |
| `created_by_email` | string    | Who created this invitation (optional)   |
| `created_at`       | timestamp | When code was generated                  |
| `updated_at`       | timestamp | Last update                              |

## Programmatic Usage

### Generate Invitations in Code

```php
use App\Models\Invitation;

// Generate with default expiration (30 days)
$invitation = Invitation::generate();

// Generate that never expires
$invitation = Invitation::generate(0);

// Generate with custom expiration
$invitation = Invitation::generate(7); // expires in 7 days

echo "Invitation Code: " . $invitation->code;
```

### Check if Invitation is Valid

```php
$invitation = Invitation::where('code', 'A7K9M2XL')->first();

if ($invitation && $invitation->isValid()) {
    // Code is valid and unused
}
```

### Query Invitations

```php
// Get all valid invitations
$valid = Invitation::valid()->get();

// Get all used invitations
$used = Invitation::used()->get();

// Get all expired invitations
$expired = Invitation::expired()->get();
```

## Security Features

1. **One-time use**: Each code can only be used once
2. **Expiration**: Codes automatically expire after configured days
3. **Case-insensitive**: Codes work regardless of case (stored uppercase)
4. **Unique codes**: Collision detection ensures unique 8-character codes
5. **Validation**: Server-side validation prevents bypass attempts

## Switching Modes

You can toggle invitation mode on/off at any time:

**To enable:**
1. Set `REGISTRATION_INVITATION_ONLY=true` in `.env`
2. Run `php artisan config:clear`
3. Generate invitation codes
4. Share codes with approved users

**To disable:**
1. Set `REGISTRATION_INVITATION_ONLY=false` in `.env`
2. Run `php artisan config:clear`
3. Open registration is now enabled

**Note:** Existing invitations remain in the database and can be re-enabled later.

## Best Practices

1. **Generate codes in batches** for specific groups of users
2. **Use expiration dates** to limit the validity window
3. **Track who created codes** using the `--email` flag for accountability
4. **Regularly clean up** expired and used codes (or keep for audit trail)
5. **Share codes securely** via direct communication, not public channels

## Troubleshooting

### Registration form doesn't show invitation field
- Check `.env` has `REGISTRATION_INVITATION_ONLY=true`
- Run `php artisan config:clear`
- Hard refresh the browser (Ctrl+F5)

### Invitation code not accepted
- Verify code exists: `php artisan invitation:list`
- Check if already used: `php artisan invitation:list --status=used`
- Check expiration: `php artisan invitation:list`

### Want to disable for testing
```env
REGISTRATION_INVITATION_ONLY=false
```
Then: `php artisan config:clear`

---

**Created:** January 2024
**Version:** 1.0
