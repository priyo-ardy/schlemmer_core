<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; // Import layout master

// Mendefinisikan persistent layout untuk page ini
defineOptions({ layout: AuthenticatedLayout });

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Dashboard" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div
            class="lg:col-span-2 bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-6 text-white relative overflow-hidden shadow-xl border border-blue-500/20"
        >
            <div class="flex justify-between items-start">
                <div>
                    <p
                        class="text-xs font-semibold text-blue-200 tracking-wider uppercase"
                    >
                        Total Portfolio Value
                    </p>
                    <h3 class="text-3xl font-extrabold mt-1 tracking-tight">
                        Rp 1.420.500.000
                    </h3>
                </div>
                <span
                    class="px-2.5 py-1 text-[10px] font-bold bg-blue-500/30 text-white border border-blue-400/30 rounded-xl uppercase tracking-wider"
                    >Live</span
                >
            </div>
            <div class="mt-10 flex gap-8">
                <div>
                    <p class="text-[10px] font-medium text-blue-200 uppercase">
                        Monthly Return
                    </p>
                    <p class="text-sm font-bold text-emerald-300">+14.2%</p>
                </div>
            </div>
        </div>

        <div
            class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex flex-col justify-between"
        >
            <div class="flex justify-between items-center">
                <span
                    class="text-xs font-bold text-slate-400 uppercase tracking-wider"
                    >Cashflow Target</span
                >
                <span
                    class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg"
                    >82%</span
                >
            </div>
            <div class="my-4">
                <p class="text-2xl font-black text-slate-900">Rp 45.800.000</p>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
                <div
                    class="bg-blue-600 h-2 rounded-full"
                    style="width: 82%"
                ></div>
            </div>
        </div>
    </div>

    <div
        class="mt-8 bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden"
    >
        <div
            class="px-6 py-5 border-b border-slate-100 flex justify-between items-center"
        >
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">
                    Recent Financial Transactions
                </h3>
            </div>
        </div>
        <div class="p-6 text-sm text-slate-500 text-center">
            Data transaksi siap dimuat secara SPA dan modular...
        </div>
    </div>
</template>
