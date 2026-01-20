<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';

// Helper debounce personalizado
import { debounce } from '@/utils/debounce';

import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import StatCard from '@/Components/StatCard.vue';

const props = defineProps({
    regions: Array,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// --- ESTADO DEL BUSCADOR ---
const search = ref(props.filters?.search || '');

// --- BÚSQUEDA CON DEBOUNCE ---
watch(
    search,
    debounce((value) => {
        router.get(
            route('regions.index'),
            { search: value },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 300)
);

// --- CÁLCULOS PARA STATCARDS ---
const totalRegions = computed(() => props.regions.length);
const totalBranches = computed(() =>
    props.regions.reduce((sum, region) => sum + (region.branches_count ?? 0), 0)
);

// --- LÓGICA DE PERMISOS ---
const isGlobalView = computed(() => {
    return (
        user.value.is_global_admin ||
        ['gerente', 'supervisor'].includes(user.value.global_role)
    );
});

// ✅ NUEVA COMPUTADA: ¿QUIÉN PUEDE CREAR REGIONES?
const canCreateRegion = computed(() => {
    return (
        isGlobalView.value ||                         // Admin global / gerente / supervisor
        user.value.global_role === 'coordinador' ||   // Coordinador explícito
        user.value.id === user.value.current_team?.user_id // Dueño del team (Jetstream)
    );
});
</script>


<template>
    <AppLayout title="Directorio de Regiones">
        
        <div class="px-4 lg:px-8 py-6 space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#00408F]">
                        {{ isGlobalView ? 'Directorio Global de Regiones' : 'Mis Regiones Asignadas' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ isGlobalView 
                            ? 'Consulta centralizada de todas las divisiones territoriales.' 
                            : 'Gestión operativa de tus territorios asignados.' 
                        }}
                    </p>
                </div>
                
                <div v-if="canCreateRegion">
                    <Link :href="route('regions.create')" class="inline-flex items-center justify-center px-4 py-2 bg-[#00408F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#00337a] transition shadow-sm">
                        <AppIcon name="plus" class="w-4 h-4 mr-2" />
                        Nueva Región
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <StatCard title="Total de Regiones" :value="totalRegions" color="blue">
                    <template #icon>
                        <AppIcon name="map" class="w-6 h-6" />
                    </template>
                </StatCard>

                <StatCard title="Sucursales Cubiertas" :value="totalBranches" color="orange">
                    <template #icon>
                        <AppIcon name="building" class="w-6 h-6" />
                    </template>
                </StatCard>

                <StatCard title="Estado del Sistema" value="Activo" color="green">
                    <template #icon>
                        <AppIcon name="check-circle" class="w-6 h-6" />
                    </template>
                </StatCard>
            </div>

            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                <div class="relative w-full md:w-1/2 lg:w-1/3">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <AppIcon name="search" class="h-5 w-5 text-gray-400" />
                    </div>
                    <input 
                        v-model="search"
                        type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-[#00408F] focus:border-[#00408F] sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Buscar por nombre, compañía..."
                    />
                </div>
            </div>

            <div v-if="regions.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200 border-dashed">
                <AppIcon name="map" class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron regiones</h3>
                <p class="mt-1 text-sm text-gray-500">Intenta ajustar los filtros de búsqueda.</p>
            </div>

            <div v-else>
                <div class="hidden md:block bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Región
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Compañía
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sucursales
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="region in regions" :key="region.id" class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-50 rounded-full flex items-center justify-center text-[#00408F]">
                                            <AppIcon name="map" class="h-4 w-4" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ region.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ region.team?.name || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <span class="font-bold">{{ region.branches_count }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('regions.branches.index', region.id)" class="text-[#00408F] hover:text-[#002a5e] mr-3 font-semibold">
                                        Ver Sucursales
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <Link v-if="isGlobalView" :href="route('regions.edit', region.id)" class="text-gray-500 hover:text-gray-700 ml-3">
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="region in regions" :key="region.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#00408F]"></div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-gray-900">{{ region.name }}</h3>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                {{ region.branches_count }} Suc.
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">{{ region.team?.name || 'Compañía N/A' }}</p>
                        
                        <div class="flex justify-end items-center space-x-3 pt-2 border-t border-gray-100">
                            <Link :href="route('regions.branches.index', region.id)" class="text-sm font-medium text-[#00408F]">
                                Explorar &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>