<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const showingMobileMenu = ref(false);

// --- Lógica original de Jetstream (Preservada) ---
const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-[#333333]">
        <Head :title="title" />
        <Banner />

        <aside class="hidden md:block fixed inset-y-0 left-0 z-30 w-64 bg-[#00408F]">
            <Sidebar />
        </aside>

        <div v-show="showingMobileMenu" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm md:hidden" @click="showingMobileMenu = false"></div>
        
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-[#00408F] transform transition-transform duration-300 ease-in-out md:hidden"
             :class="showingMobileMenu ? 'translate-x-0' : '-translate-x-full'">
             
            <div class="relative h-full flex flex-col">
                <button @click="showingMobileMenu = false" class="absolute top-4 right-4 text-white hover:bg-white/10 rounded-full p-1">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <Sidebar />
            </div>
        </div>

        <div class="flex-1 flex flex-col md:ml-64 min-h-screen transition-all duration-300">
            
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20 shadow-sm">
                
                <div class="flex items-center gap-4">
                    <button @click="showingMobileMenu = !showingMobileMenu" class="md:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-[#FF5501] hover:bg-gray-100">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h1 class="text-xl font-semibold text-[#00408F] tracking-tight truncate">
                        {{ title }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    
                    <div class="relative" v-if="$page.props.jetstream.hasTeamFeatures">
                        <Dropdown align="right" width="60">
                            <template #trigger>
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                    <span class="hidden sm:inline mr-1">Equipo:</span>
                                    <span class="font-bold text-[#FF5501]">{{ $page.props.auth.user.current_team.name }}</span>
                                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="w-60">
                                    <div class="block px-4 py-2 text-xs text-gray-400">Administrar Equipo</div>
                                    <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">Configuración</DropdownLink>
                                    <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">Crear Nuevo Equipo</DropdownLink>
                                    
                                    <div class="border-t border-gray-100"></div>
                                    <div class="block px-4 py-2 text-xs text-gray-400">Cambiar de Equipo</div>
                                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                        <form @submit.prevent="switchToTeam(team)">
                                            <DropdownLink as="button">
                                                <div class="flex items-center">
                                                    <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <div>{{ team.name }}</div>
                                                </div>
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="relative ml-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center text-sm border-2 border-transparent rounded-full focus:outline-none transition">
                                    <img class="h-9 w-9 rounded-full object-cover shadow-sm border border-gray-200" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                </button>
                            </template>
                            <template #content>
                                <div class="block px-4 py-2 text-xs text-gray-400">Cuenta de Usuario</div>
                                <DropdownLink :href="route('profile.show')">Mi Perfil</DropdownLink>
                                <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                <div class="border-t border-gray-100"></div>
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button"><span class="text-red-600 font-medium">Cerrar Sesión</span></DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6 overflow-x-hidden">
                <slot />
            </main>

            <footer class="h-12 flex items-center justify-between px-6 text-xs text-gray-400 bg-white border-t border-gray-200">
                <span>© 2026 Corporativo Global</span>
                <span class="hidden sm:inline">v1.0.0</span>
            </footer>
        </div>
    </div>
</template>