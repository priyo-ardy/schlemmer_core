<script setup>
import { Link, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title=".:: 404 Not Found ::." />
    <div class="min-h-screen flex items-center justify-center bg-slate-900 text-white">
        <div class="text-center">
            <h1 class="text-9xl font-black text-blue-500">404</h1>
            <p class="text-2xl font-bold mt-4">Oops! Page Not Found</p>
            <p class="text-slate-400 mt-2 mb-8">Sorry, the request or page you are looking for could not be found</p>
            <Link href="/dashboard" class="px-6 py-3 bg-blue-600 rounded-xl font-bold hover:bg-blue-700 transition inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                    <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                </svg>
                Back to Dashboard
            </Link>
        </div>
    </div>
</template>