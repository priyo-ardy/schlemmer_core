<script setup>
// resources/js/Components/History/HistoryListModal.vue
import { formatFieldName } from '@/Utils/historyLog';

defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    logs: { type: Array, default: () => [] },
    title: { type: String, default: 'Revision History' },
    emptyMessage: { type: String, default: 'This record has no recorded changes.' },
    showDetailLink: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'view-detail']);
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
            v-show="show"
            @click.self="emit('close')"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white w-full max-w-5xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ title }}
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

                    <div v-else-if="logs.length === 0" class="text-center py-12 border border-dashed border-slate-200 bg-white p-8">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">No History Records</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ emptyMessage }}</p>
                    </div>

                    <div v-else class="relative border-l-2 border-slate-200 ml-3 space-y-8 pb-4">
                        <div v-for="(log, index) in logs" :key="log.id" class="relative pl-6 animate-fade-in">
                            <div
                                :class="{
                                    'bg-emerald-500 border-emerald-100 ring-4 ring-emerald-50': log.event_name === 'create',
                                    'bg-blue-600 border-blue-100 ring-4 ring-blue-50': (log.event_name === 'update' || log.event_name === 'restore') && index === 0,
                                    'bg-slate-400 border-white': log.event_name === 'update' && index !== 0,
                                    'bg-orange-500 border-orange-100 ring-4 ring-orange-50': log.event_name === 'restore' && index !== 0,
                                    'bg-rose-500 border-rose-100 ring-4 ring-rose-50': log.event_name === 'delete'
                                }"
                                class="absolute w-3.5 h-3.5 -left-[8px] top-1 border-2 shadow-sm transition-all"
                            ></div>

                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2 gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 border bg-white shadow-sm text-slate-700">
                                        Rev. {{ log.revision }}
                                    </span>
                                    <span
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': log.event_name === 'create',
                                            'bg-blue-50 text-blue-700 border-blue-200': log.event_name === 'update',
                                            'bg-orange-50 text-orange-700 border-orange-200': log.event_name === 'restore',
                                            'bg-rose-50 text-rose-700 border-rose-200': log.event_name === 'delete'
                                        }"
                                        class="text-[9px] font-bold uppercase px-1.5 py-0.5 border -sm tracking-wide"
                                    >
                                        {{ log.event_name }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ log.creator?.name || 'System Auto' }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="bg-white p-4 border border-slate-200 shadow-sm space-y-3">
                                <div>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Change Reason</span>
                                    <p class="text-xs font-bold text-slate-800 leading-relaxed whitespace-pre-line">
                                        {{ log.change_reason || 'No description provided.' }}
                                    </p>
                                </div>

                                <div v-if="log.detailChanges.length > 0" class="pt-2 border-t border-slate-100 overflow-x-auto">
                                    <table class="min-w-full text-[11px] font-mono">
                                        <thead>
                                            <tr class="text-slate-400 border-b border-slate-100 text-left font-bold uppercase tracking-wider text-[10px]">
                                                <th class="pb-1.5 w-1/4">Field Data</th>
                                                <th class="pb-1.5 w-3/8 text-rose-600" v-if="!['create', 'restore'].includes(log.event_name)">Data Before</th>
                                                <th class="pb-1.5 w-3/8 text-emerald-600" v-if="log.event_name !== 'delete'">Data After</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 text-slate-600 font-medium">
                                            <tr v-for="item in log.detailChanges" :key="item.field" class="hover:bg-slate-50/50">
                                                <td class="py-1.5 font-bold text-slate-500">{{ formatFieldName(item.field) }}</td>

                                                <td class="py-1.5 pr-2" v-if="!['create', 'restore'].includes(log.event_name)">
                                                    <span class="bg-rose-50 text-rose-700 px-1.5 py-0.5 -sm line-through block w-fit max-w-xs truncate" :title="String(item.before)">
                                                        {{ item.before === null || item.before === '' ? '-' : item.before }}
                                                    </span>
                                                </td>

                                                <td class="py-1.5" v-if="log.event_name !== 'delete'">
                                                    <span class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 -sm font-bold block w-fit max-w-xs truncate" :title="String(item.after)">
                                                        {{ item.after === null || item.after === '' ? '-' : item.after }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-if="showDetailLink && log.detailChanges.length > 0">
                                    <span
                                        @click="emit('view-detail', log.id)"
                                        class="cursor-pointer text-[10px] font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        Show Details ...
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>