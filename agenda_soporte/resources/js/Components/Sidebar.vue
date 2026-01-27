<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue'; // Tu componente de iconos nativo
import ApplicationMark from '@/Components/ApplicationMark.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Lógica de Menú basada en Roles
const navigation = computed(() => {
    const role = user.value.global_role;
    const isGlobalAdmin = user.value.is_global_admin; 
    const isTeamOwner = user.value.current_team?.user_id === user.value.id;

    // NIVEL 1: Admin Global / Gerente / Supervisor
    if (isGlobalAdmin || ['gerente', 'supervisor'].includes(role)) {
        return [
            { 
            name: 'Dashboard Global', 
            route: 'admin.dashboard', 
            icon: 'dashboard', 
            active: 'admin.dashboard' 
        },
        { 
            name: 'Compañías', 
            route: 'admin.companies.index', 
            icon: 'building', 
            active: 'admin.companies.*' 
        },
        
        { 
            name: 'Regiones', 
            route: 'regions.index', 
            icon: 'map', 
            active: 'regions.*' 
        },
        { 
            name: 'Tiendas', 
            route: 'branches.index', 
            icon: 'briefcase', 
            active: 'branches.*' 
        },
        { 
            name: 'Ingenieros', 
            route: 'engineers.index', 
            icon: 'users', 
            active: 'engineers.*' 
        },
        ];
    }

    // NIVEL 2: Coordinador (Dueño del equipo o rol explícito)
    if (role === 'coordinador' || isTeamOwner) {
        return [
            { name: 'Panel Operativo', route: 'dashboard', icon: 'dashboard', active: 'dashboard' },
            { name: 'Regiones', route: 'regions.index', icon: 'map', active: 'regions.*' },
            { name: 'Tiendas', route: 'branches.index', icon: 'briefcase', active: 'branches.*' },
            { name: 'Ingenieros', route: 'engineers.index', icon: 'users', active: 'engineers.*' },
        ];
    }

    // NIVEL 3: Ingeniero (Default)
    return [
        { name: 'Mis Actividades', route: 'dashboard', icon: 'clipboard', active: 'dashboard' },
        { name: 'Mi Región', route: 'regions.index', icon: 'map', active: 'regions.*' },
    ];
});
</script>

<template>
    <div class="h-full flex flex-col bg-[#00408F] text-white shadow-xl overflow-y-auto">
 <div class="h-16 bg-[#003373] border-b border-white/10 shrink-0 overflow-hidden">
     <Link :href="route('dashboard')" class="block w-full h-full">
         <img 
             src="/img/logo-neto.png" 
             alt="Logo Neto" 
             class="w-full h-full object-cover"
         />
     </Link>
 </div>

        <nav class="flex-1 px-3 py-6 space-y-1">
            <template v-for="item in navigation" :key="item.name">
                <Link 
                    :href="route(item.route)"
                    :class="[
                        route().current(item.active)
                            ? 'bg-[#FF5501] text-white shadow-md' 
                            : 'text-gray-300 hover:bg-white/10 hover:text-white',
                        'group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-all duration-200'
                    ]"
                >
                    <AppIcon 
                        :name="item.icon" 
                        class="mr-3 flex-shrink-0 h-6 w-6 transition-colors duration-200"
                        :class="route().current(item.active) ? 'text-white' : 'text-gray-400 group-hover:text-white'"
                    />
                    {{ item.name }}
                </Link>
            </template>
        </nav>

        <div class="border-t border-white/10 bg-black/20 p-4 shrink-0">
            <div class="flex items-center">
                <img class="h-9 w-9 rounded-full border-2 border-[#BBBBBB] object-cover" :src="user.profile_photo_url" :alt="user.name" />
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-400 truncate capitalize">{{ user.global_role || 'Usuario' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>