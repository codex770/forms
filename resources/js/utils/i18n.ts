import { autoTranslate } from '@/utils/autoTranslate';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Locale = 'de' | 'en';

const translations: Record<Locale, Record<string, string>> = {
    de: {
        // Navigation
        'nav.dashboard': 'Dashboard',
        'nav.users': 'Benutzerverwaltung',
        'nav.contact': 'Kontaktnachrichten',

        // Form Center / Detail
        'forms.back': 'Zurück zum Form Center',
        'forms.total_submissions': 'Einreichungen gesamt',
        'forms.station': 'Sender',
        'forms.form_id': 'Formular-ID',
        'forms.export': 'Exportieren',
        'forms.print': 'Drucken',
        'forms.duplicates_shown': 'Duplikate: Sichtbar',
        'forms.duplicates_hidden': 'Duplikate: Ausgeblendet',

        // Message details
        'message.from': 'Nachricht von',
        'message.data': 'Nachricht',
        'contact.info': 'Kontaktdaten',
        'read.status': 'Lesestatus',
        'reads.read_by': 'Gelesen von {count} {userLabel}:',
        'reads.user': 'Benutzer',
        'reads.users': 'Benutzern',
        'quick.actions': 'Schnellaktionen',

        // Column settings
        'columns.title': 'Spalten anzeigen/ausblenden',
        'columns.select_all': 'Alle auswählen',
        'columns.clear_all': 'Alle abwählen',
        'columns.reorder': 'Spaltenreihenfolge ändern (Drag & Drop)',
        'columns.preference_level': 'Präferenzebene (gilt für {form})',
        'columns.form_config': 'Formular-Konfiguration (für alle Benutzer)',

        // Filters
        'filter.title': 'Filter',
        'filter.search': 'Suche',
        'filter.search_placeholder': 'Einreichungen durchsuchen…',
        'filter.status': 'Status',
        'filter.status_all': 'Alle Nachrichten',
        'filter.status_unread': 'Ungelesen',
        'filter.status_read': 'Gelesen',
        'filter.status_starred': 'Markiert',
        'filter.date_range': 'Datumsbereich',
        'filter.advanced': 'Erweitert',
        'filter.hide_advanced': 'Erweitert ausblenden',
        'filter.clear_all': 'Alle zurücksetzen',
        'filter.apply': 'Filter anwenden',
        'filter.age_range': 'Altersbereich',
        'filter.birth_year': 'Geburtsjahr',
        'filter.plz': 'PLZ',
        'filter.plz_from': 'PLZ von',
        'filter.plz_to': 'PLZ bis',
        'filter.city': 'Stadt',
        'filter.gender': 'Geschlecht',
        'filter.gender_all': 'Alle',
        'filter.gender_m': 'Männlich',
        'filter.gender_f': 'Weiblich',
        'filter.gender_d': 'Divers',
        'filter.radius': 'Umkreissuche (km)',
        'filter.radius_plz': 'Mittelpunkt PLZ',

        // Table
        'table.id': 'ID',
        'table.status': 'Status',
        'table.date': 'Datum',
        'table.actions': 'Aktionen',
        'table.no_results': 'Keine Einreichungen gefunden',
        'table.no_results_desc':
            'Keine Einreichungen entsprechen den aktuellen Filtern.',

        // Bulk actions
        'bulk.selected': 'ausgewählt',
        'bulk.mark_read': 'Als gelesen markieren',
        'bulk.export': 'Exportieren',
        'bulk.delete': 'Löschen',
        'bulk.clear': 'Auswahl aufheben',

        // Actions
        'action.edit': 'Bearbeiten',
        'action.delete': 'Löschen',
        'action.delete.message': 'Nachricht löschen',
        'action.print': 'Drucken',
        'action.forward_email': 'Per E-Mail weiterleiten',
        'action.reply_email': 'Per E-Mail antworten',
        'action.no_email': 'Keine E-Mail verfügbar',
        'action.mark': 'Markieren',
        'action.unmark': 'Markierung entfernen',
        'action.read': 'Als gelesen markieren',
        'action.unread': 'Als ungelesen markieren',
        'action.save': 'Speichern',
        'action.cancel': 'Abbrechen',
        'action.view': 'Anzeigen',

        // Retention
        'retention.title': 'Automatische Datenlöschung (Aufbewahrung)',
        'retention.description':
            'Legen Sie fest, wie lange Einreichungen für dieses Formular aufbewahrt werden. Nach der angegebenen Anzahl von Tagen löscht der nächtliche Job (läuft täglich um 02:00 Uhr) ältere Datensätze dauerhaft.',
        'retention.days_label': 'Aufbewahrungszeitraum (Tage)',
        'retention.days_placeholder': 'z. B. 365 – leer = dauerhaft',
        'retention.save': 'Regel speichern',
        'retention.saving': 'Wird gespeichert…',
        'retention.saved': '✓ Gespeichert',
        'retention.forever': 'Aktuell: dauerhaft aufbewahren',
        'retention.will_delete':
            'Löscht Einreichungen, die älter als {days} Tag(e) sind',

        // Language
        'lang.toggle': 'Sprache',
        'lang.de': 'Deutsch',
        'lang.en': 'English',

        // Settings
        'settings.profile': 'Profil',
        'settings.password': 'Passwort',
        'settings.appearance': 'Erscheinungsbild',

        // General
        'common.loading': 'Wird geladen…',
        'common.error': 'Fehler',
        'common.success': 'Erfolgreich',
        'common.confirm_delete':
            'Sind Sie sicher, dass Sie diese Einreichung dauerhaft löschen möchten?',
        'common.confirm_bulk_delete':
            'Sind Sie sicher, dass Sie {count} Einreichung(en) löschen möchten?',
        'common.from': 'Von',
        'common.to': 'Bis',
        'common.min': 'Min',
        'common.max': 'Max',
        'common.na': 'k. A.',
        'common.previous': 'Zurück',
        'common.next': 'Weiter',
        'common.platform': 'Plattform',
        'common.unknown': 'Unbekannt',
        'common.no_email': 'Keine E-Mail angegeben',
        'common.no_message': 'Kein Nachrichteninhalt',
        'common.submitted': 'Eingereicht',
        'common.email': 'E-Mail',

        // Dashboard
        'dashboard.title': 'Form Center',
        'dashboard.subtitle':
            'Übersicht aller verfügbaren Formulare und Einreichungen',
        'dashboard.no_forms': 'Noch keine Formulare',
        'dashboard.no_forms_desc':
            'Formulare erscheinen hier, sobald Einreichungen über Webhooks eingehen.',
        'dashboard.no_forms_station': 'Noch keine Formulare',
        'dashboard.no_forms_station_desc':
            'Formulare erscheinen, sobald Webhooks eingehen.',
        'dashboard.forms_count': '{count} Formulare',
        'dashboard.entries_count': '{count} Einträge',
        'dashboard.form_name': 'Formularname',
        'dashboard.entries': 'Einträge',
        'dashboard.total_stations': 'Sender gesamt',
        'dashboard.total_stations_desc': 'Aktive Radiosender',
        'dashboard.radio_stations': 'Radiosender',
        'dashboard.total_forms': 'Formulare gesamt',
        'dashboard.total_forms_desc': 'Eindeutige Webformulare',
        'dashboard.active_forms': 'Aktive Formulare',
        'dashboard.total_submissions': 'Einreichungen gesamt',
        'dashboard.total_submissions_desc': 'Alle Einträge zusammen',
        'dashboard.total_entries': 'Einträge gesamt',

        // Station overview
        'station.back': 'Zurück zum Dashboard',
        'station.forms_overview': 'Formularübersicht',
        'station.search_filter': 'Suche & Filter',
        'station.search_placeholder': 'Formulare durchsuchen…',
        'station.per_page': 'Pro Seite',
        'station.showing': 'Zeige {from}–{to} von {total}',
        'station.form_id': 'Formular-ID',
        'station.submission_form': 'Einreichungsformular',
        'station.entry_count': 'Anzahl Einträge',
        'station.created': 'Erstellt',
        'station.updated': 'Aktualisiert',
        'station.no_forms': 'Keine Formulare gefunden',
        'station.no_forms_desc': 'Keine Formulare entsprechen Ihrer Suche.',

        // Export
        'export.title': 'Einreichungen exportieren',
        'export.csv': 'CSV exportieren',
        'export.excel': 'Excel exportieren',
        'export.scope_current': 'Aktuelle Ansicht',
        'export.scope_selected': 'Ausgewählte ({count})',

        // Duplicate tooltip
        'duplicate.tooltip':
            '{count} doppelte Einreichungen mit gleicher E-Mail/Telefon/Name/PLZ/Geburtsjahr',

        // Radius warning
        'filter.radius_warning':
            'Mittelpunkt-PLZ konnte nicht aufgelöst werden. Bitte eine gültige PLZ eingeben.',

        // Auth
        'auth.login_title': 'Bei Ihrem Konto anmelden',
        'auth.login_desc':
            'Geben Sie E-Mail und Passwort ein, um sich anzumelden',
        'auth.login': 'Anmelden',
        'auth.email': 'E-Mail-Adresse',
        'auth.password': 'Passwort',
        'auth.remember': 'Angemeldet bleiben',
        'auth.forgot_password': 'Passwort vergessen?',
        'auth.settings': 'Einstellungen',
        'auth.settings_desc': 'Profil und Kontoeinstellungen verwalten',
        'auth.logout': 'Abmelden',
        'auth.two_factor': 'Zwei-Faktor-Authentifizierung',
        'auth.appearance': 'Erscheinungsbild',

        // Contact detail
        'contact.back_form': 'Zurück zum Formular',
        'contact.back_messages': 'Zurück zu Nachrichten',
        'contact.mark_read': 'Als gelesen markieren',
        'contact.mark_unread': 'Als ungelesen markieren',
        'contact.read': 'Gelesen',
        'contact.unread': 'Ungelesen',
    },
    en: {
        // Navigation
        'nav.dashboard': 'Dashboard',
        'nav.users': 'User Management',
        'nav.contact': 'Contact Messages',

        // Form Center / Detail
        'forms.back': 'Back to Form Center',
        'forms.total_submissions': 'total submissions',
        'forms.station': 'Station',
        'forms.form_id': 'Form ID',
        'forms.export': 'Export',
        'forms.print': 'Print',
        'forms.duplicates_shown': 'Duplicates: Shown',
        'forms.duplicates_hidden': 'Duplicates: Hidden',

        // Message details
        'message.from': 'Message from',
        'message.data': 'Message',
        'contact.info': 'Contact Information',
        'read.status': 'Read Status',
        'reads.read_by': 'Read by {count} {userLabel}:',
        'reads.user': 'user',
        'reads.users': 'users',
        'quick.actions': 'Quick Actions',

        // Column settings
        'columns.title': 'Show/Hide Columns',
        'columns.select_all': 'Select All',
        'columns.clear_all': 'Clear All',
        'columns.reorder': 'Reorder selected columns (drag & drop)',
        'columns.preference_level': 'Preference Level (applies to {form})',
        'columns.form_config': 'Form configuration (for all users)',

        // Filters
        'filter.title': 'Filters',
        'filter.search': 'Search',
        'filter.search_placeholder': 'Search submissions...',
        'filter.status': 'Status',
        'filter.status_all': 'All Messages',
        'filter.status_unread': 'Unread',
        'filter.status_read': 'Read',
        'filter.status_starred': 'Starred',
        'filter.date_range': 'Date Range',
        'filter.advanced': 'Advanced',
        'filter.hide_advanced': 'Hide Advanced',
        'filter.clear_all': 'Clear All',
        'filter.apply': 'Apply Filters',
        'filter.age_range': 'Age Range',
        'filter.birth_year': 'Birth Year',
        'filter.plz': 'Postal Code',
        'filter.plz_from': 'PLZ from',
        'filter.plz_to': 'PLZ to',
        'filter.city': 'City',
        'filter.gender': 'Gender',
        'filter.gender_all': 'All',
        'filter.gender_m': 'Male',
        'filter.gender_f': 'Female',
        'filter.gender_d': 'Diverse',
        'filter.radius': 'Radius Search (km)',
        'filter.radius_plz': 'Center PLZ',

        // Table
        'table.id': 'ID',
        'table.status': 'Status',
        'table.date': 'Date',
        'table.actions': 'Actions',
        'table.no_results': 'No submissions found',
        'table.no_results_desc': 'No submissions match your current filters.',

        // Bulk actions
        'bulk.selected': 'selected',
        'bulk.mark_read': 'Mark as Read',
        'bulk.export': 'Export',
        'bulk.delete': 'Delete',
        'bulk.clear': 'Clear Selection',

        // Actions
        'action.edit': 'Edit',
        'action.delete': 'Delete',
        'action.delete.message': 'Delete Message',
        'action.print': 'Print',
        'action.forward_email': 'Forward via Email',
        'action.reply_email': 'Reply via Email',
        'action.no_email': 'No Email Available',
        'action.mark': 'Mark',
        'action.unmark': 'Unmark',
        'action.read': 'Mark as Read',
        'action.unread': 'Mark as Unread',
        'action.save': 'Save',
        'action.cancel': 'Cancel',
        'action.view': 'View',

        // Retention
        'retention.title': 'Automated Data Deletion (Retention)',
        'retention.description':
            'Configure how long submissions for this form are kept. After the specified number of days the purge job (runs nightly at 02:00) will permanently delete older records.',
        'retention.days_label': 'Retention period (days)',
        'retention.days_placeholder': 'e.g. 365 – blank = keep forever',
        'retention.save': 'Save Rule',
        'retention.saving': 'Saving…',
        'retention.saved': '✓ Saved',
        'retention.forever': 'Currently: keep forever',
        'retention.will_delete':
            'Will delete submissions older than {days} day(s)',

        // Language
        'lang.toggle': 'Language',
        'lang.de': 'Deutsch',
        'lang.en': 'English',

        // Settings
        'settings.profile': 'Profile',
        'settings.password': 'Password',
        'settings.appearance': 'Appearance',

        // General
        'common.loading': 'Loading…',
        'common.error': 'Error',
        'common.success': 'Success',
        'common.confirm_delete':
            'Are you sure you want to permanently delete this submission?',
        'common.confirm_bulk_delete':
            'Are you sure you want to delete {count} submission(s)?',
        'common.from': 'From',
        'common.to': 'To',
        'common.min': 'Min',
        'common.max': 'Max',
        'common.na': 'N/A',
        'common.previous': 'Previous',
        'common.next': 'Next',
        'common.platform': 'Platform',
        'common.unknown': 'Unknown',
        'common.no_email': 'No email provided',
        'common.no_message': 'No message content',
        'common.submitted': 'Submitted',
        'common.email': 'Email',

        // Dashboard
        'dashboard.title': 'Form Center',
        'dashboard.subtitle':
            'Overview of all available forms and their submissions',
        'dashboard.no_forms': 'No Forms Yet',
        'dashboard.no_forms_desc':
            'Forms will appear here once submissions are received from webhooks.',
        'dashboard.no_forms_station': 'No forms yet',
        'dashboard.no_forms_station_desc':
            'Forms will appear when webhooks are received',
        'dashboard.forms_count': '{count} forms',
        'dashboard.entries_count': '{count} entries',
        'dashboard.form_name': 'Form Name',
        'dashboard.entries': 'Entries',
        'dashboard.total_stations': 'Total Stations',
        'dashboard.total_stations_desc': 'Active radio stations',
        'dashboard.radio_stations': 'Radio stations',
        'dashboard.total_forms': 'Total Forms',
        'dashboard.total_forms_desc': 'Unique webforms',
        'dashboard.active_forms': 'Active forms',
        'dashboard.total_submissions': 'Total Submissions',
        'dashboard.total_submissions_desc': 'All entries combined',
        'dashboard.total_entries': 'Total entries',

        // Station overview
        'station.back': 'Back to Dashboard',
        'station.forms_overview': 'Forms Overview',
        'station.search_filter': 'Search & Filter',
        'station.search_placeholder': 'Search forms...',
        'station.per_page': 'Per page',
        'station.showing': 'Showing {from}–{to} of {total}',
        'station.form_id': 'Form ID',
        'station.submission_form': 'Submission Form',
        'station.entry_count': 'Entry Count',
        'station.created': 'Created',
        'station.updated': 'Updated',
        'station.no_forms': 'No forms found',
        'station.no_forms_desc': 'No forms match your search.',

        // Export
        'export.title': 'Export submissions',
        'export.csv': 'Export CSV',
        'export.excel': 'Export Excel',
        'export.scope_current': 'Current view',
        'export.scope_selected': 'Selected ({count})',

        // Duplicate tooltip
        'duplicate.tooltip':
            '{count} duplicate submissions with the same email/phone/name/PLZ/birth year',

        // Radius warning
        'filter.radius_warning':
            'Center PLZ could not be resolved. Please enter a valid postal code.',

        // Auth
        'auth.login_title': 'Log in to your account',
        'auth.login_desc': 'Enter your email and password below to log in',
        'auth.login': 'Log in',
        'auth.email': 'Email address',
        'auth.password': 'Password',
        'auth.remember': 'Remember me',
        'auth.forgot_password': 'Forgot password?',
        'auth.settings': 'Settings',
        'auth.settings_desc': 'Manage your profile and account settings',
        'auth.logout': 'Log out',
        'auth.two_factor': 'Two-Factor Auth',
        'auth.appearance': 'Appearance',

        // Contact detail
        'contact.back_form': 'Back to Form',
        'contact.back_messages': 'Back to Messages',
        'contact.mark_read': 'Mark as Read',
        'contact.mark_unread': 'Mark as Unread',
        'contact.read': 'Read',
        'contact.unread': 'Unread',
    },
};

