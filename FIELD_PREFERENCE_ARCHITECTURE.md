# Field Preference Architecture - Senior Developer Recommendation

## Problem Statement

Different form types have different fields:
- **BigFM → Registration**: `name`, `email`, `message`
- **BigFM → Survey**: `dob`, `city`, `country`
- **RPR1 → Registration**: `name`, `email`, `message` (same as BigFM Registration)
- **RPR1 → Contest**: `name`, `email`, `age`, `city` (different fields)

## Recommended Architecture Strategy

### 1. **Field Detection Strategy: TYPE-LEVEL PRIMARY**

**Current Implementation**: Detects at form/type/station levels
**Recommended Enhancement**: Make TYPE level the primary source

#### Why TYPE Level?
- Forms of the same TYPE typically share the same field structure
- More efficient than scanning each form individually
- Better user experience (consistent field list)
- Easier to maintain preferences

#### Implementation:
```php
// Priority order for field detection:
1. TYPE level (station + submission_form) - PRIMARY
   - Scans ALL forms of that type
   - Creates UNION of all fields found
   - Example: All "Registration" forms in BigFM
   
2. FORM level (webform_id) - SECONDARY
   - Only if form has unique fields not in type
   - Used for edge cases
   
3. STATION level - FALLBACK
   - Only if type doesn't exist yet
```

### 2. **Preference Storage Strategy: TYPE-LEVEL DEFAULT**

**Recommended Approach**: Store preferences at TYPE level by default

#### Preference Hierarchy:
```
Global (null)                    → Applies everywhere
  └── Station (bigfm)            → Applies to all types in BigFM
      └── Type (bigfm:registration) → DEFAULT LEVEL (most common)
          └── Form (bigfm:registration:form_123) → Override for special cases
```

#### Why TYPE Level for Preferences?
- **80/20 Rule**: 80% of forms of same type have same fields
- **Consistency**: Users expect same view for same type
- **Maintenance**: One preference setting covers all forms of that type
- **Flexibility**: Can still override at form level if needed

### 3. **Field Union Strategy**

When detecting fields at TYPE level:
- Scan ALL forms of that type (not just one)
- Create UNION of all unique fields found
- This handles cases where:
  - Form 1 has: `name`, `email`, `message`
  - Form 2 has: `name`, `email`, `message`, `phone` (added field)
  - Settings show: `name`, `email`, `message`, `phone` (all fields)

### 4. **Smart Defaults**

#### Default Visible Fields by Type:
```php
// Registration type defaults
['fname', 'lname', 'email', 'message_long']

// Survey type defaults  
['dob', 'city', 'country', 'age']

// Contest type defaults
['name', 'email', 'age', 'city']
```

#### Implementation:
- Store type-specific defaults in config or database
- Apply when no user preference exists
- User can override and save

### 5. **Preference Inheritance Flow**

```
User opens BigFM → Registration → Form 123

1. Check Form-level preference: bigfm:registration:form_123
   └── If exists → Use it
   
2. Check Type-level preference: bigfm:registration
   └── If exists → Use it (DEFAULT)
   
3. Check Station-level preference: bigfm
   └── If exists → Use it
   
4. Check Global preference: null
   └── If exists → Use it
   
5. Use Type-specific defaults
   └── Registration → ['fname', 'lname', 'email', 'message_long']
```

### 6. **UI/UX Recommendations**

#### Settings Dialog Should Show:
1. **Field Source Indicator**:
   ```
   "Fields detected from: Registration type (5 forms)"
   ```

2. **Preference Level Indicator**:
   ```
   "Saving at: Type level (applies to all Registration forms)"
   [Change to Form level] [Change to Station level]
   ```

3. **Field Frequency**:
   ```
   First Name (fname) - 100% of forms
   Phone (phone) - 20% of forms ⚠️
   ```

### 7. **Database Schema Enhancement (Optional)**

Consider adding a `form_type_config` table:
```sql
CREATE TABLE form_type_configs (
    id BIGINT PRIMARY KEY,
    station VARCHAR(255),
    submission_form VARCHAR(255),
    default_fields JSON, -- ['fname', 'lname', 'email']
    field_schema JSON,   -- Expected fields with validation
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(station, submission_form)
);
```

