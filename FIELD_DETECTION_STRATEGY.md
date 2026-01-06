# Field Detection Strategy - Best Approach

## Your Concern (Valid!)

**Problem with Union Approach**:
- Shows fields from ALL forms of that type
- User might see `phone` field in settings
- But their specific form doesn't have `phone` field
- **Confusing UX**: "Why is phone here if my form doesn't use it?"

## Recommended: HYBRID Approach

### Strategy: **Form-Specific Fields + Type-Level Preferences**

#### 1. **Field Detection: FORM-LEVEL (Show only THIS form's fields)**

```php
// When user opens Form 123
// Show ONLY fields that Form 123 actually has
// NOT union of all Registration forms
```

**Why?**
- ✅ Cleaner settings (only relevant fields)
- ✅ No confusion (all fields shown actually exist)
- ✅ Better UX (user sees what they can use)

#### 2. **Preference Storage: TYPE-LEVEL (Save once, apply everywhere)**

```php
// User selects: name, email, message
// Saves at: bigfm:registration (TYPE level)
// Applies to: ALL Registration forms in BigFM
```

**Why?**
- ✅ One setting covers all forms of that type
- ✅ Efficient (don't need to set per form)
- ✅ Consistent view across same type

#### 3. **New Field Detection: Automatic**

When new submission arrives with new field:
```php
// Form 123 receives: {name, email, message, phone} ← NEW FIELD
// System automatically:
// 1. Detects 'phone' is new
// 2. Adds 'phone' to available fields
// 3. Shows notification: "New field detected: phone"
// 4. User can choose to show/hide it
```

## Implementation Flow

### Scenario: BigFM Registration Forms

#### Step 1: Form 1 First Submission
```
Submission: {name, email, message}
System detects: name, email, message
Settings show: name, email, message (only these 3)
User selects: name, email
Saves at: bigfm:registration (TYPE level)
```

#### Step 2: Form 2 Opens (Same Type)
```
Form 2 has: {name, email, message}
System detects: name, email, message (form-specific)
Settings show: name, email, message
Preference loads: name, email (from TYPE level)
Result: Shows name, email (inherited from Form 1 preference)
```

#### Step 3: Form 2 Receives New Field
```
New submission: {name, email, message, phone} ← NEW
System detects: phone is new field
Actions:
1. Add 'phone' to available fields list
2. Show badge: "New field: phone"
3. User can check/uncheck to show/hide
4. If user checks it → updates TYPE-level preference
5. Now ALL Registration forms can show 'phone'
```

#### Step 4: Form 3 Opens (Has phone field)
```
Form 3 has: {name, email, message, phone}
System detects: name, email, message, phone
Settings show: All 4 fields
Preference loads: name, email, phone (from TYPE level)
Result: Shows name, email, phone (phone was added in Step 3)
```

## Code Implementation

### 1. Field Detection: Form-Specific

```php
// ContactController.php
private function detectAvailableFields(?string $webformId = null, ?string $submissionForm = null, ?string $station = null): array
{
    // PRIORITY: Form level (show only THIS form's fields)
    if ($webformId) {
        $query = ContactSubmission::where('webform_id', $webformId);
        $submissions = $query->limit(100)->get();
        
        // Extract fields from THIS form only
        return $this->extractFieldsFromSubmissions($submissions);
    }
    
    // FALLBACK: Type level (if form doesn't exist yet)
    if ($submissionForm && $station) {
        // ... type level detection
    }
}
```

### 2. New Field Detection: On Submission

```php
// ContactController.php - handleSubmission()
public function handleSubmission(Request $request, string $category): JsonResponse
{
    // ... existing code to save submission
    
    // NEW: Check for new fields
    $webformId = $data['webform_id'] ?? null;
    if ($webformId) {
        $this->detectAndNotifyNewFields($webformId, $data);
    }
    
    return response()->json([...]);
}

private function detectAndNotifyNewFields(string $webformId, array $newData): void
{
    // Get existing fields for this form
    $existingFields = $this->detectAvailableFields($webformId);
    $existingKeys = array_column($existingFields, 'key');
    
    // Find new fields
    $newFields = [];
    foreach (array_keys($newData) as $key) {
        if (!in_array($key, $existingKeys) && 
            !in_array($key, ['webform_id', 'submission_form', 'station', 'category'])) {
            $newFields[] = $key;
        }
    }
    
    // If new fields found, store notification
    if (!empty($newFields)) {
        // Option 1: Store in cache/session for user notification
        // Option 2: Add to a "new_fields" table
        // Option 3: Just let it appear in next field detection (simplest)
    }
}
```

### 3. Frontend: Show New Field Badge

```typescript
// Forms/Detail.vue
const newFields = computed(() => {
    // Compare current fields with saved preference
    // If field exists in data but not in saved preference → it's new
    const savedFields = Array.from(visibleFields.value);
    return allAvailableFields.value.filter(f => 
        !savedFields.includes(f.key) && 
        submissions.data?.some(s => s.data[f.key] !== undefined)
    );
});
```

```vue
<!-- In settings dialog -->
<label v-for="field in fields" :key="field.key">
    <Checkbox :checked="visibleFields.has(field.key)" />
    <span>{{ field.label }}</span>
    <Badge v-if="isNewField(field.key)" variant="secondary" class="ml-2">
        New
    </Badge>
</label>
```

## Comparison: Union vs Form-Specific

### ❌ Union Approach (Current)
```
Form 1: {name, email}
Form 2: {name, email, phone}
Form 3: {name, email, message}

Settings show: name, email, phone, message (all 4)
Problem: Form 1 user sees 'phone' and 'message' but doesn't have them
```

### ✅ Form-Specific Approach (Recommended)
```
Form 1: {name, email}
Settings show: name, email (only these 2)

Form 2: {name, email, phone}
Settings show: name, email, phone (only these 3)

Form 3: {name, email, message}
Settings show: name, email, message (only these 3)

Preference saved at: bigfm:registration (TYPE level)
Result: Each form shows only its own fields, but preference applies to all
```

## Benefits of Hybrid Approach

1. ✅ **Cleaner Settings**: Only show fields that exist in THIS form
2. ✅ **Type-Level Efficiency**: One preference covers all forms
3. ✅ **Automatic Updates**: New fields appear automatically
4. ✅ **No Confusion**: User never sees fields that don't exist
5. ✅ **Flexible**: Can still override at form level if needed

## Summary

**Your idea is BETTER!** 

- Show fields from THIS form (not union)
- Save preference at TYPE level (efficiency)
- Auto-detect new fields when they arrive
- User can add new fields to view anytime

This gives the best of both worlds:
- Clean UX (only relevant fields)
- Efficient preferences (type-level)
- Automatic handling (new fields appear)

