// resources/js/Composables/useRevisionHistory.js
import { ref } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { getChangedFields, getChangedDetailsFields } from '@/Utils/historyLog';

/**
 * @param {Object} options
 * @param {(id: any) => string} options.listEndpoint   - endpoint buat ambil list revision history
 * @param {(id: any) => string} options.detailEndpoint - endpoint buat ambil detail perubahan per-row
 *
 * Dipisah endpoint-nya sebagai function param supaya composable ini portable
 * ke halaman lain yang modelnya beda (PFMEA, dst) tinggal ganti URL-nya aja.
 */
/**
 * @param {Object} options
 * @param {(id: any) => string} options.listEndpoint        - endpoint buat ambil list revision history
 * @param {(id: any) => string} [options.detailEndpoint]    - endpoint buat ambil detail perubahan per-row (opsional, kalau halamannya gak butuh drill-down detail)
 * @param {string|null} [options.headerBasePath]            - null kalau log.before/log.after langsung berisi field,
 *        atau nama key kalau nested (mis. 'header' buat endpoint yang bentuknya {header:{...}, detail:[...]})
 *
 * Dipisah endpoint-nya sebagai function param supaya composable ini portable
 * ke halaman lain yang modelnya beda (PFMEA, dst) tinggal ganti URL-nya aja.
 */
export function useRevisionHistory({ listEndpoint, detailEndpoint = null, headerBasePath = null }) {
    // modal 1: list revision history (per header)
    const showListModal = ref(false);
    const isLoadingList = ref(false);
    const historyLogs = ref([]);
    const selectedLabel = ref('');

    // modal 2: detail perubahan per-row dari 1 revision tertentu (opsional)
    const showDetailModal = ref(false);
    const isLoadingDetail = ref(false);
    const historyLogsDetails = ref([]);

    const openHistory = async (id, label = '') => {
        showListModal.value = true;
        isLoadingList.value = true;
        historyLogs.value = [];
        selectedLabel.value = label;

        try {
            const { data } = await axios.get(listEndpoint(id));
            const rawLogs = data.logs ?? data;
            // precompute detailChanges di sini, jangan panggil getChangedFields() tiap render di template
            historyLogs.value = rawLogs.map((log) => ({
                ...log,
                detailChanges: getChangedFields(log, { basePath: headerBasePath }),
            }));
        } catch (e) {
            toast.error(e?.response?.data?.message ?? 'Failed to load history logs');
        } finally {
            isLoadingList.value = false;
        }
    };

    const closeHistoryModal = () => {
        showListModal.value = false;
        historyLogs.value = [];
        selectedLabel.value = '';
    };

    const openHistoryDetail = async (id) => {
        // console.log('openHistoryDetail dipanggil', id);

        if (!detailEndpoint) {
            // console.warn('detailEndpoint belum di-set');
            return;
        }

        showDetailModal.value = true;
        // console.log('showDetailModal', showDetailModal.value); // <-- TARUH DI SINI

        isLoadingDetail.value = true;

        try {
            // console.log('request ke', detailEndpoint(id));

            const { data } = await axios.get(detailEndpoint(id));

            // console.log('response', data);

            historyLogsDetails.value = data.map((log) => ({
                ...log,
                detailChanges: getChangedDetailsFields(log),
            }));

            // console.log('historyLogsDetails', historyLogsDetails.value);
        } finally {
            isLoadingDetail.value = false;
        }
    };

    const closeHistoryDetailModal = () => {
        showDetailModal.value = false;
        historyLogsDetails.value = [];
    };

    return {
        showListModal,
        isLoadingList,
        historyLogs,
        selectedLabel,
        openHistory,
        closeHistoryModal,

        showDetailModal,
        isLoadingDetail,
        historyLogsDetails,
        openHistoryDetail,
        closeHistoryDetailModal,
    };
}