**Benefits**:
- Pre-define expected fields for each type
- Set type-specific defaults
- Validate incoming submissions
- Better field detection

### 8. **Implementation Priority**

#### Phase 1: Current (Already Implemented) ✅
- Field detection at form/type/station levels
- Preference storage with hierarchy
- Dynamic field display

#### Phase 2: Enhancements (Recommended)
1. **Improve field detection**:
   - Prioritize TYPE level detection
   - Use UNION strategy for all forms of type
   - Cache field detection results

2. **Smart defaults**:
   - Type-specific default fields
   - Auto-suggest based on field frequency

3. **UI improvements**:
   - Show preference level in settings
   - Allow changing preference scope
   - Show field frequency/coverage

#### Phase 3: Advanced (Future)
1. **Field schema validation**
2. **Field mapping/aliases** (handle `name` vs `fname+lname`)
3. **Field templates** (pre-configured field sets)

## Recommended Code Changes

### 1. Enhance Field Detection (ContactController.php)

```php
private function detectAvailableFields(?string $webformId = null, ?string $submissionForm = null, ?string $station = null): array
{
    // PRIORITY: Type level first (most common case)
    if ($submissionForm && $station && !$webformId) {
        // TYPE LEVEL: Scan ALL forms of this type
        $query = ContactSubmission::where('submission_form', $submissionForm)
                                  ->where('station', $station);
        
        // Get more samples (up to 500) for better field detection
        $submissions = $query->limit(500)->get();
        
        // Create UNION of all fields
        return $this->extractFieldsFromSubmissions($submissions);
    }
    
    // FALLBACK: Form level
    if ($webformId) {
        // ... existing code
    }
    
    // FALLBACK: Station level
    // ... existing code
}
```

### 2. Default Preference Level (Frontend)

```typescript
// In useFieldPreferences.ts
const getDefaultPreferenceLevel = (category: string) => {
    const parts = category.split(':');
    
    // If category has type (station:type:form), default to TYPE level
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`; // Type level
    }
    
    return category; // Form or station level
};
```

### 3. Type-Specific Defaults (Config or Database)

```php
// config/form_types.php
return [
    'registration' => [
        'default_fields' => ['fname', 'lname', 'email', 'message_long'],
    ],
    'survey' => [
        'default_fields' => ['dob', 'city', 'country', 'age'],
    ],
    'contest' => [
        'default_fields' => ['name', 'email', 'age', 'city'],
    ],
];
```

## Summary: Smartest Approach

### ✅ **DO THIS**:
1. **Detect fields at TYPE level** (union of all forms of that type)
2. **Store preferences at TYPE level by default** (covers 80% of cases)
3. **Allow form-level override** (for 20% special cases)
4. **Use type-specific defaults** (better UX)
5. **Show field frequency** (help users understand coverage)

### ❌ **DON'T DO THIS**:
1. Don't detect fields per-form individually (inefficient)
2. Don't store preferences only at form level (too many to manage)
3. Don't ignore type-level patterns (misses optimization opportunity)

## Example Flow

### Scenario: BigFM Registration Forms

1. **Form 1 submits**: `{name, email, message}`
2. **Form 2 submits**: `{name, email, message, phone}`
3. **System detects at TYPE level**:
   - Scans all "Registration" forms in BigFM
   - Finds: `name`, `email`, `message`, `phone`
   - Shows all 4 fields in settings

4. **User sets preference**:
   - Selects: `name`, `email`, `message` (hides phone)
   - Saves at TYPE level: `bigfm:registration`
   - Applies to ALL Registration forms in BigFM

5. **New Form 3 arrives**:
   - Has: `{name, email, message, phone, city}`
   - System detects: `city` is new
   - Adds `city` to available fields
   - User's preference still applies (name, email, message visible)
   - User can add `city` if needed

### Result:
- ✅ One preference setting covers all forms of that type
- ✅ New fields automatically appear
- ✅ Consistent view across same type
- ✅ Can override per-form if needed

