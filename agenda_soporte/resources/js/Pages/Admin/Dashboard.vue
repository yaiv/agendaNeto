<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import StatCard from '@/Components/StatCard.vue';

// Recibimos las estadísticas reales desde el Controlador (routes/web.php)
defineProps({
    stats: {
        type: Object,
        // Limpiamos los defaults para que muestren 0 si falla la carga, en lugar de datos falsos.
        default: () => ({ companies: 0, regions: 0, engineers: 0 }) 
    }
});
</script>

<template>
    <AppLayout title="Dashboard Global">
        
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 border-l-4 border-[#FF5501]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        Bienvenido al Centro de Comando
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Resumen estratégico de operaciones, compañías y rendimiento global.
                    </p>
                </div>
                <button class="hidden sm:inline-flex items-center px-4 py-2 bg-[#00408F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-800 transition">
                    Generar Reporte
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <StatCard 
                title="Compañías Activas" 
                :value="stats.companies" 
                color="blue"
                :href="route('admin.companies.index')" 
            >
                <template #icon><AppIcon name="office-building" class="w-8 h-8" /></template>
            </StatCard>
        
            <StatCard 
                title="Regiones Totales" 
                :value="stats.regions" 
                color="orange"
                :href="route('regions.index')"
            >
                <template #icon><AppIcon name="map" class="w-8 h-8" /></template>
            </StatCard>
        
            <StatCard 
                title="Ingenieros en Sitio" 
                :value="stats.engineers"
                color="green"
                :href="route('engineers.index')"
            >
                <template #icon><AppIcon name="users" class="w-8 h-8" /></template>
            </StatCard>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Actividad Reciente del Sistema</h3>
            </div>
            <div class="p-6 text-gray-500 text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-300 mb-3 flex items-center justify-center">
                     <AppIcon name="clipboard-check" class="w-12 h-12" />
                </div>
                <p>No hay alertas críticas pendientes de revisión.</p>
            </div>
        </div>

    </AppLayout>
</template>