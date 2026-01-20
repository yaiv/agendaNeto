<script setup>
import { computed } from 'vue'; // Importamos computed
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatCard from '@/Components/StatCard.vue'; // 👈 Importamos StatCard
import AppIcon from '@/Components/AppIcon.vue'; // 👈 Importamos Iconos

const props = defineProps({
    companies: Array,
});

// --- CÁLCULOS ESTADÍSTICOS ---
const totalCompanies = computed(() => props.companies.length);

const totalRegions = computed(() => {
    return props.companies.reduce((sum, company) => sum + (company.regions_count || 0), 0);
});

// Contamos cuántos usuarios distintos son dueños de compañías
const activeOwners = computed(() => {
    const uniqueOwners = new Set(props.companies.map(c => c.user_id));
    return uniqueOwners.size;
});

// Función simple para formatear fechas
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-MX', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
};
</script>

<template>
    <Head title="Gestión de Compañías" />

    <AppLayout title="Directorio Corporativo">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Compañías y Estructura
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Administración de entidades legales y equipos.</p>
                </div>
                
                <Link :href="route('admin.companies.create')" class="w-full sm:w-auto">
                    <PrimaryButton class="w-full sm:w-auto justify-center">
                        + Nueva Compañía
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8"> <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <StatCard title="Compañías Activas" :value="totalCompanies" color="blue">
                        <template #icon>
                            <AppIcon name="office-building" class="w-6 h-6" />
                        </template>
                    </StatCard>

                    <StatCard title="Total Regiones" :value="totalRegions" color="orange">
                        <template #icon>
                            <AppIcon name="map" class="w-6 h-6" />
                        </template>
                    </StatCard>

                    <StatCard title="Coordinadores Asignados" :value="activeOwners" color="green">
                        <template #icon>
                            <AppIcon name="users" class="w-6 h-6" />
                        </template>
                    </StatCard>
                </div>

                <div v-if="companies.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-10 text-center text-gray-500 border border-gray-200 border-dashed">
                    <div class="mx-auto h-12 w-12 text-gray-300 mb-3">
                        <AppIcon name="office-building" class="h-12 w-12 mx-auto" />
                    </div>
                    <p class="text-lg font-medium text-gray-900">No hay compañías registradas</p>
                    <p class="text-sm mt-1">Comienza creando la primera para definir la estructura.</p>
                </div>

                <div v-else>
                    <div class="hidden md:block bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compañía</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estructura</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinador (Owner)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Gestionar</span></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-indigo-100 text-indigo-700 rounded-full font-bold shadow-sm">
                                                    {{ company.name.charAt(0) }}
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-gray-900">{{ company.name }}</div>
                                                    <div class="text-xs text-gray-500">Reg: {{ formatDate(company.created_at) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="company.regions_count > 0">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ company.regions_count }} Regiones
                                                </span>
                                            </div>
                                            <div v-else class="text-red-500 text-xs font-bold flex items-center">
                                                <AppIcon name="exclamation-circle" class="w-4 h-4 mr-1" />
                                                Sin Estructura
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full overflow-hidden bg-gray-200 border border-gray-300">
                                                    <img v-if="company.owner.profile_photo_url" :src="company.owner.profile_photo_url" :alt="company.owner.name">
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm text-gray-900 font-medium">{{ company.owner.name }}</div>
                                                    <div class="text-xs text-gray-500">{{ company.owner.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="!company.personal_team" class="text-green-600 font-medium bg-green-50 px-2 py-0.5 rounded border border-green-200 text-xs">Corporativo</span>
                                            <span v-else class="text-gray-400 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 text-xs">Personal</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('admin.companies.show', company.id)" class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline">
                                                Gestionar &rarr;
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="md:hidden space-y-4">
                        <div v-for="company in companies" :key="company.id" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 flex items-center justify-center bg-indigo-600 text-white rounded-md font-bold text-sm">
                                        {{ company.name.charAt(0) }}
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-bold text-gray-900">{{ company.name }}</h3>
                                    </div>
                                </div>
                                <span v-if="!company.personal_team" class="text-[10px] uppercase font-bold text-green-700 bg-green-100 px-2 py-1 rounded-full">
                                    Corp
                                </span>
                            </div>

                            <div class="p-4 space-y-3">
                                <div class="flex items-center text-sm">
                                    <div class="h-6 w-6 rounded-full overflow-hidden bg-gray-200 mr-2">
                                        <img v-if="company.owner.profile_photo_url" :src="company.owner.profile_photo_url" :alt="company.owner.name">
                                    </div>
                                    <span class="text-gray-600 truncate">{{ company.owner.name }}</span>
                                </div>

                                <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-3">
                                    <span class="text-gray-500">Regiones:</span>
                                    <span v-if="company.regions_count > 0" class="font-bold text-indigo-700 bg-indigo-50 px-2 rounded">
                                        {{ company.regions_count }}
                                    </span>
                                    <span v-else class="text-red-500 text-xs font-bold">Sin asignar</span>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 border-t border-gray-100">
                                <Link :href="route('admin.companies.show', company.id)" class="block w-full text-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Gestionar Estructura
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>