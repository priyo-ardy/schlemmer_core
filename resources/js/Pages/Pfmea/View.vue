<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, onMounted, nextTick, onUnmounted } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout, inheritAttrs: false });

const props = defineProps({
    allIds: {
        type: Array,
        default: () => []
    },
    data: {
        type: Object,
        default: () => ({})
    },
    departments: {
        type: Array,
        default: () => []
    },
    materials: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const header = computed(() => props.data?.header || {});

// State editing & loading
const isEditing = ref(false);
const isLoadingHistory = ref(false);
const showSaveModal = ref(false);

const isDeptDropDownOpen = ref(false);
const deptSearch = ref('');
const highlightedDeptIndex = ref(-1);
const searchInput = ref(null);

const isStageDropDownOpen = ref(false);
const stageSearch = ref('');
const highlightedStageIndex = ref(-1);
const searchStageInput = ref(null);
const optionsStageList = ref(null);

const isMaterialOpen = ref(false);
const materialSearch = ref('');
const highlightedMaterialIndex = ref(-1);
const dropdownMaterials = ref([]);
const materialPage = ref(1);
const isMaterialLoading = ref(false);
const searchMaterialInput = ref(null);
const optionsMaterialList = ref(null);
const activeSelectedMaterial = ref(null);
const hasMore = ref(true);

const isProjectOpen = ref(false);
const projectSearch = ref('');
const highlightedProjectIndex = ref(-1);
const dropdownProjects = ref([]);
const projectPage = ref(1);
const isProjectLoading = ref(false);
const searchProjectInput = ref(null);
const optionsProjectList = ref(null);
const activeSelectedProject = ref(null);
const hasMoreProjects = ref(true);

const openedRowIndex = ref(null);
const processSearch = ref('');
const highlightedProcessIndex = ref(-1);
const processDropdown = ref([]);
const processPage = ref(1);
const isProcessLoading = ref(false);
const searchProcessInput = ref(null);
const optionsProcessList = ref(null);
const hasProcessMore = ref(true);

const createBlankitem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    process_id: '',
    revision: '',
    sequence: '',
    process_parent: '',
    process_child: '',
    selected_process: null,
    errors: { material_id: null }
});

const form = useForm({
    id: '',
    code: '',
    date: '',
    department_id: '',
    version: '',
    scope: '',
    project_id: '',
    material_id: '',
    drawing_change: '',
    process_responsibility: 'Development, Manufacturing, Quality, Business, Logistics',
    reason: '',
    prepared_by: '',
    reviewed_by: '',
    approved_by: '',
    core_teams: [],
    details: []
});

// Watcher hapus error reason saat user mulai ngetik
watch(() => form.reason, (newVal) => {
    if (newVal && newVal.trim()) {
        form.clearErrors('reason');
    }
});

// Populate Form & Update Defaults
const populateForm = (data) => {
    if (!data || !data.header) return;

    const formattedDate = data.header.date ? dayjs(data.header.date).format('YYYY-MM-DD') : '';
    
    activeSelectedProject.value = data.header.project || null;
    activeSelectedMaterial.value = data.header.material || null;

    const allUsers = props.users && props.users.length > 0 ? props.users : (page.props.users || []);
    const mappedTeams = (data.core_teams || []).map(item => {
        const userObj = allUsers.find(u => u.id === (item.user_id || item.id));
        return userObj || {
            id: item.user_id || item.id,
            name: item.team?.name || item.name || item.user?.name || `User #${item.user_id || item.id}`
        };
    });

    const mappedDetails = (data.details || []).map(item => {
        const pf = item.process_function || item.process;
        return {
            row_key: item.uuid || Math.random().toString(36).substring(2, 9),
            process_id: item.process_id || '',
            order: item.order || 1,
            sequence: pf?.sequence || '',
            process_parent: pf?.process_parent || '',
            process_child: pf?.process_child || '',
            revision: pf?.revision ? `Rev. ${pf.revision}` : '',
            selected_process: pf || (item.process_id ? { id: item.process_id, name: `Process #${item.process_id}` } : null)
        };
    });

    if (mappedDetails.length === 0) {
        mappedDetails.push(createBlankitem());
    }

    form.id = data.header.id || '';
    form.code = data.header.code || '';
    form.date = formattedDate;
    // Strictly UUID
    form.department_id = data.header.department?.uuid || data.header.department_id || '';
    form.version = data.header.version || 0;
    form.scope = data.header.scope || '';
    form.project_id = data.header.project_id || '';
    form.material_id = data.header.material_id || '';
    form.drawing_change = data.header.material?.drawing_change ?? '';
    form.process_responsibility = data.header.process_responsibility || 'Development, Manufacturing, Quality, Business, Logistics';
    form.reason = '';
    form.prepared_by = data.header.prepared_by || '';
    form.reviewed_by = data.header.reviewed_by || '';
    form.approved_by = data.header.approved_by || '';
    form.core_teams = mappedTeams;
    form.details = mappedDetails;

    form.defaults({
        id: form.id,
        code: form.code,
        date: form.date,
        department_id: form.department_id,
        version: form.version,
        scope: form.scope,
        project_id: form.project_id,
        material_id: form.material_id,
        drawing_change: form.drawing_change,
        process_responsibility: form.process_responsibility,
        reason: '',
        prepared_by: form.prepared_by,
        reviewed_by: form.reviewed_by,
        approved_by: form.approved_by,
        core_teams: mappedTeams,
        details: mappedDetails
    });
};

watch(
    () => props.data,
    (newData) => {
        populateForm(newData);
    },
    { immediate: true, deep: true }
);

const cancelEdit = () => {
    isEditing.value = false;
    form.clearErrors();
    populateForm(props.data);
};

// Department
const filteredDept = computed(() => {
    if (!deptSearch.value) return props.departments;
    const lowerSearch = deptSearch.value.toLowerCase();
    return props.departments.filter(c => 
        c.name.toLowerCase().includes(lowerSearch) || 
        (c.short_name && c.short_name.toLowerCase().includes(lowerSearch))
    );
});

watch(deptSearch, () => {
    highlightedDeptIndex.value = filteredDept.value.length > 0 ? 0 : -1;
});

const toggleDeptDropdown = async () => {
    if (!isEditing.value) return;
    isDeptDropDownOpen.value = !isDeptDropDownOpen.value;
    
    if (isDeptDropDownOpen.value) {
        highlightedDeptIndex.value = filteredDept.value.length > 0 ? 0 : -1;
        await nextTick();
        searchInput.value?.focus();
    }
};

const moveDown = () => {
    if (highlightedDeptIndex.value < filteredDept.value.length - 1) {
        highlightedDeptIndex.value++;
        ensureVisible();
    }
};

const moveUp = () => {
    if (highlightedDeptIndex.value > 0) {
        highlightedDeptIndex.value--;
        ensureVisible();
    }
};

const selectHighlighted = () => {
    if (highlightedDeptIndex.value >= 0 && highlightedDeptIndex.value < filteredDept.value.length) {
        const targetDept = filteredDept.value[highlightedDeptIndex.value];
        selectDept(targetDept);
    }
};

// Strictly matching via UUID
const selectedDeptName = computed(() => {
    if (!form.department_id) return "Select department ...";

    const dept = props.departments.find(c => String(c.uuid) === String(form.department_id));
    if (dept) return `${dept.short_name} - ${dept.name}`;

    if (props.data?.header?.department) {
        return `${props.data.header.department.short_name} - ${props.data.header.department.name}`;
    }

    return "Select department ...";
});

// Strictly assign dept.uuid
const selectDept = (dept) => {
    if (!isEditing.value) return;
    form.department_id = dept.uuid;
    isDeptDropDownOpen.value = false;
    deptSearch.value = "";
    highlightedDeptIndex.value = -1;
};

