<script setup>
// resources/js/Components/History/HistoryDetailModal.vue
import { formatFieldName, groupChangedDetailsByRow } from '@/Utils/historyLog';

defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    logs: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="show"
            @click.self="emit('close')"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white w-full max-w-5xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        History Details
                        <span v-if="logs.length" class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                            Rev. {{ logs[0].revision }}
                        </span>
                    </h3>
                    <button @click="emit('close')" class="text-slate-400 hover:text-rose-600 p-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto flex-1 px-6 py-6 bg-slate-50/60 divide-y divide-slate-200/60">
                    <div v-if="loading" class="flex flex-col items-center justify-center py-12 gap-3">
                        <div class="animate-spin h-7 w-7 border-b-2 border-blue-600"></div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loading system logs...</span>
                    </div>

                    <div v-else class="relative border-l-2 border-slate-200 ml-3 space-y-8 pb-4">
                        <div v-for="log in logs" :key="log.id" class="relative pl-6 animate-fade-in">
                            <template v-if="Object.keys(groupChangedDetailsByRow(log)).length">
                                <div
                                    v-for="(fields, row) in groupChangedDetailsByRow(log)"
                                    :key="row"
                                    class="mb-6 rounded border border-slate-200 bg-white"
                                >
                                    <div class="border-b border-slate-200 bg-slate-100 px-4 py-2">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-slate-700">
                                                    Detail Order {{ row }}
                                                </div>
                                                <div class="mt-1 text-[10px] text-slate-500">
                                                    Revision {{ log.revision }}
                                                    •
                                                    {{ log.creator?.name }}
                                                    •
                                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                                </div>
                                            </div>
                                            <span
                                                class="rounded px-2 py-0.5 text-[10px] font-bold"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': log.event_name === 'update',
                                                    'bg-emerald-100 text-emerald-700': log.event_name === 'create',
                                                    'bg-rose-100 text-rose-700': log.event_name === 'delete'
                                                }"
                                            >
                                                {{ log.event_name.toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>

                                    <table class="min-w-full text-[11px] font-mono">
                                        <thead>
                                            <tr class="border-b border-slate-100 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                <th class="w-1/4 px-3 py-2">Field Data</th>
                                                <th v-if="log.event_name !== 'create'" class="w-3/8 px-3 py-2 text-rose-600">Data Before</th>
                                                <th v-if="log.event_name !== 'delete'" class="w-3/8 px-3 py-2 text-emerald-600">Data After</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="item in fields" :key="item.field">
                                                <td class="px-3 py-2 font-bold text-slate-600">
                                                    {{ formatFieldName(item.field) }}
                                                </td>
                                                <td v-if="log.event_name !== 'create'" class="px-3 py-2">
                                                    {{ item.before ?? '-' }}
                                                </td>
                                                <td v-if="log.event_name !== 'delete'" class="px-3 py-2">
                                                    {{ item.after ?? '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <div v-else class="rounded border border-dashed border-slate-300 bg-white px-4 py-6 text-center">
                                <p class="text-xs font-semibold text-slate-500">No change found</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>