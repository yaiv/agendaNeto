<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Importamos las vistas específicas
import CoordinatorView from './Coordinator.vue';
import EngineerView from './Engineer.vue';

const page = usePage();
const user = page.props.auth.user;

// REGLA DE NEGOCIO:
// Si el usuario es el dueño del equipo actual -> Es Coordinador (Nivel 2)
const isCoordinator = computed(() => {
    return user.id === user.current_team.user_id;
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isCoordinator ? 'Centro de Mando' : 'Zona Operativa' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <CoordinatorView v-if="isCoordinator" :user="user" />
                    <EngineerView v-else :user="user" />
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>