const optionsList = ref(null);
const ensureVisible = () => {
    nextTick(() => {
        const listEl = optionsList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedDeptIndex.value];
        if (!activeEl) return;
        const listScrollTop = listEl.scrollTop;
        const listHeight = listEl.clientHeight;
        const activeTop = activeEl.offsetTop;
        const activeHeight = activeEl.clientHeight;

        if (activeTop + activeHeight > listScrollTop + listHeight) {
            listEl.scrollTop = activeTop + activeHeight - listHeight;
        } else if (activeTop < listScrollTop) {
            listEl.scrollTop = activeTop;
        }
    });
};

// Document scope
const PRODUCTION_STAGES = [
    { id: 'prototype', name: 'Prototype', short_name: 'PROTO' },
    { id: 'pre_launch', name: 'Pre-Launch', short_name: 'PRE' },
    { id: 'containment_epc', name: 'Containment EPC', short_name: 'EPC' },
    { id: 'mass_production', name: 'Mass Production', short_name: 'MP' }
];

const ensureStageVisible = () => {
    nextTick(() => {
        const listEl = optionsStageList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedStageIndex.value];
        if (!activeEl) return;
        const listScrollTop = listEl.scrollTop;
        const listHeight = listEl.clientHeight;
        const activeTop = activeEl.offsetTop;
        const activeHeight = activeEl.clientHeight;

        if (activeTop + activeHeight > listScrollTop + listHeight) {
            listEl.scrollTop = activeTop + activeHeight - listHeight;
        } else if (activeTop < listScrollTop) {
            listEl.scrollTop = activeTop;
        }
    });
};

const filteredStages = computed(() => {
    if (!stageSearch.value) return PRODUCTION_STAGES;
    const lowerSearch = stageSearch.value.toLowerCase();
    return PRODUCTION_STAGES.filter(s => 
        s.name.toLowerCase().includes(lowerSearch) || 
        s.short_name.toLowerCase().includes(lowerSearch)
    );
});

const selectedStageName = computed(() => {
    if (!form.scope) return "Document scope  ...";
    const stage = PRODUCTION_STAGES.find(s => s.id === form.scope);
    return stage ? `${stage.name}` : "Select document scope ...";
});

watch(stageSearch, () => {
    highlightedStageIndex.value = filteredStages.value.length > 0 ? 0 : -1;
});

const toggleStagesDropdown = async () => {
    if (!isEditing.value) return;
    isStageDropDownOpen.value = !isStageDropDownOpen.value;
    if (isStageDropDownOpen.value) {
        highlightedStageIndex.value = filteredStages.value.length > 0 ? 0 : -1;
        await nextTick();
        searchStageInput.value?.focus();
    }
};

const moveStageDown = () => {
    if (highlightedStageIndex.value < filteredStages.value.length - 1) {
        highlightedStageIndex.value++;
        ensureStageVisible();
    }
};

const moveStageUp = () => {
    if (highlightedStageIndex.value > 0) {
        highlightedStageIndex.value--;
        ensureStageVisible();
    }
};

const selectStageHighlighted = () => {
    if (highlightedStageIndex.value >= 0 && highlightedStageIndex.value < filteredStages.value.length) {
        const target = filteredStages.value[highlightedStageIndex.value];
        selectStage(target.id);
    }
};

const selectStage = (id) => {
    if (!isEditing.value) return;
    form.scope = id;
    isStageDropDownOpen.value = false;
    stageSearch.value = "";
    highlightedStageIndex.value = -1;
};

// Material
const selectedMaterialName = computed(() => {
    if (!form.material_id || !activeSelectedMaterial.value) return "Select material...";
    const mat = activeSelectedMaterial.value.material || activeSelectedMaterial.value;
    return mat && mat.code ? `[${mat.code}] - ${mat.name}` : "Select material ...";
});

const fetchMaterials = async (isNewSearch = false) => {
    if (!form.project_id) {
        dropdownMaterials.value = [];
        hasMore.value = false;
        return;
    }

    if (isNewSearch) {
        materialPage.value = 1;
        dropdownMaterials.value = [];
        hasMore.value = true;
        isMaterialLoading.value = false;
    }

    if (isMaterialLoading.value || !hasMore.value) return;
    isMaterialLoading.value = true;

    try {
        const response = await axios.get(
            `/api/v1/projects/${form.project_id}/material`,
            {
                params: {
                    search: materialSearch.value,
                    page: materialPage.value,
                },
            }
        );

        if (isNewSearch) {
            dropdownMaterials.value = response.data.data;
        } else {
            dropdownMaterials.value.push(...response.data.data);
        }

        hasMore.value = response.data.has_more;
        if (hasMore.value) {
            materialPage.value++;
        }
    } catch (error) {
        console.error("Gagal load material:", error);
    } finally {
        isMaterialLoading.value = false;
    }
};

const handleMaterialScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchMaterials(false);
    }
};

let debounceTimeout = null;
const handleSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchMaterials(true);
    }, 400);
};

const toggleMaterialDropdown = async () => {
    if (!isEditing.value) return;
    isMaterialOpen.value = !isMaterialOpen.value;
    if (isMaterialOpen.value) {
        highlightedMaterialIndex.value = 0;
        if (dropdownMaterials.value.length === 0) {
            await fetchMaterials(true);
        }
        await nextTick();
        searchMaterialInput.value?.focus();
    }
};

const selectMaterial = (mat) => {
    if (!isEditing.value) return;
    const targetMat = mat.material || mat;
    form.material_id = targetMat.id;
    form.drawing_change = targetMat.drawing_change === "" || targetMat.drawing_change === null ? 0 : targetMat.drawing_change;
    activeSelectedMaterial.value = targetMat;
    isMaterialOpen.value = false;
    materialSearch.value = "";
};

const moveMaterialDown = () => {
    if (highlightedMaterialIndex.value < dropdownMaterials.value.length - 1) {
        highlightedMaterialIndex.value++;
        ensureMaterialVisible();
    }
};

const moveMaterialUp = () => {
    if (highlightedMaterialIndex.value > 0) {
        highlightedMaterialIndex.value--;
        ensureMaterialVisible();
    }
};

const selectMaterialHighlighted = () => {
    if (highlightedMaterialIndex.value >= 0 && highlightedMaterialIndex.value < dropdownMaterials.value.length) {
        selectMaterial(dropdownMaterials.value[highlightedMaterialIndex.value]);
    }
};

const ensureMaterialVisible = () => {
    nextTick(() => {
        const listEl = optionsMaterialList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedMaterialIndex.value];
        if (!activeEl) return;
        if (activeEl.offsetTop + activeEl.clientHeight > listEl.scrollTop + listEl.clientHeight) {
            listEl.scrollTop = activeEl.offsetTop + activeEl.clientHeight - listEl.clientHeight;
        } else if (activeEl.offsetTop < listEl.scrollTop) {
            listEl.scrollTop = activeEl.offsetTop;
        }
    });
};

const clearMaterial = () => {
    if (!isEditing.value) return;
    form.material_id = null;
    activeSelectedMaterial.value = null;
    highlightedMaterialIndex.value = -1;
    materialSearch.value = "";
    fetchMaterials(true);
};

// Project
const fetchProjects = async (isNewSearch = false) => {
    if (isProjectLoading.value) return;

    if (isNewSearch) {
        projectPage.value = 1;
        dropdownProjects.value = [];
        hasMoreProjects.value = true;
    }

    if (!hasMoreProjects.value) return;
    isProjectLoading.value = true;

    try {
        const response = await axios.get('/api/v1/projects', {
            params: {
                search: projectSearch.value,
                page: projectPage.value
            }
        });

        dropdownProjects.value = [...dropdownProjects.value, ...response.data.data];
        hasMoreProjects.value = response.data.has_more;

        if (hasMoreProjects.value) {
            projectPage.value++;
        }
    } catch (error) {
        console.error("Gagal load project data:", error);
    } finally {
        isProjectLoading.value = false;
    }
};

