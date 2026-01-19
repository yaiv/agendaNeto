<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

console.log("--- INSPECTOR DE VUE ---");
console.log("Soy el Usuario ID:", user.id);
console.log("Estoy en el Equipo:", user.current_team.name);
console.log("El Dueño de este Equipo es ID:", user.current_team.user_id);
console.log("RESULTADO:", user.id === user.current_team.user_id ? "SOY DUEÑO" : "SOY EMPLEADO");
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
