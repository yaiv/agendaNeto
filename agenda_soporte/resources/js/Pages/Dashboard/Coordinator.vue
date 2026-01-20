<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue'; // Asegúrate de tener este componente
import StatCard from '@/Components/StatCard.vue'; // Y este también

defineProps({ user: Object });
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-[#00408F]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        Hola, {{ user.name }} 👋
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Estás gestionando la unidad de negocio: <span class="font-bold text-[#00408F]">{{ user.current_team?.name }}</span>
                    </p>
                </div>
                <span class="px-3 py-1 text-xs font-bold text-white bg-[#00408F] rounded-full shadow-sm">
                    NIVEL 2
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <StatCard title="Ingenieros Activos" value="12" color="blue">
                <template #icon><AppIcon name="users" class="w-6 h-6" /></template>
            </StatCard>
            <StatCard title="Sucursales" value="8" color="orange">
                <template #icon><AppIcon name="building" class="w-6 h-6" /></template>
            </StatCard>
            <StatCard title="Regiones" value="2" color="green">
                <template #icon><AppIcon name="map" class="w-6 h-6" /></template>
            </StatCard>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Acciones de Gestión</h3>
            <div class="flex gap-4">
                <Link :href="route('engineers.index')" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                    Ver Ingenieros
                </Link>
                <Link :href="route('branches.index')" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                    Gestionar Sucursales
                </Link>
            </div>
        </div>
    </div>
</template>