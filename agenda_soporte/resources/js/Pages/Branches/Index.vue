<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import StatCard from '@/Components/StatCard.vue';
import { debounce } from '@/utils/debounce';

const props = defineProps({
    region: Object, // Puede ser null si es vista global
    branches: Object,
    filters: Object,
    isGlobal: Boolean,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Estado del buscador
const search = ref(props.filters?.search || ''); // 👈 Agregado ?. para evitar null

// Función de búsqueda con debounce
const performSearch = debounce((value) => {
    const routeName = props.isGlobal ? 'branches.index' : 'regions.branches.index';
    const routeParams = props.isGlobal ? {} : { region: props.region?.id }; // 👈 Agregado ?.
    
    router.get(
        route(routeName, routeParams),
        { search: value },
        { 
            preserveState: true,
            preserveScroll: true,
        }
    );
}, 300);

// Watch para ejecutar búsqueda cuando cambie el input
watch(search, (newValue) => {
    performSearch(newValue);
});

// Función para limpiar búsqueda
const clearSearch = () => {
    search.value = '';
};

// Estadísticas calculadas
const stats = computed(() => {
    const total = props.branches?.total || 0;
    const data = props.branches?.data || [];
    const withEcoId = data.filter(b => b.external_id_eco).length;
    const withZone = data.filter(b => b.zone_name).length;
    
    return {
        total,
        withEcoId,
        withZone,
    };
});

// 🔥 NUEVA FUNCIÓN: Confirmación de eliminación
const deleteBranch = (branch) => {
    if (confirm(`¿Estás seguro de eliminar la tienda "${branch.name}"?`)) {
        router.delete(route('branches.destroy', branch.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message ya viene del controlador
            },
        });
    }
};
</script>

<template>
    <AppLayout :title="isGlobal ? 'Todas las Tiendas' : `Tiendas - ${region?.name || 'Cargando...'}`">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between px-4 lg:px-0">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <Link 
                        v-if="!isGlobal" 
                        :href="route('regions.index')"
                        class="text-gray-400 hover:text-[#00408F] transition"
                    >
                        <AppIcon name="arrow-left" class="h-5 w-5" />
                    </Link>
                    <h2 class="text-xl sm:text-2xl font-bold text-[#00408F]">
                        {{ isGlobal ? 'Directorio Global de Tiendas' : (region?.name || 'Cargando...') }}
                    </h2>
                </div>
                <p class="text-xs sm:text-sm text-gray-500 ml-7 sm:ml-0">
                    {{ isGlobal 
                        ? 'Consulta centralizada de todas las ubicaciones del corporativo' 
                        : 'Gestiona las ubicaciones de esta región' 
                    }}
                </p>
            </div>

            <!-- Botón Agregar -->
            <Link 
                :href="route('branches.create', isGlobal ? {} : { region_id: region?.id })"
                class="inline-flex items-center px-4 py-2 bg-[#00408F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#FF5501] focus:bg-[#FF5501] active:bg-[#FF5501] focus:outline-none focus:ring-2 focus:ring-[#FF5501] focus:ring-offset-2 transition ease-in-out duration-150"
            >
                <AppIcon name="plus" class="h-4 w-4 mr-2" />
                Nueva Tienda
            </Link>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 px-4 lg:px-0">
            <StatCard
                title="Total de Tiendas"
                :value="stats.total"
                color="blue"
            >
                <template #icon>
                    <AppIcon name="building" class="h-6 w-6" />
                </template>
            </StatCard>

            <StatCard
                title="Con ID ECO"
                :value="stats.withEcoId"
                color="orange"
            >
                <template #icon>
                    <AppIcon name="hash" class="h-6 w-6" />
                </template>
            </StatCard>

            <StatCard
                title="Con Zona Asignada"
                :value="stats.withZone"
                color="green"
            >
                <template #icon>
                    <AppIcon name="map-pin" class="h-6 w-6" />
                </template>
            </StatCard>
        </div>

        <!-- Buscador -->
        <div class="mb-6 px-4 lg:px-0">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <AppIcon name="search" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, zona, ID ECO, región o compañía..."
                    class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-[#00408F] focus:border-[#00408F] sm:text-sm"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                >
                    <AppIcon name="x" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="!branches?.data || branches.data.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 sm:p-12 text-center mx-4 lg:mx-0">
            <div class="bg-gray-50 rounded-full h-12 w-12 sm:h-16 sm:w-16 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="building" class="h-6 w-6 sm:h-8 sm:w-8 text-gray-400" />
            </div>
            <h3 class="text-base sm:text-lg font-medium text-gray-900">
                {{ search ? 'No se encontraron resultados' : 'Sin Tiendas Registradas' }}
            </h3>
            <p class="mt-1 text-gray-500 text-xs sm:text-sm max-w-md mx-auto">
                {{ search 
                    ? 'Intenta con otros términos de búsqueda' 
                    : 'Comienza agregando tu primera tienda a esta región' 
                }}
            </p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    v-if="search"
                    @click="clearSearch"
                    class="text-[#00408F] hover:text-[#FF5501] font-semibold text-sm"
                >
                    Limpiar búsqueda
                </button>
                <Link
                    v-else
                    :href="route('branches.create', isGlobal ? {} : { region_id: region?.id })"
                    class="inline-flex items-center px-4 py-2 bg-[#00408F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#FF5501] transition"
                >
                    <AppIcon name="plus" class="h-4 w-4 mr-2" />
                    Agregar Primera Tienda
                </Link>
            </div>
        </div>

        <!-- Tabla Desktop -->
        <div v-else class="hidden md:block bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                           <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID ECO
                            </th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tienda
                            </th>
                            <th v-if="isGlobal" class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Región
                            </th>
                            <th v-if="isGlobal" class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Compañía
                            </th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Zona
                            </th>
                            <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr 
                            v-for="branch in branches.data" 
                            :key="branch.id" 
                            class="hover:bg-blue-50/30 transition"
                        >
                            <!-- ID ECO -->
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <code class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                    {{ branch.external_id_eco || 'N/A' }}
                                </code>
                            </td>
                        
                            <!-- Tienda -->
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <AppIcon 
                                        name="building" 
                                        class="h-5 w-5 text-[#FF5501] mr-3 flex-shrink-0" 
                                    />
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ branch.name }}
                                    </span>
                                </div>
                            </td>
                        
                            <!-- Región -->
                            <td v-if="isGlobal" class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700">
                                    {{ branch.region?.name || 'N/A' }}
                                </span>
                            </td>
                        
                            <!-- Compañía -->
                            <td v-if="isGlobal" class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-[#00408F]">
                                    {{ branch.region?.team?.name || 'N/A' }}
                                </span>
                            </td>
                        
                            <!-- Zona -->
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ branch.zone_name || 'N/A' }}
                            </td>
                        
                            <!-- Acciones -->
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <Link 
                                        :href="route('branches.edit', branch.id)"
                                        class="text-[#00408F] hover:text-[#FF5501] font-semibold"
                                    >
                                        Editar
                                    </Link>
                                
                                    <span class="text-gray-300">|</span>
                                
                                    <button
                                        @click="deleteBranch(branch)"
                                        class="text-red-600 hover:text-red-800 font-semibold"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>

            <!-- Paginación -->
            <div v-if="branches.links && branches.links.length > 3" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Mostrando <span class="font-medium">{{ branches.from }}</span> a 
                        <span class="font-medium">{{ branches.to }}</span> de 
                        <span class="font-medium">{{ branches.total }}</span> resultados
                    </div>
                    <div class="flex space-x-2">
                        <Link
                            v-for="(link, index) in branches.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-md',
                                link.active 
                                    ? 'bg-[#00408F] text-white' 
                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas Mobile/Tablet -->
        <div class="md:hidden space-y-4 px-4">
            <div v-for="branch in branches?.data || []" :key="branch.id" 
                class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
            >
                <div class="h-1 bg-[#00408F]"></div>
                
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center flex-1 min-w-0">
                            <AppIcon name="building" class="h-5 w-5 text-[#FF5501] mr-2 flex-shrink-0" />
                            <h3 class="text-base font-bold text-gray-800 truncate">
                                {{ branch.name }}
                            </h3>
                        </div>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div v-if="isGlobal" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Región:</span>
                            <span class="font-medium text-gray-700">{{ branch.region?.name }}</span>
                        </div>
                        <div v-if="isGlobal" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Compañía:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-[#00408F]">
                                {{ branch.region?.team?.name || 'N/A' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Zona:</span>
                            <span class="text-gray-700">{{ branch.zone_name || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">ID ECO:</span>
                            <code class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                {{ branch.external_id_eco || 'N/A' }}
                            </code>
                        </div>
                    </div>

                    <div class="flex space-x-2 pt-3 border-t border-gray-100">
                        <Link 
                            :href="route('branches.edit', branch.id)"
                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition"
                        >
                            <AppIcon name="edit" class="h-4 w-4 mr-1" />
                            Editar
                        </Link>
                        <!-- 🔥 FIX: Usar button en lugar de Link -->
                        <button
                            @click="deleteBranch(branch)"
                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-50 border border-red-200 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-100 transition"
                        >
                            <AppIcon name="trash" class="h-4 w-4 mr-1" />
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Paginación Mobile -->
            <div v-if="branches?.links && branches.links.length > 3" class="flex items-center justify-center space-x-2 pt-4">
                <Link
                    v-for="(link, index) in branches.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="[
                        'px-3 py-2 text-sm font-medium rounded-md',
                        link.active 
                            ? 'bg-[#00408F] text-white' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                        !link.url && 'opacity-50 cursor-not-allowed'
                    ]"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>
        </div>

    </AppLayout>
</template>x