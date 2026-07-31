<script setup>
import { ref, reactive, computed, nextTick, onMounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

// Form State
const form = reactive({
  project_id: null,
  material_id: null,
  errors: {}
})

// Data Raw Lists
const projectsList = ref([])
const materialsList = ref([])

// Loading States
const isProjectLoading = ref(false)
const isMaterialLoading = ref(false)
const loadingPfmea = ref(false)

// --- TOAST NOTIFICATION STATE ---
const toast = reactive({
  show: false,
  type: 'success', // 'success' | 'warning' | 'error'
  message: ''
})

let toastTimeout = null
const showToast = (type, message) => {
  clearTimeout(toastTimeout)
  toast.type = type
  toast.message = message
  toast.show = true

  toastTimeout = setTimeout(() => {
    toast.show = false
  }, 4000)
}

// --- PROJECT DROPDOWN STATES ---
const isProjectOpen = ref(false)
const projectSearch = ref('')
const highlightedProjectIndex = ref(0)
const searchProjectInput = ref(null)
const optionsProjectList = ref(null)

// --- MATERIAL DROPDOWN STATES ---
const isMaterialOpen = ref(false)
const materialSearch = ref('')
const highlightedMaterialIndex = ref(0)
const searchMaterialInput = ref(null)
const optionsMaterialList = ref(null)

// --- PFMEA DATA STATE ---
const pfmea = reactive({
  doc_no: '-',
  issue_date: '-',
  issuing_dept: 'R&D Dept.',
  page: '1 of 1',
  key_date: '-',
  part_name: '-',
  part_no: '-',
  dwg_no: '-',
  process_responsibility: 'Development, Manufacturing, Quality, Business, Logistics',
  scope_selected: 'Containment EPC',
  core_team: '-',
  approved_by: '-',
  reviewed_by: '-',
  prepared_by: '-',
  revisions: [],
  items: []
})

// Helper Formatter Tanggal (Format: 1-Jan-2026 atau dengan waktu)
const formatDate = (dateStr) => {
  if (!dateStr || dateStr === '-') return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr

    const day = String(d.getDate()).padStart(2, '0')
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    const month = months[d.getMonth()]
    const year = d.getFullYear()

    return `${day}-${month}-${year}`
  } catch {
    return dateStr
  }
}

// =========================================================================
// API CALLS
// =========================================================================

// 1. Fetch List Projects
const fetchProjects = async () => {
  isProjectLoading.value = true
  try {
    const res = await axios.get('/api/v1/projects')
    projectsList.value = res.data.data || res.data || []
  } catch (error) {
    console.error('Gagal mengambil data project:', error)
    showToast('error', 'Gagal memuat list project!')
  } finally {
    isProjectLoading.value = false
  }
}

// 2. Fetch Materials berdasarkan Project yang dipilih
const fetchMaterials = async (projectId) => {
  if (!projectId) return
  isMaterialLoading.value = true
  try {
    const res = await axios.get(`/api/v1/projects/${projectId}/material`)
    materialsList.value = res.data.data || res.data || []
  } catch (error) {
    console.error('Gagal mengambil data material:', error)
    showToast('error', 'Gagal memuat list material!')
  } finally {
    isMaterialLoading.value = false
  }
}

