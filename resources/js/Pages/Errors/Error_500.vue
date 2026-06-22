<script setup>
import { Link, Head } from '@inertiajs/vue3';
const reloadPage = () => window.location.reload();
</script>

<template>
    <Head title=".:: 500 Internal Server Error ::." />
    <div class="min-h-screen flex items-center justify-center bg-slate-950 p-6">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl">
            <div class="text-rose-500 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-3xl font-black text-white">System Error</h1>
            <p class="text-slate-400 mt-4 mb-8">Oops, there's an error with our internal server. Please try again in a few moments or contact the administrator.</p>
      
            <div class="flex flex-col gap-3">
                <button @click="reloadPage" class="w-full py-3 bg-white text-slate-900 font-bold rounded-xl hover:bg-slate-200 transition inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 00-15.5-2M4 13a8.1 8.1 0 0015.5 2M4 4v5h5" />
                    </svg>
                    Reload Page
                </button>
                <Link href="/dashboard" class="w-full py-3 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                        <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                    </svg>
                    Back to Safety
                </Link>
            </div>
        </div>
    </div>
</template>