const selectedProjectName = computed(() => {
    if (!form.project_id || !activeSelectedProject.value) return "Select project...";
    return `[${activeSelectedProject.value.code}] - ${activeSelectedProject.value.name}`;
});

const toggleProjectDropdown = async() => {
    if (!isEditing.value) return;
    isProjectOpen.value = !isProjectOpen.value;
    if (isProjectOpen.value) {
        highlightedProjectIndex.value = 0;
        if (dropdownProjects.value.length === 0) {
            await fetchProjects(true);
        }
        await nextTick();
        searchProjectInput.value?.focus();
    }
};

const selectProject = (project) => {
    if (!isEditing.value) return;
    form.project_id = project.id;
    activeSelectedProject.value = project;
    isProjectOpen.value = false;
    projectSearch.value = "";
};

const moveProjectDown = () => {
    if (highlightedProjectIndex.value < dropdownProjects.value.length - 1) {
        highlightedProjectIndex.value++;
        ensureProjectVisible();
    }
};

const moveProjectUp = () => {
    if (highlightedProjectIndex.value > 0) {
        highlightedProjectIndex.value--;
        ensureProjectVisible();
    }
};

const selectProjectHighlighted = () => {
    if (highlightedProjectIndex.value >= 0 && highlightedProjectIndex.value < dropdownProjects.value.length) {
        selectProject(dropdownProjects.value[highlightedProjectIndex.value]);
    }
};

const ensureProjectVisible = () => {
    nextTick(() => {
        const listEl = optionsProjectList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedProjectIndex.value];
        if (!activeEl) return;
        if (activeEl.offsetTop + activeEl.clientHeight > listEl.scrollTop + listEl.clientHeight) {
            listEl.scrollTop = activeEl.offsetTop + activeEl.clientHeight - listEl.clientHeight;
        } else if (activeEl.offsetTop < listEl.scrollTop) {
            listEl.scrollTop = activeEl.offsetTop;
        }
    });
};

const clearProject = () => {
    if (!isEditing.value) return;
    form.project_id = null;
    activeSelectedProject.value = null;
    highlightedProjectIndex.value = -1;
    projectSearch.value = "";
    fetchProjects(true);
};

const handleProjectScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchProjects(false);
    }
};

let debounceProjectTimeout = null;
const handleProjectSearch = () => {
    clearTimeout(debounceProjectTimeout);
    debounceProjectTimeout = setTimeout(() => {
        fetchProjects(true);
    }, 400);
};

watch(
    errors,
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

// Process template
const dropdownStyle = ref({
    position: 'fixed',
    top: '0px',
    left: '0px',
    width: '0px',
    zIndex: '9999'
});

const selectedProcessName = (item) => {
    if (!item.process_id) return "Select process template ...";
    return item.selected_process?.name || "Select process template ...";
};

const fetchProcess = async(isNewSearch = false) => {
    if (isProcessLoading.value) return;

    if (isNewSearch) {
        processPage.value = 1;
        processDropdown.value = [];
        hasProcessMore.value = true;
    }

    if (!hasProcessMore.value) return;

    isProcessLoading.value = true;

    try {
        const response = await axios.get('/api/v1/process-template', {
            params: {
                search: processSearch.value,
                page: processPage.value
            }
        });

        processDropdown.value = [ ...processDropdown.value, ...response.data.data];
        hasProcessMore.value = response.data.has_more;

        if (hasProcessMore.value) {
            processPage.value++;
        }
    } catch(error) {
        console.error("Failed to load process template data: ", error);
    } finally {
        isProcessLoading.value = false;
    }
};

const handleProcessScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchProcess(false);
    }
};

let processDebounceTimeout = null;
const handleProcessSearch = () => {
    clearTimeout(processDebounceTimeout);
    processDebounceTimeout = setTimeout(() => {
        fetchProcess(true);
    }, 400);
};

