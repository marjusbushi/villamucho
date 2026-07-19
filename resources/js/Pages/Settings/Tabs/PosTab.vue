<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

const props = defineProps({ settings: { type: Object, default: () => ({}) }, staff: { type: Array, default: () => [] }, toasts: Object });
const form = useForm({
    service_mode: props.settings.service_mode || 'hybrid', opening_view: props.settings.opening_view || 'products',
    salesperson_enabled: props.settings.salesperson_enabled ?? true, salesperson_required: props.settings.salesperson_required ?? true,
    staff: props.staff.map((person) => ({ ...person, pin: '', clear_pin: false })),
});
const enabledCount = computed(() => form.staff.filter((person) => person.enabled).length);
function submit() { form.put(route('settings.pos'), { preserveScroll: true, onSuccess: () => props.toasts?.success('Konfigurimi POS u ruajt.') }); }
</script>

<template>
    <Card>
        <template #header><div><h3 class="text-h4 text-primary-900">POS & Shërbimi</h3><p class="mt-1 text-body-sm text-neutral-500">Mënyra e punës, ekrani fillestar dhe salesperson-at.</p></div></template>
        <form class="space-y-6" @submit.prevent="submit">
            <section><h4 class="text-label text-primary-900">Mënyra e shërbimit</h4><p class="mt-1 text-small text-neutral-500">Përcakton nëse POS punon me tavolina, shitje direkte ose të dyja.</p><div class="mt-3 grid gap-3 md:grid-cols-3"><label v-for="option in [{value:'hybrid',title:'Hibrid',text:'Tavolina + shitje direkte'},{value:'tables',title:'Vetëm tavolina',text:'Restorant me llogari tavoline'},{value:'direct',title:'Shitje direkte',text:'Banak, pa tavolina'}]" :key="option.value" class="cursor-pointer rounded-xl border p-4" :class="form.service_mode === option.value ? 'border-accent-500 bg-accent-50 ring-2 ring-accent-500/10' : 'border-neutral-200'"><input v-model="form.service_mode" type="radio" :value="option.value" class="sr-only"><strong class="text-body-sm text-primary-900">{{ option.title }}</strong><span class="mt-1 block text-tiny text-neutral-500">{{ option.text }}</span></label></div></section>
            <section class="grid gap-4 border-t border-neutral-100 pt-5 md:grid-cols-2"><label><span class="text-label text-neutral-700">Ekrani fillestar</span><select v-model="form.opening_view" class="mt-2 w-full rounded-lg border-neutral-200"><option value="tables">Tavolinat</option><option value="products">Produktet POS</option></select></label><div class="space-y-3 rounded-xl bg-neutral-50 p-4"><Checkbox v-model="form.salesperson_enabled" label="Aktivizo salesperson në POS" /><Checkbox v-model="form.salesperson_required" :disabled="!form.salesperson_enabled" label="Salesperson i detyrueshëm për çdo porosi" /></div></section>
            <section class="border-t border-neutral-100 pt-5"><h4 class="text-label text-primary-900">Salesperson-at</h4><p class="mt-1 text-small text-neutral-500">{{ enabledCount }} aktivë. PIN-i ruhet i enkriptuar dhe nuk shfaqet më.</p><div class="mt-3 divide-y divide-neutral-100 rounded-xl border border-neutral-200"><div v-for="person in form.staff" :key="person.id" class="grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_120px_110px] sm:items-center"><label class="flex items-center gap-3"><input v-model="person.enabled" type="checkbox" class="h-4 w-4 rounded border-neutral-300 text-accent-600"><span><strong class="block text-body-sm text-primary-900">{{ person.name }}</strong><span class="text-tiny text-neutral-500">{{ person.has_pin && !person.clear_pin ? 'PIN i konfiguruar' : 'Pa PIN' }}</span></span></label><input v-model="person.pin" type="password" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" class="w-full rounded-lg border-neutral-200 text-center tracking-[.3em]" placeholder="PIN i ri"><button v-if="person.has_pin && !person.clear_pin" type="button" class="text-small font-semibold text-error-600" @click="person.clear_pin = true; person.pin = ''">Hiq PIN</button><button v-else-if="person.clear_pin" type="button" class="text-small font-semibold text-neutral-500" @click="person.clear_pin = false">Anulo heqjen</button></div></div></section>
            <div v-if="form.hasErrors" class="rounded-lg bg-error-50 px-3 py-2 text-small text-error-700">Kontrollo fushat. PIN-i duhet të ketë 4 shifra.</div>
            <div class="settings-actions"><Button type="submit" variant="primary" :loading="form.processing">Ruaj konfigurimin POS</Button></div>
        </form>
    </Card>
</template>
