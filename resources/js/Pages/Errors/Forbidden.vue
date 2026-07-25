<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-slate-900 text-slate-100 font-sans">
    <div class="text-center p-8 max-w-md bg-slate-800 border border-slate-700 shadow-2xl rounded-xl">
      <!-- Icon / Badge 403 -->
      <div class="inline-flex items-center justify-center w-16 h-16 bg-rose-500/10 text-rose-500 rounded-full text-2xl font-black mb-4">
        403
      </div>

      <h1 class="text-xl font-bold text-white mb-2">Akses Ditolak</h1>
      <p class="text-sm text-slate-400 mb-6 leading-relaxed">
        Maaf, Anda tidak memiliki hak akses atau permission yang cukup untuk melihat halaman ini.
      </p>

      <!-- Tombol Kembali menggunakan Inertia Link agar SPA tetap mulus -->
      <Link
        href="/dashboard"
        class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-500 transition shadow-sm"
      >
        Kembali ke Dashboard
      </Link>
    </div>
  </div>
</template>
