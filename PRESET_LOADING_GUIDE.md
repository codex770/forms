# 🔄 How Preset Loading Works

## What Happens When You Save a Preset:

### Step 1: You Customize Your View
```
✅ Check/uncheck columns (e.g., hide Email, show Name)
✅ Apply filters (e.g., status = "unread", city = "Berlin")
✅ Sort by column (e.g., sort by Date, descending)
```

### Step 2: Click "Save Current View As..."
```javascript
// Frontend saves to database:
{
  category: "radio_regenbogen_test_fuer_akan",  // The form ID
  preference_name: "Quick Review",               // Your custom name
  visible_columns: ["checkbox", "name", "date"], // Which columns to show
  sort_config: {
    column: "created_at",                        // Sort by this column
    direction: "desc"                            // Ascending or descending
  },
  saved_filters: {
    status: "unread",                            // Only unread
    city: "Berlin",                              // Only Berlin
    // ... all other active filters
  }
}
```

### Step 3: Preset Saved! ✅
Database stores everything in `user_table_preferences` table.

---

## What Happens When You Load a Preset:

### Step 1: Click on Saved Preset Name
```
You click: "Quick Review" in the View Settings dropdown
```

### Step 2: Frontend Loads Preset Data
```javascript
// loadPreset() function runs:
1. ✅ Update visible columns
   visibleColumns = ["checkbox", "name", "date"]
   
2. ✅ Update sorting
   sortColumn = "created_at"
   sortDirection = "desc"
   
3. ✅ Update all filters
   status = "unread"
   city = "Berlin"
   // ... etc
   
4. ✅ Call applyFilters()
   This navigates to: /forms/{webformId}?status=unread&city=Berlin&sort_column=created_at&sort_direction=desc
```

### Step 3: Backend Processes Request
```php
// ContactController@showFormDetail
1. ✅ Receives all filter parameters
2. ✅ Receives sort_column and sort_direction
3. ✅ Queries database with filters and sorting
4. ✅ Returns filtered/sorted data to frontend
```

### Step 4: Page Reloads with New Data ✅
```
Table shows:
- ✅ Only the columns you selected
- ✅ Only the filtered data (unread, Berlin)
- ✅ Sorted by your preference (date descending)
```

---

## What Was Fixed:

### ❌ Before:
```javascript
const applyFilters = () => {
    const params = {};
    // ... only filters, NO SORTING!
    if (searchQuery.value) params.search = searchQuery.value;
    // Missing: sort_column and sort_direction
}
```

### ✅ After:
```javascript
const applyFilters = () => {
    const params = {};
    
    // Filters
    if (searchQuery.value) params.search = searchQuery.value;
    // ... all filters ...
    
    // ✅ NOW INCLUDES SORTING!
    if (sortColumn.value) params.sort_column = sortColumn.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;
}
```

---

## Testing Your Preset:

### 1. Create a Test Preset:
```
1. Go to form detail page
2. Click "View Settings"
3. Hide some columns (e.g., uncheck "Email")
4. Apply a filter (e.g., Status = "Unread")
5. Click "Save Current View As..."
6. Name it: "Test Preset"
7. Click Save
```

### 2. Reload the Page:
```
1. Refresh your browser (F5)
2. All columns should be visible again
3. Filters should be cleared
```

### 3. Load Your Preset:
```
1. Click "View Settings"
2. Click "Test Preset" in the Saved Views section
3. Watch what happens:
   ✅ Columns change (Email disappears)
   ✅ Filters apply (only unread shown)
   ✅ URL updates with parameters
   ✅ Table reloads with filtered data
```

---

## What Gets Saved vs What Doesn't:

### ✅ Saved in Preset:
- Column visibility (which columns to show/hide)
- Sorting (column + direction)
- All active filters (status, date, age, city, etc.)

### ❌ NOT Saved:
- Selected rows (checkboxes)
- Scroll position
- Expanded/collapsed sections
- Current page number

---

## Troubleshooting:

### Issue: "Columns don't change when I load preset"
**Check:** Are the columns actually different from default?
**Solution:** Make sure you hide/show different columns before saving

### Issue: "Filters don't apply when I load preset"
**Check:** Browser console for errors (F12)
**Solution:** Check if filters have valid values

### Issue: "Sorting doesn't work"
**Check:** Is sort_column and sort_direction in the URL after loading?
**Solution:** Should see `?sort_column=created_at&sort_direction=desc`

### Issue: "Page doesn't reload"
**Check:** Browser console for JavaScript errors
**Solution:** Make sure applyFilters() is being called

---

## Flow Diagram:

```
┌─────────────────────────────────────────────┐
│ 1. User Clicks "Test Preset"                │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ 2. loadPreset() Updates Variables:          │
│    - visibleColumns                         │
│    - sortColumn, sortDirection              │
│    - All filter values                      │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ 3. applyFilters() Builds URL:               │
│    /forms/xyz?status=unread&sort_column=... │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ 4. router.get() Navigates to New URL        │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ 5. Backend Receives Request                 │
│    - Applies filters to query               │
│    - Applies sorting                        │
│    - Returns filtered data                  │
└─────────────────┬───────────────────────────┘
                  ↓
┌─────────────────────────────────────────────┐
│ 6. Frontend Renders:                        │
│    - Table with filtered data               │
│    - Only visible columns                   │
│    - Sorted correctly                       │
└─────────────────────────────────────────────┘
                  ↓
              ✅ DONE!
```

---

## Console Logs to Check:

Open browser console (F12) and look for:
```
✅ "Preset loaded successfully: Test Preset"
✅ Network request to: /forms/xyz?status=unread&...
✅ No JavaScript errors
```

---

Now try loading your saved preset! It should work correctly. 🎉
