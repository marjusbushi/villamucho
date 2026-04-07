<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ roomTypes: Array, toasts: Object });

const showModal = ref(false);
const editingType = ref(null);

const form = useForm({
    name: '', description: '', base_price: '', max_occupancy: 2, amenities: [],
});

const amenityInput = ref('');

function openCreate() {
    editingType.value = null;
    form.reset();
    showModal.value = true;
}

function openEdit(type) {
    editingType.value = type;
    form.name = type.name;
    form.description = type.description || '';
    form.base_price = type.base_price;
    form.max_occupancy = type.max_occupancy;
    form.amenities = type.amenities || [];
    showModal.value = true;
}

function addAmenity() {
    if (amenityInput.value.trim()) {
        form.amenities.push(amenityInput.value.trim());
        amenityInput.value = '';
    }
}

function removeAmenity(i) {
    form.amenities.splice(i, 1);
}

function submit() {
    if (editingType.value) {
        form.put(route('settings.room-types.update', editingType.value.id), {
            onSuccess: () => { showModal.value = false; props.toasts?.success('Tipi u perditesua.'); },
        });
    } else {
        form.post(route('settings.room-types.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); props.toasts?.success('Tipi u shtua.'); },
        });
    }
}

function deleteType(type) {
    if (!confirm(`Fshi "${type.name}"? Kjo nuk mund te kthehet.`)) return;
    router.delete(route('settings.room-types.destroy', type.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success('Tipi u fshi.'),
        onError: () => props.toasts?.error(`Nuk mund te fshihet — ka ${type.rooms_count} dhoma.`),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <div class="flex items-center justify-between">
                <h3 class="text-h4 text-primary-900">Tipet e Dhomave</h3>
                <Button size="sm" variant="primary" @click="openCreate">+ Shto tip</Button>
            </div>
        </template>

        <div class="divide-y divide-neutral-100">
            <div v-for="type in roomTypes" :key="type.id" class="flex items-center justify-between py-3">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-body-sm text-primary-900 font-medium">{{ type.name }}</p>
                        <Badge variant="neutral" size="sm">{{ type.rooms_count }} dhoma</Badge>
                    </div>
                    <p class="text-small text-neutral-500 mt-0.5">
                        €{{ type.base_price }}/nate · Max {{ type.max_occupancy }} persona
                    </p>
                </div>
                <div class="flex gap-1.5">
                    <Button size="sm" variant="ghost" @click="openEdit(type)">Edito</Button>
                    <Button size="sm" variant="ghost" class="text-error-600" @click="deleteType(type)">Fshi</Button>
                </div>
            </div>
        </div>

        <div v-if="!roomTypes?.length" class="py-8 text-center text-body-sm text-neutral-500">Nuk ka tipe dhomash.</div>
    </Card>

    <Modal :show="showModal" :title="editingType ? 'Edito tipin' : 'Tip i ri dhome'" @close="showModal = false">
        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <FormGroup label="Emri" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="psh. Deluxe" :error="form.errors.name" />
                </FormGroup>
                <FormGroup label="Cmimi baze (€/nate)" :error="form.errors.base_price" required>
                    <TextInput type="number" v-model="form.base_price" min="0" step="0.01" :error="form.errors.base_price" />
                </FormGroup>
            </div>
            <FormGroup label="Kapaciteti max" :error="form.errors.max_occupancy" required>
                <TextInput type="number" v-model="form.max_occupancy" min="1" max="20" :error="form.errors.max_occupancy" />
            </FormGroup>
            <FormGroup label="Pershkrim" :error="form.errors.description">
                <Textarea v-model="form.description" :rows="2" placeholder="Pershkrim i shkurter..." />
            </FormGroup>
            <FormGroup label="Amenities">
                <div class="flex gap-2 mb-2">
                    <TextInput v-model="amenityInput" placeholder="psh. WiFi" @keyup.enter.prevent="addAmenity" class="flex-1" />
                    <Button type="button" size="sm" variant="outline" @click="addAmenity">+</Button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <Badge v-for="(a, i) in form.amenities" :key="i" variant="neutral">
                        {{ a }}
                        <button type="button" class="ml-1 text-neutral-400 hover:text-error-500" @click="removeAmenity(i)">×</button>
                    </Badge>
                </div>
            </FormGroup>
        </form>
        <template #footer>
            <Button variant="outline" @click="showModal = false">Anulo</Button>
            <Button variant="primary" :loading="form.processing" @click="submit">{{ editingType ? 'Ruaj' : 'Shto' }}</Button>
        </template>
    </Modal>
</template>
