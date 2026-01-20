<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Importamos los componentes hijos
import CoordinatorView from './Coordinator.vue';
import EngineerView from './Engineer.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Lógica Robusta: Usamos el rol global o la propiedad de equipo
const isCoordinator = computed(() => {
    // Es coordinador si tiene el rol explícito O si es el dueño del equipo actual
    return user.value.global_role === 'coordinador' || 
           user.value.id === user.value.current_team?.user_id;
});
</script>

<template>
    <AppLayout :title="isCoordinator ? 'Centro de Mando' : 'Zona Operativa'">
        
        <CoordinatorView v-if="isCoordinator" :user="user" />
        <EngineerView v-else :user="user" />

    </AppLayout>
</template>    