<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ user: Object });
</script>

<template>
    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-gray-900">
                Hola, Coordinador {{ user.name }} 👋
            </h1>
            <span class="px-3 py-1 text-xs font-bold text-indigo-700 bg-indigo-100 rounded-full">
                NIVEL 2
            </span>
        </div>

        <p class="mt-4 text-gray-500">
            Gestionando la compañía: <strong>{{ user.current_team.name }}</strong>.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <h3 class="font-bold text-blue-800">Mis Regiones</h3>
                <p class="text-sm text-blue-600 mb-3">Administra las sucursales de tu territorio.</p>
                <button class="text-sm font-bold text-blue-700 underline">Ir a Regiones →</button>
            </div>
        </div>
    </div>
</template>