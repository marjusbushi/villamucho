<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';

// Move a checked-in guest to a different room. Only the room changes — dates,
// guest, folio and total stay the same (see ReservationController::moveRoom).
const props = defineProps({
    show: { type: Boolean, default: false },
    reservation: { type: Object, default: null },
    rooms: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'moved']);

const form = useForm({ room_id: '' });

const currentRoom = computed(() => props.rooms.find((r) => r.id === props.reservation?.room_id) || null);
const roomOptions = computed(() =>
    props.rooms
        .filter((r) => r.id !== props.reservation?.room_id)
        .map((r) => ({ value: r.id, label: `${r.room_number} — ${r.room_type?.name}` }))
);
function d(v) {
    return v ? String(v).split('T')[0] : '';
}

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        form.reset();
        form.clearErrors();
    }
);

function submit() {
    if (!props.reservation) return;
    form.post(route('reservations.move-room', props.reservation.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('moved');
            emit('close');
            form.reset();
        },
    });
}
</script>

<template>
    <Modal :show="show" :title="$t('admin.generated.k_66958d4a915d')" max-width="md" @close="emit('close')">
        <div v-if="reservation" class="space-y-4">
            <div class="rounded-lg bg-neutral-50 border border-neutral-100 px-3 py-2 text-body-sm text-neutral-700">
                <b>{{ reservation.guest?.first_name }} {{ reservation.guest?.last_name }}</b>
{{ $t('admin.generated.k_05aa650e99ef') }} <b>{{ currentRoom?.room_number || reservation.room?.room_number || '—' }}</b>
                · {{ d(reservation.check_in_date) }} → {{ d(reservation.check_out_date) }}
            </div>
            <FormGroup :label="$t('admin.generated.k_7ef2d92cda66')" :error="form.errors.room_id" required>
                <Select v-model="form.room_id" :options="roomOptions" :placeholder="$t('admin.generated.k_c37e114bf99b')" :error="form.errors.room_id" />
            </FormGroup>
            <p class="text-tiny text-neutral-500">{{ $t('admin.generated.k_0aef3d636c72') }}</p>
        </div>
        <template #footer>
            <Button variant="outline" @click="emit('close')">{{ $t('admin.generated.k_44e4b0052c88') }}</Button>
            <Button variant="primary" :loading="form.processing" :disabled="!form.room_id" @click="submit">{{ $t('admin.generated.k_4f76381a6ffd') }}</Button>
        </template>
    </Modal>
</template>
