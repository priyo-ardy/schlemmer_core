<script setup>
import { ref, watch } from "vue";
import { router, Link, Head } from "@inertiajs/vue3";
import { debounce } from "lodash";
import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";
defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const showPurgeModal = ref(false);
const start = ref("");
const end = ref("");

watch(
    search,
    debounce((val) => {
        router.get(
            route("activity-log.index"),
            { search: val || null },
            { preserveState: true, replace: true }
        );
    }, 300),
);

const performPurge = () => {
    if (!start.value || !end.value) return;

    router.post(
        route("activity-log.purge"),
        {
            start: start.value,
            end: end.value,
        },
        {
            onSuccess: () => {
                showPurgeModal.value = false;
                start.value = "";
                end.value = "";
            },
        },
    );
};
</script>

<template>
    <Head title="Activity Logs" />
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex-1">
                    <input
                        v-model="search"
                        placeholder="Search by description/user..."
                        class="w-full border border-slate-200 bg-white rounded px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="flex gap-2">
                    <button
                        @click="showPurgeModal = true"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 text-xs font-bold rounded"
                    >
                        Purge Logs
                    </button>
                </div>
            </div>

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <div class="text-xs font-bold text-slate-800">Activity Logs</div>
                    <div class="text-[11px] text-slate-500">
                        {{ logs?.total ?? 0 }} record(s)
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-slate-600">
                                <th class="px-4 py-2 font-bold w-16">ID</th>
                                <th class="px-4 py-2 font-bold w-16">Log Name</th>
                                <th class="px-4 py-2 font-bold">Description</th>
                                <th class="px-4 py-2 font-bold w-56">Subject</th>
                                <th class="px-4 py-2 font-bold w-36">Created At</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="row in logs?.data ?? []"
                                :key="row.id"
                                class="border-t border-slate-100 hover:bg-slate-50/60"
                            >
                                <td class="px-4 py-2 font-semibold text-slate-700">{{ row.id }}</td>
                                <td class="px-4 py-2 font-semibold text-slate-700">{{ row.log_name }}</td>
                                <td class="px-4 py-2 text-slate-700 break-words">
                                    {{ row.description }}
                                </td>
                                <td class="px-4 py-2 text-slate-600 break-words">
                                    {{ row.subject_type }}
                                </td>
                                <td class="px-4 py-2 text-slate-600 whitespace-nowrap">
                                    {{ row.created_at }}
                                </td>
                            </tr>

                            <tr v-if="(logs?.data ?? []).length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    No logs found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-100">
                    <div
                        v-if="logs?.links?.length"
                        class="mt-0 flex gap-1 flex-wrap"
                    >
                        <template v-for="link in logs.links" :key="(link?.label ?? '') + (link?.url ?? '')">
                            <Link
                                v-if="link && link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="{
                                    'font-bold text-slate-900': link.active,
                                    'text-slate-600 hover:text-slate-900': !link.active,
                                }"
                                class="px-2 py-1 rounded text-[12px] border border-slate-200 bg-white"
                            />
                            <span
                                v-else
                                class="px-2 py-1 rounded text-[12px] border border-slate-200 bg-slate-50 text-slate-400 select-none"
                                v-html="link?.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showPurgeModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4"
        >
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                <h3 class="font-black text-lg mb-4 text-slate-900">
                    Purge Activity Logs
                </h3>

                <div class="space-y-3">
                    <div>
                        <div class="text-[11px] font-bold text-slate-700 mb-1">Start date</div>
                        <input type="date" v-model="start" class="w-full border p-2 text-xs rounded" />
                    </div>

                    <div>
                        <div class="text-[11px] font-bold text-slate-700 mb-1">End date</div>
                        <input type="date" v-model="end" class="w-full border p-2 text-xs rounded" />
                    </div>
                </div>

                <div class="mt-5 flex gap-2">
                    <button
                        @click="showPurgeModal = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-800 px-4 py-2 text-xs font-bold rounded border border-slate-200"
                    >
                        Cancel
                    </button>

                    <button
                        @click="performPurge"
                        class="flex-1 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 text-xs font-bold rounded"
                    >
                        Delete Logs in Range
                    </button>
                </div>
            </div>
        </div>
</template>
