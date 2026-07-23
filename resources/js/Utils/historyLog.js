// resources/js/Utils/historyLog.js
//
// Pure helper functions buat ngolah data audit log (header & detail).
// Dipisah dari komponen biar bisa dipakai ulang di halaman lain yang formatnya sama
// (header/detail revision history), tanpa nge-duplicate logic diffing-nya.

const IGNORED_HEADER_KEYS = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by'];
const IGNORED_DETAIL_KEYS = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by', 'header_id'];

/**
 * Bandingin before/after, hasilnya array of {field, before, after}.
 * @param {Object} log
 * @param {Object} [opts]
 * @param {string|null} [opts.basePath] - null kalau log.before/log.after langsung berisi field
 *        (mis. endpoint /process/{id}/logs), atau nama key kalau nested (mis. 'header' buat
 *        endpoint /process/logs/{id} yang bentuknya {header:{...}, detail:[...]}).
 * @param {string[]} [opts.ignoredKeys]
 */
export const getChangedFields = (log, { basePath = null, ignoredKeys = IGNORED_HEADER_KEYS } = {}) => {
    const changes = [];
    const before = basePath ? log.before?.[basePath] : log.before;
    const after = basePath ? log.after?.[basePath] : log.after;

    if (log.event_name === 'update' && before && after) {
        Object.keys(after).forEach((key) => {
            if (!ignoredKeys.includes(key) && before[key] !== after[key]) {
                changes.push({ field: key, before: before[key], after: after[key] });
            }
        });
    } else if (log.event_name === 'delete' && before) {
        Object.keys(before).forEach((key) => {
            if (!ignoredKeys.includes(key) && before[key] !== null) {
                changes.push({ field: key, before: before[key], after: null });
            }
        });
    } else if ((log.event_name === 'create' || log.event_name === 'restore') && after) {
        Object.keys(after).forEach((key) => {
            if (!ignoredKeys.includes(key) && after[key] !== null) {
                changes.push({ field: key, before: null, after: after[key] });
            }
        });
    }

    return changes;
};

/**
 * Sama kayak getChangedFields tapi di level detail/rows (before.detail[] vs after.detail[]),
 * dicocokin per index array -> row ke berapa.
 */
export const getChangedDetailsFields = (log, ignoredKeys = IGNORED_DETAIL_KEYS) => {
    const changesDetails = [];
    const beforeDetails = log.before?.detail ?? [];
    const afterDetails = log.after?.detail ?? [];
    const maxLength = Math.max(beforeDetails.length, afterDetails.length);

    for (let i = 0; i < maxLength; i++) {
        const before = beforeDetails[i];
        const after = afterDetails[i];

        if (!before && after) {
            Object.entries(after).forEach(([key, value]) => {
                if (ignoredKeys.includes(key)) return;
                changesDetails.push({ row: i + 1, field: key, before: null, after: value });
            });
            continue;
        }

        if (before && !after) {
            Object.entries(before).forEach(([key, value]) => {
                if (ignoredKeys.includes(key)) return;
                changesDetails.push({ row: i + 1, field: key, before: value, after: null });
            });
            continue;
        }

        Object.keys(after).forEach((key) => {
            if (ignoredKeys.includes(key)) return;
            if (before[key] !== after[key]) {
                changesDetails.push({ row: i + 1, field: key, before: before[key], after: after[key] });
            }
        });
    }

    return changesDetails;
};

/**
 * Group ulang detailChanges (hasil getChangedDetailsFields) berdasarkan nomor row.
 * Butuh log.detailChanges udah keisi duluan (lihat composable).
 */
export const groupChangedDetailsByRow = (log) => {
    return (log.detailChanges ?? []).reduce((groups, item) => {
        if (!groups[item.row]) groups[item.row] = [];
        groups[item.row].push(item);
        return groups;
    }, {});
};

/**
 * snake_case -> Title Case buat label kolom di UI.
 */
export const formatFieldName = (text) => {
    if (!text) return '';
    return text.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};