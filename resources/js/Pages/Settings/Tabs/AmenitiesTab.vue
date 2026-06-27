<script setup>
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';

const props = defineProps({ amenities: Array, toasts: Object });

const form = useForm({ name: '' });

function add() {
    form.post(route('settings.amenities.store'), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); props.toasts?.success('Pajisja u shtua.'); },
    });
}

function remove(amenity) {
    if (!confirm(`Hiq "${amenity.name}" nga lista?`)) return;
    router.delete(route('settings.amenities.destroy', amenity.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success('Pajisja u hoq.'),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">Pajisjet (Amenities)</h3>
                <p class="text-small text-neutral-500 mt-0.5">Krijo listën një herë — pastaj zgjidhi me tik te çdo tip dhome.</p>
            </div>
        </template>

        <!-- Add a new amenity to the master list -->
        <form @submit.prevent="add" class="flex gap-2 mb-5">
            <TextInput v-model="form.name" placeholder="psh. WiFi, Ballkon, Minibar..." :error="form.errors.name" class="flex-1" />
            <Button variant="primary" :loading="form.processing" @click="add">+ Shto</Button>
        </form>

        <!-- Master list -->
        <div v-if="amenities?.length" class="flex flex-wrap gap-2">
            <span
                v-for="a in amenities"
                :key="a.id"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-neutral-100 text-body-sm text-neutral-700"
            >
                {{ a.name }}
                <button type="button" class="text-neutral-400 hover:text-error-500 text-base leading-none" @click="remove(a)">×</button>
            </span>
        </div>
        <div v-else class="py-6 text-center text-body-sm text-neutral-500">
            Asnjë pajisje akoma. Shto të parën lart.
        </div>
    </Card>
</template>