function humanizeKey(key: string): string {
    return key
        .replace(/[-_]/g, ' ')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, (c) => c.toUpperCase())
        .trim();
}

// Reactive cache — lives outside useI18n so it persists across calls
const autoTranslateCache = ref<Record<string, string>>({});

export const fieldOverrides: Record<Locale, Record<string, string>> = {
    de: {
        plz: 'Postleitzahl',
        zip: 'Postleitzahl',
        zip_code: 'Postleitzahl',
        postal_code: 'Postleitzahl',
        // city: 'Stadt',
        bday: 'Geburtsdatum',
        birthday: 'Geburtsdatum',
        birth_year: 'Geburtsjahr',
        fname: 'Vorname',
        lname: 'Nachname',
        first_name: 'Vorname',
        last_name: 'Nachname',
        email_address: 'E-Mail',
        email: 'E-Mail',
        gender: 'Geschlecht',
        sex: 'Geschlecht',
        datenschutz: 'Datenschutz',
        message_long: 'Nachricht (lang)',
        message_short: 'Nachricht (kurz)',
        newsletter_opt_in: 'Newsletter-Anmeldung',
        street: 'Straße & Hausnummer',
        address: 'Straße & Hausnummer',
        datenschutz_teilnahmebedingungen: 'Datenschutz Teilnahmebedingungen',
    },
    en: {
        plz: 'Postal Code',
        zip: 'Postal Code',
        zip_code: 'Postal Code',
        postal_code: 'Postal Code',
        // city: 'City',
        bday: 'Birthday',
        birthday: 'Birthday',
        birth_year: 'Birth Year',
        fname: 'First Name',
        lname: 'Last Name',
        first_name: 'First Name',
        last_name: 'Last Name',
        email_address: 'Email Address',
        email: 'Email',
        gender: 'Gender',
        sex: 'Sex',
        datenschutz: 'Data Privacy',
        message_long: 'Message (long)',
        message_short: 'Message (short)',
        newsletter_opt_in: 'Newsletter Opt-In',
        street: 'Street & House No.',
        address: 'Address',
        datenschutz_teilnahmebedingungen:
            'Privacy Policy and Terms of Participation',
    },
};