// 3. Fetch Full Detail PFMEA Data (Ditrik oleh Tombol Search)
const fetchPfmeaData = async () => {
  if (!form.project_id || !form.material_id) {
    showToast('warning', 'Pilih Project dan Material terlebih dahulu!')
    return
  }

  resetPfmeaData()
  loadingPfmea.value = true

  try {
    const res = await axios.get('/api/v1/pfmea', {
      params: {
        project_id: form.project_id,
        material_id: form.material_id,
      }
    })

    const data = res.data.data

    if (data) {
      pfmea.doc_no = data.doc_no || '-'
      pfmea.issue_date = formatDate(data.issue_date)
      pfmea.issuing_dept = data.issuing_dept || 'R&D Dept.'
      pfmea.page = data.page || '1 of 1'
      pfmea.key_date = formatDate(data.key_date)
      pfmea.part_name = data.part_name || '-'
      pfmea.part_no = data.part_no || '-'
      pfmea.dwg_no = data.dwg_no || '-'
      pfmea.process_responsibility = data.process_responsibility || 'Development, Manufacturing, Quality, Business, Logistics'
      pfmea.scope_selected = data.scope || 'Containment EPC'
      pfmea.core_team = data.core_team || '-'
      pfmea.approved_by = data.approved_by || '-'
      pfmea.reviewed_by = data.reviewed_by || '-'
      pfmea.prepared_by = data.prepared_by || '-'
      pfmea.revisions = data.revision_history || data.revisions || []
      pfmea.items = data.items || []

      showToast('success', res.data.message || 'Data PFMEA berhasil dimuat!')
    }
  } catch (error) {
    console.error('Error fetching PFMEA data:', error)
    resetPfmeaData()

    if (error.response) {
      const status = error.response.status
      const serverMessage = error.response.data?.message || 'Terjadi kesalahan pada server.'

      if (status === 404 || status === 400) {
        showToast('warning', serverMessage)
      } else if (status === 500) {
        showToast('error', serverMessage)
      } else {
        showToast('error', `[HTTP ${status}] ${serverMessage}`)
      }
    } else if (error.request) {
      showToast('error', 'Gagal terhubung ke server. Periksa koneksi jaringan Anda!')
    } else {
      showToast('error', 'Terjadi kesalahan sistem yang tidak terduga.')
    }
  } finally {
    loadingPfmea.value = false
  }
}

const resetPfmeaData = () => {
  pfmea.doc_no = '-'
  pfmea.issue_date = '-'
  pfmea.key_date = '-'
  pfmea.part_no = '-'
  pfmea.core_team = '-'
  pfmea.approved_by = '-'
  pfmea.reviewed_by = '-'
  pfmea.prepared_by = '-'
  pfmea.revisions = []
  pfmea.items = []
}

// =========================================================================
// PROJECT DROPDOWN LOGIC
// =========================================================================

const dropdownProjects = computed(() => {
  if (!projectSearch.value) return projectsList.value
  const q = projectSearch.value.toLowerCase()
  return projectsList.value.filter(prj => 
    prj.name?.toLowerCase().includes(q) || 
    prj.code?.toLowerCase().includes(q) ||
    prj.customer?.alias?.toLowerCase().includes(q)
  )
})

const selectedProjectName = computed(() => {
  if (!form.project_id) return 'Select Project...'
  const prj = projectsList.value.find(p => p.id === form.project_id)
  return prj ? `[${prj.code}] - ${prj.name}` : 'Select Project...'
})

const toggleProjectDropdown = () => {
  isProjectOpen.value = !isProjectOpen.value
  if (isProjectOpen.value) {
    isMaterialOpen.value = false
    highlightedProjectIndex.value = 0
    nextTick(() => searchProjectInput.value?.focus())
  }
}

const selectProject = (prj) => {
  form.project_id = prj.id
  isProjectOpen.value = false
  projectSearch.value = ''
  
  clearMaterial()
  fetchMaterials(prj.id)
}

const clearProject = () => {
  form.project_id = null
  projectSearch.value = ''
  clearMaterial()
  materialsList.value = []
}

const handleProjectSearch = () => {
  highlightedProjectIndex.value = 0
}

const moveProjectDown = () => {
  if (highlightedProjectIndex.value < dropdownProjects.value.length - 1) {
    highlightedProjectIndex.value++
    scrollToHighlightedProject()
  }
}

const moveProjectUp = () => {
  if (highlightedProjectIndex.value > 0) {
    highlightedProjectIndex.value--
    scrollToHighlightedProject()
  }
}