const updateProcessDropdownPosition = (index) => {
    const triggerEl = document.getElementById(`process-trigger-${index}`);
    if (triggerEl) {
        const rect = triggerEl.getBoundingClientRect();
        const dropdownHeight = 220;
        const spaceBelow = window.innerHeight - rect.bottom;

        let topPos = rect.bottom;
        if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
            topPos = rect.top - dropdownHeight;
        }

        dropdownStyle.value = {
            position: 'fixed',
            top: `${topPos}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            zIndex: '9999'
        };
    }
};

const toggleProcessDropdown = async(index) => {
    if (!isEditing.value) return;
    if (openedRowIndex.value === index) {
        openedRowIndex.value = null;
    } else {
        openedRowIndex.value = index;
        highlightedProcessIndex.value = 0;
        processSearch.value = "";

        await nextTick();
        updateProcessDropdownPosition(index);

        if (processDropdown.value.length === 0) {
            await fetchProcess(true);
        }

        await nextTick();

        if (searchProcessInput.value && searchProcessInput.value[index]) {
            searchProcessInput.value[index].focus();
        } else if (searchProcessInput.value && typeof searchProcessInput.value.focus === 'function') {
            searchProcessInput.value.focus();
        }
    }
};

const selectProcess = (prc, index) => {
    if (!isEditing.value) return;
    const item = form.details[index];
    if (item) {
        item.process_id = prc.id;
        item.selected_process = prc;
        item.sequence = prc.sequence || '';
        item.process_parent = prc.process_parent || '';
        item.process_child = prc.process_child || '';
        item.revision = prc.revision ? "Rev. " + prc.revision : '';

        if (item.errors) item.errors.process_id = null;
    }
    openedRowIndex.value = null;
    processSearch.value = "";
    fetchProcess(true);
};

const moveProcessDown = () => {
    if (highlightedProcessIndex.value < processDropdown.value.length - 1) {
        highlightedProcessIndex.value++;
        ensureProcessVisible();
    }
};

const moveProcessUp = () => {
    if (highlightedProcessIndex.value > 0) {
        highlightedProcessIndex.value--;
        ensureProcessVisible();
    }
};

const selectProcessHighlighted = (index) => {
    if (highlightedProcessIndex.value >= 0 && highlightedProcessIndex.value < processDropdown.value.length) {
        selectProcess(processDropdown.value[highlightedProcessIndex.value], index);
    }
};

const ensureProcessVisible = () => {
    nextTick(() => {
        const listEl = optionsProcessList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedProcessIndex.value];
        if (!activeEl) return;
        if (activeEl.offsetTop + activeEl.clientHeight > listEl.scrollTop + listEl.clientHeight) {
            listEl.scrollTop = activeEl.offsetTop + activeEl.clientHeight - listEl.clientHeight;
        } else if (activeEl.offsetTop < listEl.scrollTop) {
            listEl.scrollTop = activeEl.offsetTop;
        }
    });
};

const clearProcess = (index) => {
    if (!isEditing.value) return;
    const item = form.details[index];
    if (item) {
        item.process_id = null;
        item.selected_process = null;
        item.sequence = '';
        item.process_parent = '';
        item.process_child = '';
        item.revision = '';
        if (item.errors) item.errors.process_id = null;
    }
    highlightedProcessIndex.value = -1;
    processSearch.value = "";
    fetchProcess(true);
};

const handleWindowResizeOrScroll = () => {
    if (openedRowIndex.value !== null) {
        updateProcessDropdownPosition(openedRowIndex.value);
    }
};

onMounted(() => {
    window.addEventListener('resize', handleWindowResizeOrScroll);
    window.addEventListener('scroll', handleWindowResizeOrScroll, true);
    document.addEventListener("click", handleClickOutside);
    fetchUsers();
});

onUnmounted(() => {
    window.removeEventListener('resize', handleWindowResizeOrScroll);
    window.removeEventListener('scroll', handleWindowResizeOrScroll, true);
    document.removeEventListener("click", handleClickOutside);
});

// Toolbar detail
const selectedRowIndex = ref(null);

const onNew = () => {
    if (!isEditing.value) return;
    form.details.push(createBlankitem());
    selectedRowIndex.value = form.details.length - 1;
};

const onInsert = () => {
    if (!isEditing.value) return;
    if (selectedRowIndex.value !== null && selectedRowIndex.value !== undefined) {
        form.details.splice(selectedRowIndex.value, 0, createBlankitem());
    } else {
        onNew();
    }
};

const OnDelete = () => {
    if (!isEditing.value) return;
    if (selectedRowIndex.value !== null) {
        if (form.details.length > 1) {
            form.details.splice(selectedRowIndex.value, 1);
            if (selectedRowIndex.value >= form.details.length) {
                selectedRowIndex.value = form.details.length - 1;
            }
        } else {
            toast.warning("There must be at least one row in the table");
        }
    } else {
        toast.info("Select or click one of the table rows first to delete it.");
    }
};

const onDeleteAll = () => {
    if (!isEditing.value) return;
    form.details = [createBlankitem()];
    selectedRowIndex.value = 0;
};

// Core teams
const users = ref([]);
const userSearch = ref("");
const isUserDropdownOpen = ref(false);
const userLoading = ref(false);

const fetchUsers = async () => {
    try {
        userLoading.value = true;
        const { data } = await axios.get('/api/v1/users');
        users.value = data.data ?? data;
    } catch (err) {
        console.error(err);
    } finally {
        userLoading.value = false;
    }
};

const filteredUsers = computed(() => {
    const list = users.value.length > 0 ? users.value : (props.users || []);
    if (!userSearch.value) return list;
    return list.filter(user => user.name.toLowerCase().includes(userSearch.value.toLowerCase()));
});

const toggleUser = (user) => {
    if (!isEditing.value) return;
    const index = form.core_teams.findIndex(item => item.id === user.id);
    if (index >= 0) {
        form.core_teams.splice(index, 1);
    } else {
        form.core_teams.push(user);
    }
    userSearch.value = "";
};

const removeUser = (id) => {
    if (!isEditing.value) return;
    form.core_teams = form.core_teams.filter(item => item.id !== id);
};

const isSelectedUser = (id) => {
    return form.core_teams.some(item => item.id === id);
};

const dropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isUserDropdownOpen.value = false;
    }
};

// Actions & Navigation
const firstData = () => {
    if (!props.allIds || props.allIds.length === 0) return;
    const firstId = props.allIds[0];
    if (header.value?.id === firstId) {
        toast.info('You are in the first data');
        return;
    }
    router.get(`/pfmea/${firstId}/view`);
};

const lastData = () => {
    if (!props.allIds || props.allIds.length === 0) return;
    const lastId = props.allIds[props.allIds.length - 1];
    if (header.value?.id === lastId) {
        toast.info('You are in the last data');
        return;
    }
    router.get(`/pfmea/${lastId}/view`);
};

const prevData = () => {
    if (!props.allIds || props.allIds.length === 0) return;
    const currentIndex = props.allIds.indexOf(header.value?.id);
    if (currentIndex > 0) {
        router.get(`/pfmea/${props.allIds[currentIndex - 1]}/view`);
    } else {
        toast.info('You are in the first data');
    }
};

const nextData = () => {
    if (!props.allIds || props.allIds.length === 0) return;
    const currentIndex = props.allIds.indexOf(header.value?.id);
    if (currentIndex >= 0 && currentIndex < props.allIds.length - 1) {
        router.get(`/pfmea/${props.allIds[currentIndex + 1]}/view`);
    } else {
        toast.info('You are in the last data');
    }
};

const newForm = () => {
    router.get('/pfmea/create');
};

const handleOpenSaveModal = () => {
    if (!isEditing.value) return;
    showSaveModal.value = true;
};

const validateAndSave = () => {
    // Validasi frontend untuk reason
    if (!form.reason || !form.reason.trim()) {
        form.setError('reason', 'Revision / change reason is required.');
        return;
    }

    form.put(`/pfmea/${form.id}`, {
        onSuccess: () => {
            showSaveModal.value = false;
            isEditing.value = false;
            form.reason = '';
            toast.success("Document updated successfully");
        },
        onError: () => {
            toast.error("Validation error, please check your input");
        }
    });
};

const deleteSelected = (id) => {
    if (!id) return;
    if (confirm("Are you sure you want to delete this document?")) {
        router.delete(`/pfmea/${id}`, {
            onSuccess: () => toast.success("Document deleted successfully")
        });
    }
};

// --- CHANGE LOGS & REVISION HISTORY LOGIC ---
const showLogs = ref(false);
const dataLogs = ref([]);
const showDetailModal = ref(false);
const selectedDetailLog = ref(null);

const changeLogs = async (id) => {
    isLoadingHistory.value = true;
    try {
        const response = await axios(`/pfmea/${id}/logs`);
        dataLogs.value = response.data;
        showLogs.value = true;
    } catch (err) {
        const firstError = typeof err === 'object' && err !== null ? Object.values(err)[0] : 'Failed to fetch logs';
        toast.error(typeof firstError === 'string' ? firstError : 'Error fetching change logs');
    } finally {
        isLoadingHistory.value = false;
    }
};

const openDetailsModal = (log) => {
    selectedDetailLog.value = log;
    showDetailModal.value = true;
};

const formatTeams = (teams) => {
    if (!teams || !Array.isArray(teams) || teams.length === 0) return '-';
    
    return teams
        .map(t => t.team?.name || t.name || t.user?.name || (t.user_id ? `User #${t.user_id}` : `User #${t.id}`))
        .filter(Boolean)
        .join(', ');
};

const getHeaderDiffs = (log) => {
    const beforeH = log.before?.header || {};
    const afterH = log.after?.header || {};

    const beforeTeams = formatTeams(log.before?.core_teams);
    const afterTeams = formatTeams(log.after?.core_teams);

    const comparisons = [
        { label: 'Code', before: beforeH.code, after: afterH.code },
        { label: 'Scope', before: beforeH.scope, after: afterH.scope },
        { label: 'Department', before: beforeH.department?.name || beforeH.department?.short_name, after: afterH.department?.name || afterH.department?.short_name },
        { label: 'Project', before: beforeH.project?.name || beforeH.project?.code, after: afterH.project?.name || afterH.project?.code },
        { label: 'Material', before: beforeH.material?.name || beforeH.material?.code, after: afterH.material?.name || afterH.material?.code },
        { label: 'Process Responsibility', before: beforeH.process_responsibility, after: afterH.process_responsibility },
        { label: 'Core Teams', before: beforeTeams, after: afterTeams }
    ];

    if (log.event_name === 'create') {
        return comparisons.filter(c => c.after && c.after !== '-').map(c => ({ label: c.label, before: '-', after: c.after }));
    }

    const changed = comparisons.filter(c => (c.before || '') !== (c.after || ''));
    return changed.length > 0 ? changed : [{ label: 'Remark', before: '-', after: '-' }];
};

const getDetailItems = (log) => {
    if (!log) return [];
    const beforeDetails = log.before?.details || [];
    const afterDetails = log.after?.details || [];

    const maxLen = Math.max(beforeDetails.length, afterDetails.length);
    const result = [];

    for (let i = 0; i < maxLen; i++) {
        const b = beforeDetails[i] || {};
        const a = afterDetails[i] || {};
        const pfB = b.process_function || b.process || {};
        const pfA = a.process_function || a.process || {};

        const rawFields = [
            { label: 'Order', before: b.order, after: a.order },
            { label: 'Process Function Name', before: pfB.name, after: pfA.name },
            { label: 'Sequence', before: pfB.sequence, after: pfA.sequence },
            { label: 'Process Parent', before: pfB.process_parent, after: pfA.process_parent },
            { label: 'Process Child', before: pfB.process_child, after: pfA.process_child },
            { 
                label: 'Revision', 
                before: pfB.revision !== undefined && pfB.revision !== null ? `Rev. ${pfB.revision}` : undefined, 
                after: pfA.revision !== undefined && pfA.revision !== null ? `Rev. ${pfA.revision}` : undefined 
            }
        ];

        const changedFields = rawFields.filter(f => {
            const valBefore = (f.before === null || f.before === undefined || f.before === '') ? '-' : String(f.before);
            const valAfter = (f.after === null || f.after === undefined || f.after === '') ? '-' : String(f.after);

            if (log.event_name === 'create') {
                return valAfter !== '-';
            }

            return valBefore !== valAfter;
        }).map(f => ({
            label: f.label,
            before: (f.before === null || f.before === undefined || f.before === '') ? '-' : String(f.before),
            after: (f.after === null || f.after === undefined || f.after === '') ? '-' : String(f.after)
        }));

        if (changedFields.length > 0) {
            result.push({
                order: a.order || b.order || (i + 1),
                updater: a.updater?.name || log.after?.header?.updater?.name || 'System User',
                date: dayjs(a.created_at || log.created_at).format('DD/MM/YYYY, HH.mm.ss'),
                event_name: log.event_name,
                fields: changedFields
            });
        }
    }

    return result;
};

const hasDetailChanges = (log) => {
    if (!log) return false;
    
    const beforeDetails = log.before?.details || [];
    const afterDetails = log.after?.details || [];

    if (log.event_name === 'create' && afterDetails.length > 0) return true;
    if (beforeDetails.length !== afterDetails.length) return true;

    for (let i = 0; i < beforeDetails.length; i++) {
        const b = beforeDetails[i] || {};
        const a = afterDetails[i] || {};
        const pfB = b.process_function || b.process || {};
        const pfA = a.process_function || a.process || {};

        if (
            b.order !== a.order ||
            b.process_id !== a.process_id ||
            pfB.name !== pfA.name ||
            pfB.sequence !== pfA.sequence ||
            pfB.process_parent !== pfA.process_parent ||
            pfB.process_child !== pfA.process_child ||
            pfB.revision !== pfA.revision
        ) {
            return true;
        }
    }
    
    return false;
};
</script>

<template>
    <Head title="PFMEA Document View"/>
    <!-- Page wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">PMFEA Process Function</h1>
                    <p class="text-xs text-slate-500 mt-1">View and manage PFMEA template process function.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <!-- Toolbar -->
                            <div class="flex items-center gap-3"> 
                                <div class="flex items-center gap-1.5">
                                    <Link href="/pfmea" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        <span class="hidden sm:inline">Back</span>
                                    </Link>

                                    <div class="w-px h-6 bg-slate-300 mx-1" v-if="!isEditing"></div>

                                    <div class="flex items-center bg-slate-100/80 p-0.5 border border-slate-200/50 shadow-inner" v-if="!isEditing">
                                        <button @click="firstData" :disabled="allIds.indexOf(header?.id) === 0" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed" title="First">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="prevData" :disabled="allIds.indexOf(header?.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed" title="Previous">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="nextData" :disabled="allIds.indexOf(header?.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed" title="Next">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="lastData" :disabled="allIds.indexOf(header?.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed" title="Last">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <div class="flex items-center gap-1.5">
                                    <button v-if="isEditing" type="button" @click="handleOpenSaveModal" :disabled="form.processing"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
                                        <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                        </svg>
                                        <span>{{ form.processing ? 'Saving...' : 'Save' }}</span>
                                    </button>

                                    <button 
                                        v-if="!isEditing"
                                        type="button" 
                                        @click="newForm"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-green-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span>New</span>
                                    </button>

                                    <button type="button" 
                                        @click="isEditing ? cancelEdit() : isEditing = true" 
                                        :class="[
                                            'flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold transition-all shadow-sm border',
                                            isEditing 
                                                ? 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' 
                                                : 'bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border-blue-100 hover:border-blue-600'
                                        ]">
                                        <svg v-if="!isEditing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>{{ isEditing ? 'Cancel' : 'Edit' }}</span>
                                    </button>
                                    
                                    <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                    <div class="relative group">
                                        <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-xs font-bold transition-all shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                            </svg>
                                            Tools
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600 group-hover:rotate-180 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 top-full mt-1.5 w-40 bg-white border border-slate-100 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden origin-top-right transform scale-95 group-hover:scale-100">
                                            <div class="py-1 flex flex-col">
                                                <button type="button" @click="changeLogs(header.id)" :disabled="isLoadingHistory" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                    <svg v-if="!isLoadingHistory" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                        <path d="M21 6.375c0 2.692-4.03 4.875-9 4.875S3 9.067 3 6.375 7.03 1.5 12 1.5s9 2.183 9 4.875Z" />
                                                        <path d="M12 12.75c2.685 0 5.19-.586 7.078-1.609a8.283 8.283 0 0 0 1.897-1.384c.016.121.025.244.025.368C21 12.817 16.97 15 12 15s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.285 8.285 0 0 0 1.897 1.384C6.809 12.164 9.315 12.75 12 12.75Z" />
                                                        <path d="M12 16.5c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 15.914 9.315 16.5 12 16.5Z" />
                                                        <path d="M12 20.25c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 19.664 9.315 20.25 12 20.25Z" />
                                                    </svg>
                                                    <svg v-else class="animate-spin h-3.5 w-3.5 text-blue-600" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>

                                                    {{ isLoadingHistory ? 'Loading...' : 'View Change Logs' }}
                                                </button>
                                                <button type="button" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                    Print Data
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-px h-4 bg-slate-200 mx-1"></div>

                                    <button type="button" @click="deleteSelected(header?.id)" class="p-1.5 text-slate-400 hover:text-white hover:bg-rose-500 transition-colors" title="Delete Record">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>     
            </div>

            <!-- Form header (Ditambahkan relative z-20 agar berada di atas layer tabel) -->
            <div class="relative z-20 bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Document No. <span class="text-rose-500">*</span>
                            </label>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </div>
                            <input type="text" v-model="form.code" :readonly="!isEditing" placeholder="ISLMGxxxxFMEA01(00)" maxlength="255" autocomplete="off" 
                            :class="[
                                'w-full pl-10 pr-4 py-2.5 border text-xs font-medium transition-all outline-none',
                                !isEditing ? 'bg-slate-100/70 border-slate-200 text-slate-600 cursor-not-allowed' : 'bg-slate-50 border-slate-200 focus:bg-white focus:ring-2 focus:border-blue-500 focus:ring-blue-100 text-slate-700',
                                form.errors.code ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' : ''
                            ]">
                        </div>
                        <p v-if="form.errors.code" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.code }}</p>
                        <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique PFMEA Document No.</p>
                    </div>

                    <div class="lg:col-span-1 flex flex-col justify-start">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Revision
                            </label>
                        </div>
                        <div class="w-full flex">
                            <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700 w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span class="text-xs font-black tracking-widest">v.<span class="text-sm">{{ form.version || 0 }}</span></span>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                            Issue Date <span class="text-rose-500">*</span>
                        </label>
                        <div>
                            <input type="date" v-model="form.date" :readonly="!isEditing"
                            :class="[
                                'w-full pl-2 pr-4 py-2.5 border text-xs font-medium transition-all outline-none mt-2',
                                !isEditing ? 'bg-slate-100/70 border-slate-200 text-slate-600 cursor-not-allowed' : 'bg-slate-50 border-slate-200 focus:bg-white focus:ring-2 focus:border-blue-500 focus:ring-blue-100 text-slate-700',
                                form.errors.date ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' : ''
                            ]">
                            <p v-if="form.errors.date" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.date }}</p>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Issued Department <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div v-if="isDeptDropDownOpen && isEditing" @click="isDeptDropDownOpen = false" class="fixed inset-0 z-40"></div>

                            <div
                                @click="toggleDeptDropdown"
                                class="relative z-40 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none flex justify-between items-center transition-all"
                                :class="[
                                    !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed text-slate-600' : 'bg-white cursor-pointer border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                    form.errors.department_id ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600' : ''
                                ]"
                            >
                                <span :class="form.department_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedDeptName }}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180 text-blue-500': isDeptDropDownOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <p v-if="form.errors.department_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.department_id }}</p>

                            <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <div v-if="isDeptDropDownOpen && isEditing" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input ref="searchInput" type="text" v-model="deptSearch" @click.stop @keydown.down.prevent="moveDown" @keydown.up.prevent="moveUp" @keydown.enter.prevent="selectHighlighted" @keydown.esc="isDeptDropDownOpen = false" class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white" placeholder="Type to search..." />
                                        </div>
                                    </div>

                                    <div ref="optionsList" class="max-h-48 overflow-y-auto">
                                        <div v-for="(dept, index) in filteredDept" :key="dept.uuid || dept.id" @click="selectDept(dept)" @mouseenter="highlightedDeptIndex = index" class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0" :class="[(form.department_id === dept.uuid) ? 'border-l-2 border-l-blue-600 font-bold' : '', index === highlightedDeptIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700']">
                                            {{ dept.short_name }} - {{ dept.name }}
                                        </div>
                                        <div v-if="filteredDept.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Department not found ... "{{ deptSearch }}"</div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Document Scope <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div v-if="isStageDropDownOpen && isEditing" @click="isStageDropDownOpen = false" class="fixed inset-0 z-40"></div>

                            <div
                                @click="toggleStagesDropdown"
                                class="relative z-40 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none flex justify-between items-center transition-all"
                                :class="[
                                    !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed text-slate-600' : 'bg-white cursor-pointer border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                    form.errors.scope ? 'border-rose-500 focus:border-rose-500 text-rose-600' : ''
                                ]"
                            >
                                <span :class="form.scope ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedStageName }}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180 text-blue-500': isStageDropDownOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <p v-if="form.errors.scope" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.scope }}</p>

                            <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <div v-if="isStageDropDownOpen && isEditing" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input ref="searchStageInput" type="text" v-model="stageSearch" @click.stop @keydown.down.prevent="moveStageDown" @keydown.up.prevent="moveStageUp" @keydown.enter.prevent="selectStageHighlighted" @keydown.esc="isStageDropDownOpen = false" class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white" placeholder="Type to search scope..." />
                                        </div>
                                    </div>

                                    <div ref="optionsStageList" class="max-h-48 overflow-y-auto">
                                        <div v-for="(stage, index) in filteredStages" :key="stage.id" @click="selectStage(stage.id)" @mouseenter="highlightedStageIndex = index" class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0" :class="[form.scope === stage.id ? 'border-l-2 border-l-blue-600 font-bold' : '', index === highlightedStageIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700']">
                                            {{ stage.name }}
                                        </div>
                                        <div v-if="filteredStages.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Document scope not found ... "{{ stageSearch }}"</div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Project <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div v-if="isProjectOpen && isEditing" @click="isProjectOpen = false" class="fixed inset-0 z-40"></div>

                            <div
                                @click="toggleProjectDropdown"
                                class="relative z-40 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none flex justify-between items-center transition-all"
                                :class="[
                                    !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed text-slate-600' : 'bg-white cursor-pointer border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                    form.errors.project_id ? 'border-rose-500 focus:border-rose-500 text-rose-600' : ''
                                ]"
                            >
                                <span :class="form.project_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedProjectName }}
                                </span>

                                <div class="flex items-center space-x-1.5 relative z-40">
                                    <svg v-if="form.project_id && isEditing" @click.stop="clearProject" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180 text-blue-500': isProjectOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <p v-if="form.errors.project_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.project_id }}</p>

                            <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <div v-if="isProjectOpen && isEditing" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input ref="searchProjectInput" type="text" v-model="projectSearch" @click.stop @input="handleProjectSearch" @keydown.down.prevent="moveProjectDown" @keydown.up.prevent="moveProjectUp" @keydown.enter.prevent="selectProjectHighlighted" @keydown.esc="isProjectOpen = false" class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white" placeholder="Type to search project..." />
                                        </div>
                                    </div>

                                    <div ref="optionsProjectList" @scroll="handleProjectScroll" class="max-h-48 overflow-y-auto">
                                        <div v-for="(prj, index) in dropdownProjects" :key="prj.id" @click="selectProject(prj)" @mouseenter="highlightedProjectIndex = index" class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0" :class="[form.project_id === prj.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '', index === highlightedProjectIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700']">
                                            <span class="text-black font-bold">[{{ prj.code }}]</span> - {{ prj.name }} ({{ prj.customer?.alias }})
                                        </div>
                                        <div v-if="isProjectLoading" class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100">Loading items...</div>
                                        <div v-if="dropdownProjects.length === 0 && !isProjectLoading" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Project not found ... "{{ projectSearch }}"</div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Part No. <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div v-if="isMaterialOpen && isEditing" @click="isMaterialOpen = false" class="fixed inset-0 z-40"></div>

                            <div
                                @click="toggleMaterialDropdown"
                                class="relative z-40 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none flex justify-between items-center transition-all"
                                :class="[
                                    !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed text-slate-600' : 'bg-white cursor-pointer border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                    form.errors.material_id ? 'border-rose-500 focus:border-rose-500 text-rose-600' : ''
                                ]"
                            >
                                <span :class="form.material_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedMaterialName }}
                                </span>

                                <div class="flex items-center space-x-1.5 relative z-40">
                                    <svg v-if="form.material_id && isEditing" @click.stop="clearMaterial" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180 text-blue-500': isMaterialOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <p v-if="form.errors.material_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.material_id }}</p>

                            <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <div v-if="isMaterialOpen && isEditing" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input ref="searchMaterialInput" type="text" v-model="materialSearch" @click.stop @input="handleSearch" @keydown.down.prevent="moveMaterialDown" @keydown.up.prevent="moveMaterialUp" @keydown.enter.prevent="selectMaterialHighlighted" @keydown.esc="isMaterialOpen = false" class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white" placeholder="Type to search material..." />
                                        </div>
                                    </div>

                                    <div ref="optionsMaterialList" @scroll="handleMaterialScroll" class="max-h-48 overflow-y-auto">
                                        <div v-for="(mat, index) in dropdownMaterials" :key="mat.id" @click="selectMaterial(mat)" @mouseenter="highlightedMaterialIndex = index" class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0" :class="[form.material_id === (mat.material?.id || mat.id) ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '', index === highlightedMaterialIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700']">
                                            [{{ mat.material?.code || mat.code }}] - {{ mat.material?.name || mat.name }}
                                        </div>
                                        <div v-if="isMaterialLoading" class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100">Loading items...</div>
                                        <div v-if="dropdownMaterials.length === 0 && !isMaterialLoading" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Material not found ... "{{ materialSearch }}"</div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Change Level.
                        </label>
                        <input type="text" v-model="form.drawing_change" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-200 bg-slate-100/70 text-slate-600 cursor-not-allowed text-right" readonly placeholder="Part No. changing level" />
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Process Responsibility.
                        </label>
                        <input 
                            type="text" 
                            v-model="form.process_responsibility" 
                            :readonly="!isEditing"
                            class="w-full px-3 py-2 border text-xs transition-all outline-none"
                            :class="[
                                !isEditing ? 'bg-slate-100/70 border-slate-200 text-slate-600 cursor-not-allowed' : 'bg-slate-50 border-slate-200 focus:bg-white focus:ring-2 focus:border-blue-500 focus:ring-blue-100 text-slate-700',
                                form.errors.process_responsibility ? 'border-rose-500 focus:border-rose-500' : ''
                            ]"
                            placeholder="Process Responsibility" 
                        />
                        <p v-if="form.errors.process_responsibility" class="block mt-1 text-[9px] font-bold text-rose-500">{{ form.errors.process_responsibility }}</p>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Core Teams. <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative" ref="dropdownRef">
                            <div
                                @click="isEditing && (isUserDropdownOpen = true)"
                                class="relative w-full min-h-[38px] px-3 py-2 border transition-all duration-200 pr-9"
                                :class="[
                                    !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed' : 'bg-slate-50 border-slate-300 focus-within:bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-200 cursor-text',
                                    form.errors.core_teams ? 'border-rose-500' : ''
                                ]"
                            >
                                <div class="flex flex-wrap items-center gap-1">
                                    <span v-for="user in form.core_teams" :key="user.id" class="inline-flex items-center gap-1 border border-slate-300 bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                                        {{ user.name }}
                                        <button v-if="isEditing" type="button" @click.stop="removeUser(user.id)" class="text-slate-400 hover:text-red-600 transition-colors">✕</button>
                                    </span>

                                    <input
                                        v-if="isEditing"
                                        v-model="userSearch"
                                        @focus="isUserDropdownOpen = true"
                                        class="flex-1 min-w-[120px] bg-transparent outline-none text-xs text-slate-800"
                                        placeholder="Search user..."
                                    />
                                </div>

                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isUserDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <p v-if="form.errors.core_teams" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.core_teams }}</p>

                            <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-1 scale-95">
                                <div v-if="isUserDropdownOpen && isEditing" class="absolute z-50 mt-1 w-full bg-white border border-slate-300 shadow-lg max-h-64 overflow-y-auto">
                                    <div v-if="filteredUsers.length === 0" class="px-3 py-2 text-xs text-slate-500">No user found.</div>
                                    <div v-for="user in filteredUsers" :key="user.id" @click="toggleUser(user)" class="flex items-center justify-between px-3 py-2 text-xs cursor-pointer hover:bg-slate-100 transition-colors">
                                        <span>{{ user.name }}</span>
                                        <svg v-if="isSelectedUser(user.id)" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form detail -->
            <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5 bg-slate-50/50">
                    <button @click="onNew" :disabled="!isEditing" :class="['text-xs font-semibold transition-all duration-200', !isEditing ? 'text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:text-blue-600 hover:underline cursor-pointer underline-offset-4']">New</button>
                    <button @click="onInsert" :disabled="!isEditing" :class="['text-xs font-semibold transition-all duration-200', !isEditing ? 'text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:text-blue-600 hover:underline cursor-pointer underline-offset-4']">Insert</button>
                    <button @click="OnDelete" :disabled="!isEditing" :class="['text-xs font-semibold transition-all duration-200', !isEditing ? 'text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:text-red-600 hover:underline cursor-pointer underline-offset-4']">Delete</button>
                    <button @click="onDeleteAll" :disabled="!isEditing" :class="['text-xs font-semibold transition-all duration-200', !isEditing ? 'text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:text-red-700 hover:underline cursor-pointer underline-offset-4']">Delete All</button>
                </div>
                <div class="overflow-auto max-h-[100vh]">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider z-30 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-16">No.</th>
                                <th class="px-4 py-3 min-w-[350px]">Process Function</th>
                                <th class="px-4 py-3 min-w-[100px]">Process Sequence</th>
                                <th class="px-4 py-3 min-w-[100px]">Process Parent</th>
                                <th class="px-4 py-3 min-w-[100px]">Process Child</th>
                                <th class="px-4 py-3 min-w-[100px]">Revision</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, index) in form.details"
                                :key="item.row_key"
                                @click="selectedRowIndex = index"
                                @focusin="selectedRowIndex = index"
                                :class="['transition-colors duration-150 cursor-pointer', selectedRowIndex === index ? 'bg-blue-50/80 hover:bg-blue-50 border-l-4 border-l-blue-500' : 'hover:bg-slate-50/50']"
                            >
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <div
                                            :id="`process-trigger-${index}`"
                                            @click="toggleProcessDropdown(index)"
                                            class="relative z-1 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none flex justify-between items-center transition-all"
                                            :class="[
                                                !isEditing ? 'bg-slate-100/70 border-slate-200 cursor-not-allowed text-slate-600' : 'bg-white cursor-pointer border-slate-300 focus:border-blue-500 text-slate-800',
                                                form.errors[`details.${index}.process_id`] ? 'border-rose-500 bg-rose-50 text-rose-600' : ''
                                            ]"
                                        >
                                            <span :class="item.process_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                {{ selectedProcessName(item) }}
                                            </span>

                                            <div class="flex items-center space-x-1.5 relative z-10">
                                                <svg v-if="item.process_id && isEditing" @click.stop="clearProcess(index)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': openedRowIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>

                                        <p v-if="form.errors[`details.${index}.process_id`]" class="mt-1 text-[10px] font-bold text-rose-500">
                                            {{ form.errors[`details.${index}.process_id`] }}
                                        </p>

                                        <Teleport to="body">
                                            <div v-if="openedRowIndex === index && isEditing" class="fixed inset-0 z-[9998]" @click="openedRowIndex = null"></div>
                                            
                                            <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                                <div
                                                    v-if="openedRowIndex === index && isEditing"
                                                    :style="dropdownStyle"
                                                    class="bg-white border border-slate-200 shadow-2xl overflow-hidden rounded-sm"
                                                    @click.stop
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                            <input
                                                                ref="searchProcessInput"
                                                                type="text"
                                                                v-model="processSearch"
                                                                @input="handleProcessSearch"
                                                                @keydown.down.prevent="moveProcessDown"
                                                                @keydown.up.prevent="moveProcessUp"
                                                                @keydown.enter.prevent="selectProcessHighlighted(index)"
                                                                @keydown.esc="openedRowIndex = null"
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                                placeholder="Type to search process template..."
                                                            />
                                                        </div>
                                                    </div>

                                                    <div ref="optionsProcessList" @scroll="handleProcessScroll" class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="(prc, idx) in processDropdown"
                                                            :key="prc.id"
                                                            @click="selectProcess(prc, index)"
                                                            @mouseenter="highlightedProcessIndex = idx"
                                                            class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                                                            :class="[
                                                                item.process_id === prc.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '',
                                                                idx === highlightedProcessIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                                                            ]"
                                                        >
                                                            {{ prc.name }}
                                                        </div>
                                                        <div v-if="isProcessLoading" class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100">Loading items...</div>
                                                        <div v-if="processDropdown.length === 0 && !isProcessLoading" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Process template not found ... "{{ processSearch }}"</div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.sequence" placeholder="Process Sequence" readonly class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-200 bg-slate-100/70 text-slate-600 cursor-not-allowed">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.process_parent" placeholder="Process Parent" readonly class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-200 bg-slate-100/70 text-slate-600 cursor-not-allowed">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.process_child" placeholder="Process Child" readonly class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-200 bg-slate-100/70 text-slate-600 cursor-not-allowed">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.revision" placeholder="Process function revision" readonly class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-200 bg-slate-100/70 text-slate-600 cursor-not-allowed">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL 1: SAVE CONFIRMATION WITH REASON -->
        <Teleport to="body">
            <div v-if="showSaveModal" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 shadow-2xl w-full max-w-md p-6 font-sans">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Revision / Change Reason
                        </h3>
                        <button type="button" @click="showSaveModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <p class="text-xs text-slate-500 mb-3">
                        Please enter the reason for updating this PFMEA document. This reason will be recorded in the change log history.
                    </p>

                    <div class="mb-4">
                        <textarea
                            v-model="form.reason"
                            rows="3"
                            class="w-full p-2.5 border text-xs font-medium transition-all outline-none resize-none"
                            :class="[
                                form.errors.reason 
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-rose-600 bg-rose-50/50' 
                                    : 'bg-slate-50 border-slate-300 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:ring-blue-100 text-slate-800'
                            ]"
                            placeholder="e.g., Updated process function details and team members..."
                        ></textarea>
                        <p v-if="form.errors.reason" class="mt-1.5 text-[10px] font-bold text-rose-500 font-sans">
                            {{ form.errors.reason }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showSaveModal = false"
                            class="px-3.5 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="validateAndSave"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Saving...' : 'Save Changes' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 2: PROCESS FUNCTION REVISION HISTORY TIMELINE -->
        <Teleport to="body">
            <div v-if="showLogs" class="fixed inset-0 z-[9990] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col font-sans">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white sticky top-0 z-10">
                        <div class="flex items-center gap-3">
                            <div class="p-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-black text-slate-900 tracking-wider uppercase">
                                PROCESS FUNCTION REVISION HISTORY : {{ header?.code || 'DOCUMENT' }}
                            </h3>
                        </div>
                        <button type="button" @click="showLogs = false" class="text-slate-400 hover:text-slate-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Timeline Content Body -->
                    <div class="p-6 overflow-y-auto space-y-6 bg-slate-50/50">
                        <div v-if="dataLogs.length === 0" class="text-center py-12 text-slate-400 font-medium text-xs">
                            No revision logs found.
                        </div>

                        <div v-else class="relative border-l-2 border-slate-200 ml-3 pl-6 space-y-6">
                            <div v-for="(log, idx) in dataLogs" :key="log.id" class="relative group">
                                <!-- Timeline Marker Dot -->
                                <div class="absolute -left-[31px] top-3.5 h-3 w-3 bg-blue-600 border-2 border-white shadow-sm"></div>

                                <!-- Card Item -->
                                <div class="bg-white border border-slate-200 shadow-xs p-5 space-y-4">
                                    <!-- Meta Header -->
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-slate-600 border border-slate-300 uppercase">
                                                REV. {{ log.after?.header?.version ?? log.revision ?? 0 }}
                                            </span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700 bg-blue-100">
                                                {{ log.event_name }}
                                            </span>
                                            <span class="text-xs font-black text-slate-800 ml-1">
                                                {{ log.after?.header?.updater?.name || log.before?.header?.updater?.name || 'System User' }}
                                            </span>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-400">
                                            {{ dayjs(log.created_at).format('DD/MM/YYYY, HH.mm.ss') }}
                                        </span>
                                    </div>

                                    <!-- Change Reason -->
                                    <div>
                                        <span class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider">
                                            CHANGE REASON
                                        </span>
                                        <p class="text-xs font-bold text-slate-800 mt-1">
                                            {{ log.change_reason || 'No description provided.' }}
                                        </p>
                                    </div>

                                    <!-- Header Diff Table -->
                                    <div class="border border-slate-100 bg-white">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wider">
                                                    <th class="py-2 px-3 text-slate-400 w-1/3">FIELD DATA</th>
                                                    <th class="py-2 px-3 text-rose-500 w-1/3">DATA BEFORE</th>
                                                    <th class="py-2 px-3 text-emerald-600 w-1/3">DATA AFTER</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 text-xs">
                                                <tr v-for="diff in getHeaderDiffs(log)" :key="diff.label">
                                                    <td class="py-2 px-3 font-semibold text-slate-600">{{ diff.label }}</td>
                                                    <td class="py-2 px-3">
                                                        <span v-if="diff.before !== '-'" class="bg-rose-100/80 text-rose-700 line-through px-1.5 py-0.5 font-mono text-[11px]">
                                                            {{ diff.before }}
                                                        </span>
                                                        <span v-else class="text-slate-400 font-mono">-</span>
                                                    </td>
                                                    <td class="py-2 px-3">
                                                        <span v-if="diff.after !== '-'" class="bg-emerald-100/80 text-emerald-800 font-bold px-1.5 py-0.5 font-mono text-[11px]">
                                                            {{ diff.after }}
                                                        </span>
                                                        <span v-else class="text-slate-400 font-mono">-</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Trigger Show Details Modal -->
                                    <div v-if="hasDetailChanges(log)" class="pt-2 border-t border-slate-100/60 mt-4">
                                        <button type="button" @click="openDetailsModal(log)" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition-all">
                                            Show Details ...
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 3: HISTORY DETAILS (DETAILS BREAKDOWN) -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-[9995] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col font-sans">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white sticky top-0 z-10">
                        <div class="flex items-center gap-3">
                            <div class="p-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-black text-slate-900 tracking-wider uppercase flex items-center gap-2">
                                HISTORY DETAILS 
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-mono px-2 py-0.5 rounded-xs uppercase">
                                    REV. {{ selectedDetailLog?.after?.header?.version ?? selectedDetailLog?.revision ?? 1 }}
                                </span>
                            </h3>
                        </div>
                        <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-6 bg-slate-50/50">
                        <div v-if="getDetailItems(selectedDetailLog).length === 0" class="text-center py-12 text-slate-400 font-medium text-xs bg-white border border-slate-200">
                            No process function details were changed in this revision.
                        </div>

                        <div v-else v-for="item in getDetailItems(selectedDetailLog)" :key="item.order" class="bg-white border border-slate-200 shadow-xs">
                            <div class="flex items-center justify-between bg-slate-100/70 px-4 py-3 border-b border-slate-200">
                                <div>
                                    <h4 class="text-xs font-black text-slate-800">Detail Order {{ item.order }}</h4>
                                    <p class="text-[10px] text-slate-500 font-medium mt-0.5">
                                        Revision {{ selectedDetailLog?.after?.header?.version ?? selectedDetailLog?.revision ?? 1 }} • {{ item.updater }} • {{ item.date }}
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700 bg-blue-100">
                                    {{ item.event_name }}
                                </span>
                            </div>

                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wider">
                                        <th class="py-2.5 px-4 text-slate-400 w-1/3">FIELD DATA</th>
                                        <th class="py-2.5 px-4 text-rose-500 w-1/3">DATA BEFORE</th>
                                        <th class="py-2.5 px-4 text-emerald-600 w-1/3">DATA AFTER</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs font-mono">
                                    <tr v-for="field in item.fields" :key="field.label">
                                        <td class="py-2 px-4 font-sans font-bold text-slate-600">{{ field.label }}</td>
                                        <td class="py-2 px-4">
                                            <span v-if="field.before !== '-'" class="bg-rose-100/80 text-rose-700 line-through px-1.5 py-0.5">
                                                {{ field.before }}
                                            </span>
                                            <span v-else class="text-slate-400">-</span>
                                        </td>
                                        <td class="py-2 px-4">
                                            <span v-if="field.after !== '-'" class="bg-emerald-100/80 text-emerald-800 font-bold px-1.5 py-0.5">
                                                {{ field.after }}
                                            </span>
                                            <span v-else class="text-slate-400">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>