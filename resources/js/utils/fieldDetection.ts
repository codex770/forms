/**
 * Utility functions for field detection and formatting
 */

export interface FieldInfo {
    key: string;
    type: 'string' | 'integer' | 'float' | 'boolean' | 'date' | 'object';
    label: string;
}

/**
 * Detect field type from a value
 */
export function detectFieldType(value: any): FieldInfo['type'] {
    if (value === null || value === undefined) {
        return 'string';
    }
    
    if (typeof value === 'string') {
        // Check if it's a date
        if (value.match(/^\d{4}-\d{2}-\d{2}/) || !isNaN(Date.parse(value))) {
            return 'date';
        }
        return 'string';
    }
    
    if (typeof value === 'number') {
        return Number.isInteger(value) ? 'integer' : 'float';
    }
    
    if (typeof value === 'boolean') {
        return 'boolean';
    }
    
    if (typeof value === 'object') {
        return 'object';
    }
    
    return 'string';
}

/**
 * Format field value for display
 */
export function formatFieldValue(value: any, type?: FieldInfo['type']): string {
    if (value === null || value === undefined) {
        return 'N/A';
    }
    
    if (type === 'date' || (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}/))) {
        try {
            const date = new Date(value);
            return date.toLocaleDateString();
        } catch {
            return String(value);
        }
    }
    
    if (type === 'object') {
        return JSON.stringify(value, null, 2);
    }
    
    if (type === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    
    return String(value);
}

/**
 * Group fields by category for better organization
 * Uses exact matching to prevent duplicates
 */
export function groupFieldsByCategory(fields: FieldInfo[]): Record<string, FieldInfo[]> {
    const groups: Record<string, FieldInfo[]> = {
        'Contact Information': [],
        'Message Content': [],
        'Location': [],
        'Personal Details': [],
        'Other': [],
    };
    
    // Use exact key matching first, then fallback to includes for variations
    const contactFields = ['fname', 'lname', 'first_name', 'last_name', 'name', 'email', 'email_address', 'phone', 'phone_number', 'tel'];
    const messageFields = ['message', 'message_long', 'message_short', 'description', 'content', 'text', 'comment'];
    const locationFields = ['city', 'zip', 'zip_code', 'postal_code', 'plz', 'place', 'location', 'address', 'street'];
    const personalFields = ['age', 'birth_year', 'birthday', 'bday', 'gender', 'sex', 'dob'];
    
    // Track which fields have been categorized to prevent duplicates
    const categorized = new Set<string>();
    
    fields.forEach(field => {
        // Skip if already categorized
        if (categorized.has(field.key)) {
            return;
        }
        
        const key = field.key.toLowerCase();
        let categorized_flag = false;
        
        // Exact match first (most specific)
        if (contactFields.includes(key)) {
            groups['Contact Information'].push(field);
            categorized_flag = true;
        } else if (messageFields.includes(key)) {
            groups['Message Content'].push(field);
            categorized_flag = true;
        } else if (locationFields.includes(key)) {
            groups['Location'].push(field);
            categorized_flag = true;
        } else if (personalFields.includes(key)) {
            groups['Personal Details'].push(field);
            categorized_flag = true;
        } else {
            // Fallback: check if key contains any of the field names (but be more specific)
            // Only match if the key starts with or is exactly the field name
            if (contactFields.some(f => key === f || key.startsWith(f + '_') || key.endsWith('_' + f))) {
                groups['Contact Information'].push(field);
                categorized_flag = true;
            } else if (messageFields.some(f => key === f || key.startsWith(f + '_') || key.endsWith('_' + f))) {
                groups['Message Content'].push(field);
                categorized_flag = true;
            } else if (locationFields.some(f => key === f || key.startsWith(f + '_') || key.endsWith('_' + f))) {
                groups['Location'].push(field);
                categorized_flag = true;
            } else if (personalFields.some(f => key === f || key.startsWith(f + '_') || key.endsWith('_' + f))) {
                groups['Personal Details'].push(field);
                categorized_flag = true;
            } else {
                groups['Other'].push(field);
                categorized_flag = true;
            }
        }
        
        if (categorized_flag) {
            categorized.add(field.key);
        }
    });
    
    // Remove empty groups
    Object.keys(groups).forEach(key => {
        if (groups[key].length === 0) {
            delete groups[key];
        }
    });
    
    return groups;
}

/**
 * Get human-readable label for a field key
 */
export function getFieldLabel(key: string): string {
    const labels: Record<string, string> = {
        'fname': 'First Name',
        'lname': 'Last Name',
        'first_name': 'First Name',
        'last_name': 'Last Name',
        'name': 'Name',
        'email': 'Email',
        'email_address': 'Email Address',
        'phone': 'Phone',
        'message_long': 'Message (Long)',
        'message_short': 'Message (Short)',
        'message': 'Message',
        'description': 'Description',
        'city': 'City',
        'zip': 'ZIP Code',
        'zip_code': 'ZIP Code',
        'postal_code': 'Postal Code',
        'plz': 'PLZ',
        'gender': 'Gender',
        'age': 'Age',
        'birth_year': 'Birth Year',
        'birthday': 'Birthday',
        'bday': 'Birthday',
    };
    
    if (labels[key]) {
        return labels[key];
    }
    
    // Convert snake_case or camelCase to Title Case
    return key
        .replace(/[_-]/g, ' ')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