const selectProjectHighlighted = () => {
  if (dropdownProjects.value[highlightedProjectIndex.value]) {
    selectProject(dropdownProjects.value[highlightedProjectIndex.value])
  }
}

const scrollToHighlightedProject = () => {
  if (!optionsProjectList.value) return
  const el = optionsProjectList.value.children[highlightedProjectIndex.value]
  if (el) el.scrollIntoView({ block: 'nearest' })
}

// =========================================================================
// MATERIAL DROPDOWN LOGIC
// =========================================================================

const dropdownMaterials = computed(() => {
  if (!materialSearch.value) return materialsList.value
  const q = materialSearch.value.toLowerCase()
  return materialsList.value.filter(item => {
    const mat = item.material || item
    return mat.name?.toLowerCase().includes(q) || 
           mat.code?.toLowerCase().includes(q) ||
           mat.specification?.toLowerCase().includes(q)
  })
})

const selectedMaterialName = computed(() => {
  if (!form.material_id) return 'Select Material...'
  const item = materialsList.value.find(m => (m.material?.id || m.material_id || m.id) === form.material_id)
  if (!item) return 'Select Material...'
  const mat = item.material || item
  return `[${mat.code}] - ${mat.name}`
})

const toggleMaterialDropdown = () => {
  if (!form.project_id) return
  isMaterialOpen.value = !isMaterialOpen.value
  if (isMaterialOpen.value) {
    isProjectOpen.value = false
    highlightedMaterialIndex.value = 0
    nextTick(() => searchMaterialInput.value?.focus())
  }
}

const selectMaterial = (item) => {
  const matId = item.material_id || item.material?.id || item.id
  form.material_id = matId
  isMaterialOpen.value = false
  materialSearch.value = ''
}

const clearMaterial = () => {
  form.material_id = null
  materialSearch.value = ''
  resetPfmeaData()
}

const handleMaterialSearch = () => {
  highlightedMaterialIndex.value = 0
}

const moveMaterialDown = () => {
  if (highlightedMaterialIndex.value < dropdownMaterials.value.length - 1) {
    highlightedMaterialIndex.value++
    scrollToHighlightedMaterial()
  }
}

const moveMaterialUp = () => {
  if (highlightedMaterialIndex.value > 0) {
    highlightedMaterialIndex.value--
    scrollToHighlightedMaterial()
  }
}

const selectMaterialHighlighted = () => {
  if (dropdownMaterials.value[highlightedMaterialIndex.value]) {
    selectMaterial(dropdownMaterials.value[highlightedMaterialIndex.value])
  }
}

const scrollToHighlightedMaterial = () => {
  if (!optionsMaterialList.value) return
  const el = optionsMaterialList.value.children[highlightedMaterialIndex.value]
  if (el) el.scrollIntoView({ block: 'nearest' })
}

onMounted(() => {
  fetchProjects()
})

// --- GROUPING / ROWSPAN LOGIC UNTUK PROCESS STEP & CONTROL DETECTION ---
const groupedPfmeaItems = computed(() => {
  const items = pfmea.items
  let stepSpanCounts = {}
  let controlSpanCounts = {}
  
  // 1. Hitung rowspan untuk Process Step
  for (let i = 0; i < items.length; i++) {
    const currentStep = items[i].process_step
    if (i > 0 && currentStep === items[i - 1].process_step) {
      stepSpanCounts[i] = 0
      let parentIdx = i - 1
      while (parentIdx >= 0 && items[parentIdx].process_step === currentStep) {
        parentIdx--
      }
      parentIdx++
      stepSpanCounts[parentIdx] = (stepSpanCounts[parentIdx] || 1) + 1
    } else {
      stepSpanCounts[i] = 1
    }
  }

  // 2. Hitung rowspan untuk Control Detection (hanya digabung jika process_step-nya sama DAN teks control_detection-nya sama)
  for (let i = 0; i < items.length; i++) {
    const currentControl = items[i].control_detection
    const currentStep = items[i].process_step
    
    if (i > 0 && currentControl === items[i - 1].control_detection && currentStep === items[i - 1].process_step) {
      controlSpanCounts[i] = 0
      let parentIdx = i - 1
      while (parentIdx >= 0 && items[parentIdx].control_detection === currentControl && items[parentIdx].process_step === currentStep) {
        parentIdx--
      }
      parentIdx++
      controlSpanCounts[parentIdx] = (controlSpanCounts[parentIdx] || 1) + 1
    } else {
      controlSpanCounts[i] = 1
    }
  }

  return items.map((item, index) => ({
    ...item,
    rowspanStep: stepSpanCounts[index],
    rowspanControl: controlSpanCounts[index]
  }))
}) 
</script>

