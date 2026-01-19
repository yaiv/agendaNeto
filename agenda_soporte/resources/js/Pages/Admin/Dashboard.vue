<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
</script>

<template>
    <AdminLayout title="Panel de Control">
        <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-700 p-6">
            <h1 class="text-2xl font-bold text-white mb-4">
                Bienvenido al Centro de Comando Global 🚀
            </h1>
            <p class="text-gray-400">
                Desde aquí tendrás control total sobre todas las compañías, regiones e ingenieros.
                Este panel es invisible para los usuarios normales.
            </p>
            
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-700 p-4 rounded-lg">
                    <span class="block text-3xl font-bold text-yellow-400">5</span>
                    <span class="text-sm text-gray-300">Compañías Activas</span>
                </div>
                </div>
        </div>
    </AdminLayout>
</template>