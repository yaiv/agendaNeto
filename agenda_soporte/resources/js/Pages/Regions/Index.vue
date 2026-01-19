<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    regions: Array,
});
</script>

<template>
    <AppLayout title="Mis Regiones">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Regiones Operativas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="regions.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 text-center text-gray-500">
                    <p class="text-lg">No tienes regiones asignadas.</p>
                    <p class="text-sm">Contacta a tu Coordinador si necesitas acceso.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="region in regions" :key="region.id" class="bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition border-l-4 border-indigo-500">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-900">{{ region.name }}</h3>
                                <span v-if="region.code" class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    {{ region.code }}
                                </span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <span class="font-bold">{{ region.branches_count }}</span> Sucursales registradas
                            </p>

                            <div class="border-t pt-4 mt-2">
                                <button class="text-indigo-600 font-bold hover:text-indigo-900 text-sm flex items-center">
                                    Gestionar Sucursales 
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>