<template>
  <Head title="PFMEA Document Viewer" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        PFMEA Document Viewer
      </h2>
    </template>

    <!-- FULLPAGE LOADING OVERLAY -->
    <div v-if="loadingPfmea" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex flex-col items-center justify-center text-white">
      <svg class="animate-spin h-12 w-12 text-white mb-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="text-sm font-bold tracking-wider">Loading PFMEA Data... Please wait.</span>
    </div>

    <!-- FLOATING TOAST NOTIFICATION -->
    <Transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="toast.show" 
        class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white shadow-lg pointer-events-auto border-l-4 p-4 flex items-start space-x-3"
        :class="{
          'border-emerald-500 text-emerald-800': toast.type === 'success',
          'border-amber-500 text-amber-800': toast.type === 'warning',
          'border-rose-500 text-rose-800': toast.type === 'error'
        }"
      >
        <!-- Icon Success -->
        <svg v-if="toast.type === 'success'" class="h-5 w-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Icon Warning -->
        <svg v-if="toast.type === 'warning'" class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        <!-- Icon Error -->
        <svg v-if="toast.type === 'error'" class="h-5 w-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <div class="flex-1 text-xs font-medium">
          <p class="font-bold capitalize">{{ toast.type }}</p>
          <p class="mt-0.5 text-slate-600">{{ toast.message }}</p>
        </div>

        <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>

    <div>
      <div class="max-w-[1920px] mx-auto">
        
        <!-- FILTER BAR SECTION -->
        <div class="bg-white p-4 shadow-sm mb-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-end border-l-4 border-blue-600">    
          
          <!-- 1. MANUAL DROPDOWN PROJECT -->
          <div class="md:col-span-5">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">
              Project <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <div
                v-if="isProjectOpen"
                @click="isProjectOpen = false"
                class="fixed inset-0 z-40"
              ></div>

              <div
                @click="toggleProjectDropdown"
                class="relative z-40 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none bg-white cursor-pointer flex justify-between items-center transition-all"
                :class="[
                  form.errors.project_id
                    ? 'border-rose-500 text-rose-600'
                    : 'border-slate-300 text-slate-800'
                ]"
              >
                <span :class="form.project_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                  {{ selectedProjectName }}
                </span>

                <div class="flex items-center space-x-1.5 relative z-40">
                  <svg
                    v-if="form.project_id"
                    @click.stop="clearProject"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors duration-150"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>

                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                    :class="{'rotate-180 text-blue-500': isProjectOpen}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-out"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="isProjectOpen"
                  class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                >
                  <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                    <div class="relative">
                      <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      <input
                        ref="searchProjectInput"
                        type="text"
                        v-model="projectSearch"
                        @click.stop
                        @input="handleProjectSearch"
                        @keydown.down.prevent="moveProjectDown"
                        @keydown.up.prevent="moveProjectUp"
                        @keydown.enter.prevent="selectProjectHighlighted"
                        @keydown.esc="isProjectOpen = false"
                        class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                        placeholder="Type to search project..."
                      />
                    </div>
                  </div>
                  
                  <div 
                    ref="optionsProjectList" 
                    class="max-h-48 overflow-y-auto"
                  >
                    <div
                      v-for="(prj, index) in dropdownProjects"
                      :key="prj.id"
                      @click="selectProject(prj)"
                      @mouseenter="highlightedProjectIndex = index"
                      class="px-3 py-2 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                      :class="[
                        form.project_id === prj.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '',
                        index === highlightedProjectIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                      ]"
                    >
                      <span class="text-black font-bold">[{{ prj.code }}]</span> - {{ prj.name }}
                      <span v-if="prj.customer?.alias">({{ prj.customer.alias }})</span>
                    </div>

                    <div 
                      v-if="isProjectLoading" 
                      class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100"
                    >
                      Loading items...
                    </div>

                    <div 
                      v-if="dropdownProjects.length === 0 && !isProjectLoading" 
                      class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50"
                    >
                      Project not found ... "{{ projectSearch }}"
                    </div>
                  </div>
                </div>
              </Transition>
            </div>
          </div>

          <!-- 2. MANUAL DROPDOWN MATERIAL -->
          <div class="md:col-span-5">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">
              Material (Part Name) <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <div
                v-if="isMaterialOpen"
                @click="isMaterialOpen = false"
                class="fixed inset-0 z-40"
              ></div>

              <div
                @click="toggleMaterialDropdown"
                class="relative z-40 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none bg-white flex justify-between items-center transition-all"
                :class="[
                  !form.project_id ? 'bg-slate-100 cursor-not-allowed text-slate-400 border-slate-200' : 'cursor-pointer border-slate-300 text-slate-800'
                ]"
              >
                <span :class="form.material_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                  {{ selectedMaterialName }}
                </span>

                <div class="flex items-center space-x-1.5 relative z-40">
                  <svg
                    v-if="form.material_id"
                    @click.stop="clearMaterial"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors duration-150"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>

                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                    :class="{'rotate-180 text-blue-500': isMaterialOpen}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-out"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="isMaterialOpen"
                  class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                >
                  <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                    <div class="relative">
                      <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      <input
                        ref="searchMaterialInput"
                        type="text"
                        v-model="materialSearch"
                        @click.stop
                        @input="handleMaterialSearch"
                        @keydown.down.prevent="moveMaterialDown"
                        @keydown.up.prevent="moveMaterialUp"
                        @keydown.enter.prevent="selectMaterialHighlighted"
                        @keydown.esc="isMaterialOpen = false"
                        class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                        placeholder="Type to search material..."
                      />
                    </div>
                  </div>

                  <div 
                    ref="optionsMaterialList" 
                    class="max-h-48 overflow-y-auto"
                  >
                    <div
                      v-for="(item, index) in dropdownMaterials"
                      :key="item.id"
                      @click="selectMaterial(item)"
                      @mouseenter="highlightedMaterialIndex = index"
                      class="px-3 py-2 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                      :class="[
                        form.material_id === (item.material?.id || item.material_id) ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '',
                        index === highlightedMaterialIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                      ]"
                    >
                      <span class="text-black font-bold">
                        [{{ item.material?.code || item.code }}]
                      </span> 
                      - {{ item.material?.name || item.name }}
                    </div>

                    <div 
                      v-if="isMaterialLoading" 
                      class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100"
                    >
                      Loading materials...
                    </div>

                    <div 
                      v-if="dropdownMaterials.length === 0 && !isMaterialLoading" 
                      class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50"
                    >
                      Material not found ... "{{ materialSearch }}"
                    </div>
                  </div>
                </div>
              </Transition>
            </div>
          </div>

          <!-- 3. TOMBOL SEARCH -->
          <div class="md:col-span-2">
            <button 
              @click="fetchPfmeaData" 
              :disabled="!form.project_id || !form.material_id || loadingPfmea"
              type="button"
              class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-sm transition duration-150 disabled:bg-slate-300 disabled:cursor-not-allowed"
            >
              <svg v-if="!loadingPfmea" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              <svg v-else class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loadingPfmea ? 'Searching...' : 'Search' }}</span>
            </button>
          </div>
        </div>

        <!-- EXCEL WORKSHEET VIEWER CONTAINER -->
        <div class="bg-white overflow-hidden shadow-sm p-4 text-xs font-sans text-black overflow-x-auto">
          <div class="border-2 border-black min-w-[1750px]">
            
            <!-- 1. TOP HEADER ROW -->
            <div class="grid grid-cols-12 border-b-2 border-black">
              <div class="col-span-2 p-2 flex items-center justify-center border-r border-black">
                <img 
                  src="/storage/logo.webp" 
                  alt="Schlemmer Logo" 
                  class="max-h-60 max-w-full object-contain" 
                />
              </div>

              <div class="col-span-6 p-2 text-center border-r border-black flex flex-col justify-center">
                <div class="font-bold text-4xl tracking-widest">Schlemmer Automotive Indonesia</div>
                <div class="font-bold text-3xl tracking-widest mt-1">PFMEA</div>
              </div>

              <div class="col-span-4 grid grid-cols-3 text-[11px]">
                <div class="border-b border-r border-black p-1 font-semibold">Issue Date</div>
                <div class="col-span-2 border-b border-black p-1 bg-yellow-200 font-bold">{{ pfmea.issue_date }}</div>
                <div class="border-b border-r border-black p-1 font-semibold">Issuing Dept.:</div>
                <div class="border-b border-r border-black p-1 bg-yellow-200">{{ pfmea.issuing_dept }}</div>
                <div class="border-b border-black p-1 flex">
                  <span class="font-semibold mr-1">Page:</span> {{ pfmea.page }}
                </div>
                <div class="border-b border-r border-black p-1 text-center font-semibold bg-yellow-300">Approved</div>
                <div class="border-b border-r border-black p-1 text-center font-semibold bg-yellow-300">Reviewed</div>
                <div class="border-b border-black p-1 text-center font-semibold bg-yellow-300">Prepared</div>
                <div class="border-b border-r border-black p-2 text-center bg-yellow-100 font-medium">{{ pfmea.approved_by }}</div>
                <div class="border-b border-r border-black p-2 text-center bg-yellow-100 font-medium">{{ pfmea.reviewed_by }}</div>
                <div class="border-b border-black p-2 text-center bg-yellow-100 font-medium">{{ pfmea.prepared_by }}</div>
                <div class="col-span-3 p-1 font-bold text-right bg-yellow-300 border-t border-black">
                  No.: {{ pfmea.doc_no }}
                </div>
              </div>
            </div>

            <!-- 2. MAIN BODY FORM GRID -->
            <div class="grid grid-cols-12 border-b border-black">
              <div class="col-span-2 border-r border-black grid grid-cols-3">
                <div class="col-span-1 border-r border-black p-2 font-bold flex items-center justify-center [writing-mode:vertical-lr] rotate-180">
                  Scope
                </div>
                <div class="col-span-2 flex flex-col justify-between">
                  <div class="border-b border-black p-1 flex justify-between items-center" :class="{'bg-yellow-300 font-semibold': pfmea.scope_selected === 'prototype'}">
                    <span>Prototype</span>
                    <span v-if="pfmea.scope_selected === 'prototype'">✓</span>
                  </div>
                  <div class="border-b border-black p-1 flex justify-between items-center" :class="{'bg-yellow-300 font-semibold': pfmea.scope_selected === 'pre_launch'}">
                    <span>Pre-Launch</span>
                    <span v-if="pfmea.scope_selected === 'pre_launch'">✓</span>
                  </div>
                  <div class="border-b border-black p-1 flex justify-between items-center" :class="{'bg-yellow-300 font-semibold': pfmea.scope_selected === 'Containment EPC'}">
                    <span>Containment EPC</span>
                    <span v-if="pfmea.scope_selected === 'Containment EPC'">✓</span>
                  </div>
                  <div class="p-1 flex justify-between items-center" :class="{'bg-yellow-300 font-semibold': pfmea.scope_selected === 'mass_production'}">
                    <span>Mass Production</span>
                    <span v-if="pfmea.scope_selected === 'mass_production'">✓</span>
                  </div>
                </div>
              </div>
              <div class="col-span-5 border-r border-black">
                <div class="border-b border-black grid grid-cols-3 bg-yellow-300">
                  <span class="p-1 font-bold border-r border-black">Project Name:</span>
                  <span class="col-span-2 p-1 font-bold">{{ selectedProjectName }}</span>
                </div>
                <div class="border-b border-black grid grid-cols-3 bg-yellow-200">
                  <span class="p-1 font-semibold border-r border-black">Key Date:</span>
                  <span class="col-span-2 p-1 font-bold">{{ pfmea.key_date }}</span>
                </div>
                <div class="border-b border-black grid grid-cols-3 bg-yellow-300">
                  <span class="p-1 font-bold border-r border-black">Part Name:</span>
                  <span class="col-span-2 p-1 font-bold">{{ pfmea.part_name }}</span>
                </div>
                <div class="border-b border-black grid grid-cols-3 bg-yellow-200">
                  <span class="p-1 font-semibold border-r border-black">Part No./Level:</span>
                  <span class="col-span-2 p-1 font-bold">{{ pfmea.part_no }} / {{ pfmea.dwg_no }}</span>
                </div>
                <div class="p-1 font-medium bg-white">
                  <span class="font-semibold">Process Responsibility:</span> {{ pfmea.process_responsibility }}
                </div>
              </div>

              <!-- REVISION HISTORY (Maksimal 3 Baris + Scroll) -->
              <div class="col-span-5 flex flex-col">
                <div class="text-center font-bold border-b border-black bg-gray-100 p-1">Revision History</div>
                <div class="max-h-[115px] overflow-y-auto w-full">
                  <table class="w-full text-center border-collapse">
                    <thead class="sticky top-0 z-10">
                      <tr class="border-b border-black bg-yellow-300 font-semibold">
                        <th class="border-r border-black p-1 w-12">Ver.</th>
                        <th class="border-r border-black p-1 w-28">Date</th>
                        <th class="border-r border-black p-1">Content</th>
                        <th class="border-r border-black p-1 w-16">Approved</th>
                        <th class="border-r border-black p-1 w-16">Reviewed</th>
                        <th class="p-1 w-16">Prepared</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="(rev, idx) in pfmea.revisions" 
                        :key="idx" 
                        class="border-b border-black bg-yellow-100 last:border-b-0"
                      >
                        <td class="border-r border-black p-1 font-bold">{{ rev.ver }}</td>
                        <td class="border-r border-black p-1">{{ formatDate(rev.date) }}</td>
                        <td class="border-r border-black p-1 text-left px-2">{{ rev.content || '-' }}</td>
                        <td class="border-r border-black p-1">{{ pfmea.approved_by || '-' }}</td>
                        <td class="border-r border-black p-1">{{ pfmea.reviewed_by || '-' }}</td>
                        <td class="p-1">{{ pfmea.prepared_by || '-' }}</td>
                      </tr>
                      <tr v-if="!pfmea.revisions.length" class="bg-yellow-100">
                        <td colspan="6" class="p-2 text-center text-gray-500 italic">No revision records</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- 3. CORE TEAM ROW -->
            <div class="p-1 bg-yellow-300 font-medium border-b-2 border-black">
              <span class="font-bold">Core Team:</span>
              <span class="ml-2">{{ pfmea.core_team }}</span>
            </div>

            <!-- 4. READ-ONLY WORKSHEET TABLE -->
            <table class="w-full border-collapse text-[11px]">
              <thead>
                <tr class="text-center font-bold border-b border-black bg-white">
                  <th rowspan="2" class="border-r border-black p-1 min-w-[120px] align-middle">Process Step</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[70px] align-middle">Kakotora YC</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[130px] align-middle">Requirements</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[130px] align-middle">Potential Failure Mode</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[140px] align-middle">Potential Effect(s) of Failure</th>
                  <th rowspan="2" class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Severity</span>
                  </th>
                  <th rowspan="2" class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Classification</span>
                  </th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[140px] align-middle">Potential Cause(s) of Failure</th>
                  <th rowspan="2" class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Occurrence</span>
                  </th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[120px] align-middle">Controls Prevention</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[120px] align-middle">Control Detection</th>
                  <th rowspan="2" class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Detection</span>
                  </th>
                  <th rowspan="2" class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">RPN</span>
                  </th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[130px] align-middle">Recommended Action(s)</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[100px] align-middle">Responsibility</th>
                  <th rowspan="2" class="border-r border-black p-1 min-w-[90px] align-middle">Target Completion Date</th>
                  <th colspan="5" class="border-b border-black p-1 bg-gray-50">Result</th>
                </tr>

                <tr class="text-center font-bold border-b border-black bg-white">
                  <th class="border-r border-black p-1 min-w-[110px] align-middle">Action Taken Completion Date</th>
                  <th class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Severity</span>
                  </th>
                  <th class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Occurrence</span>
                  </th>
                  <th class="border-r border-black p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">Detection</span>
                  </th>
                  <th class="p-1 w-8 align-middle">
                    <span class="[writing-mode:vertical-lr] rotate-180 inline-block my-2">RPN</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(item, index) in groupedPfmeaItems" 
                  :key="index" 
                  class="border-b border-black hover:bg-gray-50"
                >
                  <!-- 1. Process Step dengan Rowspan -->
                  <td 
                    v-if="item.rowspanStep > 0" 
                    :rowspan="item.rowspanStep" 
                    class="border-r border-black p-1.5 font-medium align-middle bg-white"
                  >
                    {{ item.process_step || '-' }}
                  </td>

                  <td class="border-r border-black p-1.5">{{ item.kakotora_yc || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.requirements || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.potential_failure_mode || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.potential_effects || '-' }}</td>
                  <td class="border-r border-black p-1 text-center font-semibold">{{ item.severity || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.classification || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.potential_causes || '-' }}</td>
                  <td class="border-r border-black p-1 text-center font-semibold">{{ item.occurrence || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.controls_prevention || '-' }}</td>
                  
                  <!-- 2. Control Detection dengan Rowspan -->
                  <td 
                    v-if="item.rowspanControl > 0" 
                    :rowspan="item.rowspanControl" 
                    class="border-r border-black p-1.5 align-middle bg-white"
                  >
                    {{ item.control_detection || '-' }}
                  </td>

                  <td class="border-r border-black p-1 text-center font-semibold">{{ item.detection || '-' }}</td>
                  <td class="border-r border-black p-1 text-center bg-yellow-100 font-bold text-red-600">
                    {{ item.rpn || (item.severity && item.occurrence && item.detection ? item.severity * item.occurrence * item.detection : '-') }}
                  </td>
                  <td class="border-r border-black p-1.5">{{ item.recommended_actions || '-' }}</td>
                  <td class="border-r border-black p-1.5">{{ item.responsibility || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.target_completion_date || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.action_taken_date || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.result_severity || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.result_occurrence || '-' }}</td>
                  <td class="border-r border-black p-1 text-center">{{ item.result_detection || '-' }}</td>
                  <td class="p-1 text-center bg-yellow-100 font-bold text-red-600">
                    {{ item.result_rpn || (item.result_severity && item.result_occurrence && item.result_detection ? item.result_severity * item.result_occurrence * item.result_detection : '-') }}
                  </td>
                </tr>

                <tr v-if="!pfmea.items.length">
                  <td colspan="21" class="p-4 text-center text-gray-500 italic bg-gray-50">
                    {{ form.material_id ? 'Click "Search" button above to display PFMEA data.' : 'Please select Project & Material above, then click Search.' }}
                  </td>
                </tr>
              </tbody>
            </table>

          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>