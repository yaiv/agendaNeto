<script setup>
// 1. TUS IMPORTACIONES EXACTAS
import { ref, computed, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import StatCard from '@/Components/StatCard.vue';
import { debounce } from '@/utils/debounce'; // 👈 Tu utilidad personalizada

// 2. PROPS (Recibimos filters del controlador)
const props = defineProps({
    engineers: Object, // Es Objeto porque viene paginado
    filters: Object,   // Filtros para mantener la búsqueda al recargar
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// 3. LÓGICA DE BÚSQUEDA
const search = ref(props.filters.search || '');

const clearSearch = () => {
    search.value = '';
};

// Usamos tu debounce personalizado
watch(search, debounce((value) => {
    router.get(
        route('engineers.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

// 4. COMPUTADAS PARA LA VISTA
// Acceso seguro a la data paginada
const engineersList = computed(() => props.engineers.data || []);

const stats = computed(() => {
    // Calculamos sobre la página actual o usas el total global si viene del backend
    const list = engineersList.value;
    const total = props.engineers.total || 0; 
    const active = list.filter(e => e.status === 'active').length;
    const inactive = list.filter(e => e.status === 'inactive').length;
    
    return { total, active, inactive };
});
</script>

<template>
    <AppLayout title="Gestión de Ingenieros">
        
        <div class="mb-6 flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between px-4 lg:px-0">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-[#00408F]">
                    Ingenieros de Sitio
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    Personal técnico y asignación de zonas operativas
                </p>
            </div>

            <Link 
                :href="route('engineers.create')"
                class="inline-flex items-center px-4 py-2 bg-[#00408F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#FF5501] transition ease-in-out duration-150"
            >
                <AppIcon name="plus" class="h-4 w-4 mr-2" />
                Nuevo Ingeniero
            </Link>
        </div>

        <div class="mb-6 px-4 lg:px-0">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <AppIcon name="search" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, zona, ID ECO, región o compañía..."
                    class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-[#00408F] focus:border-[#00408F] sm:text-sm shadow-sm"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                >
                    <AppIcon name="x" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 px-4 lg:px-0">
            <StatCard title="Total de Ingenieros" :value="stats.total" color="blue">
                <template #icon><AppIcon name="users" class="h-6 w-6" /></template>
            </StatCard>

            <StatCard title="Activos (Vista)" :value="stats.active" color="green">
                <template #icon><AppIcon name="user-check" class="h-6 w-6" /></template>
            </StatCard>

            <StatCard title="Inactivos (Vista)" :value="stats.inactive" color="orange">
                <template #icon><AppIcon name="user-x" class="h-6 w-6" /></template>
            </StatCard>
        </div>

        <div v-if="engineersList.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 sm:p-12 text-center mx-4 lg:mx-0">
            <div class="bg-gray-50 rounded-full h-12 w-12 sm:h-16 sm:w-16 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="users" class="h-6 w-6 sm:h-8 sm:w-8 text-gray-400" />
            </div>
            <h3 class="text-base sm:text-lg font-medium text-gray-900">
                Sin resultados
            </h3>
            <p class="mt-1 text-gray-500 text-xs sm:text-sm max-w-md mx-auto">
                {{ search ? 'No se encontraron ingenieros con ese criterio.' : 'Comienza agregando tu primer ingeniero.' }}
            </p>
            <div class="mt-6" v-if="!search">
                <Link :href="route('engineers.create')" class="text-[#00408F] font-bold hover:underline">
                    Agregar Primer Ingeniero
                </Link>
            </div>
             <div class="mt-6" v-else>
                 <button @click="clearSearch" class="text-[#FF5501] font-bold hover:underline">
                    Limpiar búsqueda
                </button>
            </div>
        </div>

        <div v-else class="hidden md:block bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingeniero</th>
                            <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Región Base</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apoyo</th>
                            <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="ing in engineersList" :key="ing.id" class="hover:bg-blue-50/30 transition">
                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 mr-3">
                                        <div class="h-10 w-10 rounded-full bg-[#00408F] text-white flex items-center justify-center font-bold text-sm">
                                            {{ ing.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ ing.name }}</div>
                                        <div class="text-xs text-gray-500">{{ ing.email }}</div>
                                        <div v-if="ing.team_name" class="text-[10px] text-[#00408F] font-semibold mt-0.5">
                                            {{ ing.team_name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 text-center">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="ing.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ ing.status === 'active' ? 'Activo' : 'Baja' }}
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                <div class="text-xs text-gray-500 font-mono">{{ ing.code }}</div>
                                <div class="text-sm text-gray-900">{{ ing.phone }}</div>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-50 text-[#00408F]">
                                    {{ ing.primary_region }}
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                <div class="text-xs text-gray-500 max-w-[150px] truncate">
                                    {{ ing.support_regions || '-' }}
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 text-right">
                                <Link :href="route('engineers.edit', ing.id)" class="text-[#00408F] hover:text-[#FF5501] font-semibold text-sm">
                                    Editar
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="engineers.links && engineers.links.length > 3" class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                 <Link v-if="engineers.prev_page_url" :href="engineers.prev_page_url" class="text-xs font-bold text-[#00408F]">Anterior</Link>
                 <Link v-if="engineers.next_page_url" :href="engineers.next_page_url" class="text-xs font-bold text-[#00408F]">Siguiente</Link>
            </div>
        </div>

        <div class="md:hidden space-y-4 px-4">
            <div v-for="ing in engineersList" :key="ing.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-gray-900">{{ ing.name }}</h3>
                        <p class="text-xs text-gray-500">{{ ing.primary_region }}</p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] rounded-full" :class="ing.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ ing.status }}
                    </span>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                    <Link :href="route('engineers.edit', ing.id)" class="text-xs font-bold text-[#00408F]">EDITAR</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>