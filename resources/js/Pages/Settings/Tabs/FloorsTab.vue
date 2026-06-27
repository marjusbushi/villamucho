<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ floors: Array, toasts: Object });

const showModal = ref(false);
const editing = ref(null);

const form = useForm({ number: '', name: '' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(floor) {
    editing.value = floor;
    form.number = floor.number;
    form.name = floor.name;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('settings.floors.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; props.toasts?.success('Kati u perditesua.'); },
        });
    } else {
        form.post(route('settings.floors.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); props.toasts?.success('Kati u shtua.'); },
        });
    }
}

function deleteFloor(floor) {
    if (!confirm(`Fshi katin "${floor.name}" (nr. ${floor.number})?`)) return;
    router.delete(route('settings.floors.destroy', floor.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success('Kati u fshi.'),
        onError: () => props.toasts?.error('Nuk mund te fshihet — ka dhoma ne kete kat.'),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-h4 text-primary-900">Katet</h3>
                    <p class="text-small text-neutral-500 mt-0.5">Shto dhe emerto katet e hotelit (numri lidhet me dhomat).</p>
                </div>
                <Button size="sm" variant="primary" @click="openCreate">+ Shto kat</Button>
            </div>
        </template>

        <div class="divide-y divide-neutral-100">
            <div v-for="floor in floors" :key="floor.id" class="py-3.5 flex items-center gap-4">
                <Badge variant="neutral" size="sm">Kati {{ floor.number }}</Badge>
                <p class="flex-1 min-w-0 text-body-sm text-primary-900 font-medium truncate">{{ floor.name }}</p>
                <div class="flex gap-1.5 shrink-0">
                    <Button size="sm" variant="ghost" @click="openEdit(floor)">Edito</Button>
                    <Button size="sm" variant="ghost" class="text-error-600" @click="deleteFloor(floor)">Fshi</Button>
                </div>
            </div>
        </div>

        <div v-if="!floors?.length" class="py-8 text-center text-body-sm text-neutral-500">
            Nuk ka kate akoma. Shtoni katin e pare me butonin lart.
        </div>
    </Card>

    <!-- Create/Edit Modal -->
    <Modal :show="showModal" :title="editing ? 'Edito katin' : 'Kat i ri'" @close="showModal = false">
        <form @submit.prevent="submit" class="space-y-4">
            <FormGroup label="Numri i katit" :error="form.errors.number" required>
                <TextInput type="number" v-model="form.number" min="0" max="255" placeholder="psh. 0 = Perdhese, 1, 2..." :error="form.errors.number" />
            </FormGroup>
            <FormGroup label="Emri" :error="form.errors.name" required>
                <TextInput v-model="form.name" placeholder="psh. Perdhese / Kati 1 / Tarraca" :error="form.errors.name" />
            </FormGroup>
        </form>
        <template #footer>
            <Button variant="outline" @click="showModal = false">Anulo</Button>
            <Button variant="primary" :loading="form.processing" @click="submit">{{ editing ? 'Ruaj' : 'Shto' }}</Button>
        </template>
    </Modal>
</template>
