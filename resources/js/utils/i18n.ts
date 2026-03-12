import { computed } from 'vue';

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

        // Column settings
        'columns.title': 'Spalten anzeigen/ausblenden',
        'columns.select_all': 'Alle auswählen',
        'columns.clear_all': 'Alle abwählen',
        'columns.reorder': 'Spaltenreihenfolge ändern (Drag & Drop)',
        'columns.preference_level': 'Einstellungsebene',
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
        'table.no_results_desc': 'Keine Einreichungen entsprechen den aktuellen Filtern.',

        // Bulk actions
        'bulk.selected': 'ausgewählt',
        'bulk.mark_read': 'Als gelesen markieren',
        'bulk.export': 'Exportieren',
        'bulk.delete': 'Löschen',
        'bulk.clear': 'Auswahl aufheben',

        // Actions
        'action.edit': 'Bearbeiten',
        'action.delete': 'Löschen',
        'action.mark': 'Markieren',
        'action.unmark': 'Markierung aufheben',
        'action.read': 'Als gelesen markieren',
        'action.unread': 'Als ungelesen markieren',
        'action.save': 'Speichern',
        'action.cancel': 'Abbrechen',
        'action.view': 'Anzeigen',

        // Retention
        'retention.title': 'Automatische Datenlöschung (Aufbewahrung)',
        'retention.description': 'Legen Sie fest, wie lange Einreichungen für dieses Formular aufbewahrt werden. Nach der angegebenen Anzahl von Tagen löscht der nächtliche Job (läuft täglich um 02:00 Uhr) ältere Datensätze dauerhaft.',
        'retention.days_label': 'Aufbewahrungszeitraum (Tage)',
        'retention.days_placeholder': 'z. B. 365 – leer = dauerhaft',
        'retention.save': 'Regel speichern',
        'retention.saving': 'Wird gespeichert…',
        'retention.saved': '✓ Gespeichert',
        'retention.forever': 'Aktuell: dauerhaft aufbewahren',
        'retention.will_delete': 'Löscht Einreichungen, die älter als {days} Tag(e) sind',

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
        'common.confirm_delete': 'Sind Sie sicher, dass Sie diese Einreichung dauerhaft löschen möchten?',
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

        // Column settings
        'columns.title': 'Show/Hide Columns',
        'columns.select_all': 'Select All',
        'columns.clear_all': 'Clear All',
        'columns.reorder': 'Reorder selected columns (drag & drop)',
        'columns.preference_level': 'Preference level',
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
        'action.mark': 'Mark',
        'action.unmark': 'Unmark',
        'action.read': 'Mark as Read',
        'action.unread': 'Mark as Unread',
        'action.save': 'Save',
        'action.cancel': 'Cancel',
        'action.view': 'View',

        // Retention
        'retention.title': 'Automated Data Deletion (Retention)',
        'retention.description': 'Configure how long submissions for this form are kept. After the specified number of days the purge job (runs nightly at 02:00) will permanently delete older records.',
        'retention.days_label': 'Retention period (days)',
        'retention.days_placeholder': 'e.g. 365 – blank = keep forever',
        'retention.save': 'Save Rule',
        'retention.saving': 'Saving…',
        'retention.saved': '✓ Saved',
        'retention.forever': 'Currently: keep forever',
        'retention.will_delete': 'Will delete submissions older than {days} day(s)',

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
        'common.confirm_delete': 'Are you sure you want to permanently delete this submission?',
    },
};

/**
 * Composable for app-wide translations.
 * Usage: const { t, locale } = useI18n();
 */
export function useI18n() {
    const locale = computed<Locale>(() => 'de');

    const t = (key: string, vars?: Record<string, string | number>): string => {
        const dict = translations[locale.value] ?? translations['de'];
        let str = dict[key] ?? key;
        if (vars) {
            for (const [k, v] of Object.entries(vars)) {
                str = str.replace(`{${k}}`, String(v));
            }
        }
        return str;
    };

    return { t, locale };
}
