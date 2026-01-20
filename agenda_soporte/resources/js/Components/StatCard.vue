<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    value: [String, Number],
    color: {
        type: String,
        default: 'blue' // Opciones: blue, orange, green
    },
    href: { // 👈 Nueva propiedad opcional
        type: String,
        default: null
    }
});

// Mapas de colores para no ensuciar el template
const colors = {
    blue: { text: 'text-[#00408F]', bg: 'bg-blue-50' },
    orange: { text: 'text-[#FF5501]', bg: 'bg-orange-50' },
    green: { text: 'text-green-600', bg: 'bg-green-50' },
};
</script>

<template>
<component 
        :is="href ? Link : 'div'" 
        :href="href"
        class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 p-5 flex items-center justify-between transition-all duration-200"
        :class="href ? 'hover:shadow-md hover:scale-[1.02] cursor-pointer' : ''"
    >
        <div>
            <div class="text-sm font-medium text-gray-500 truncate mb-1">
                {{ title }}
            </div>
            <div :class="`text-3xl font-bold ${colors[color]?.text || colors.blue.text}`">
                {{ value }}
            </div>
        </div>

        <div :class="`p-3 rounded-full ${colors[color]?.bg || colors.blue.bg} ${colors[color]?.text || colors.blue.text}`">
            <slot name="icon" />
        </div>
</component>
</template>