<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import StatCard from '@/Components/StatCard.vue';

defineProps({ user: Object });
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        Panel Operativo
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Ingeniero de Sitio asignado a: <span class="font-bold text-green-700">{{ user.current_team?.name }}</span>
                    </p>
                </div>
                <span class="px-3 py-1 text-xs font-bold text-green-800 bg-green-100 rounded-full">
                    NIVEL 3
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <StatCard title="Tickets Asignados" value="0" color="orange">
                <template #icon><AppIcon name="clipboard" class="w-6 h-6" /></template>
            </StatCard>
            <StatCard title="Mi Región" value="Sin Asignar" color="blue">
                <template #icon><AppIcon name="map" class="w-6 h-6" /></template>
            </StatCard>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <AppIcon name="clipboard" class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        No tienes actividades pendientes para el día de hoy.
                        Mantente atento a nuevas asignaciones de tu coordinador.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>