/**
 * Composable for app-wide translations.
 * Usage: const { t, locale } = useI18n();
 */
export function useI18n() {
    const page = usePage();
    const locale = computed<Locale>(() => {
        const l = page.props.locale as string | undefined;
        return l === 'en' ? 'en' : 'de';
    });

    const t = (
        key: string,
        varsOrFallback?: Record<string, string | number> | string,
        vars?: Record<string, string | number>,
    ): string => {
        const dict = translations[locale.value] ?? translations['de'];
        const fallback =
            typeof varsOrFallback === 'string'
                ? varsOrFallback
                : humanizeKey(key.split('.').pop() ?? key);
        const actualVars =
            typeof varsOrFallback === 'object' ? varsOrFallback : vars;

        let str = dict[key] ?? fallback;
        if (actualVars) {
            for (const [k, v] of Object.entries(actualVars)) {
                str = str.replace(`{${k}}`, String(v));
            }
        }
        return str;
    };

    const tField = (fieldKey: string): string => {
        const overrides = fieldOverrides[locale.value] ?? fieldOverrides['de'];

        // 1. Check manual overrides
        if (overrides[fieldKey]) {
            return overrides[fieldKey];
        }

        // 2. Humanize the key
        const humanized = humanizeKey(fieldKey);

        // 3. English — humanized is already English
        if (locale.value === 'en') {
            return humanized;
        }

        // 4. Check reactive cache
        const cacheKey = `${locale.value}:${fieldKey}`;
        if (autoTranslateCache.value[cacheKey]) {
            return autoTranslateCache.value[cacheKey];
        }

        // 5. Fire async translation, return humanized in the meantime
        autoTranslate(humanized, locale.value).then((translated) => {
            autoTranslateCache.value = {
                ...autoTranslateCache.value,
                [cacheKey]: translated,
            };
        });

        return humanized;
    };

    return { t, tField, locale, humanizeKey, autoTranslateCache };
}
