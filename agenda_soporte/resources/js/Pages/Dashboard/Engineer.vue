<script setup>
defineProps({ user: Object });
</script>

<template>
    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-gray-900">
                Panel Operativo
            </h1>
            <span class="px-3 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-full">
                NIVEL 3
            </span>
        </div>

        <p class="mt-4 text-gray-500">
            Ingeniero: <strong>{{ user.name }}</strong><br>
            Base: <strong>{{ user.current_team.name }}</strong>
        </p>

        <div class="mt-6 border-l-4 border-yellow-400 bg-yellow-50 p-4">
            <p class="text-sm text-yellow-700">
                No tienes tareas asignadas para hoy.
            </p>
        </div>
    </div>
</template>