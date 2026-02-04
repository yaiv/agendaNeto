<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CoordinatorView from './Coordinator.vue';
import EngineerView from './Engineer.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Capturamos los datos que vienen del controlador
const stats = computed(() => page.props.stats);
const organizationMap = computed(() => page.props.organizationMap);

const isCoordinator = computed(() => {
    return user.value.global_role === 'coordinador' || 
           user.value.id === user.value.current_team?.user_id;
});
</script>

<template>
    <AppLayout :title="isCoordinator ? 'Centro de Mando' : 'Zona Operativa'">
        <CoordinatorView v-if="isCoordinator" :user="user" :stats="stats" />
        <EngineerView 
v-else 
    :user="user" 
    :stats="stats" 
    :organization-map="organizationMap"
    :cecos="$page.props.cecos"
        />
    </AppLayout>
</template>