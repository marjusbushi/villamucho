<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

const props = defineProps({
    orders: Object,
    menu: Array,
    activeReservations: Array,
    filters: Object,
    stats: Object,
});

const toasts = ref(null);
const showNewOrder = ref(false);
const showPayModal = ref(false);
const selectedOrder = ref(null);

// Cart for new order
const cart = ref([]);
const tableNumber = ref('');
const selectedReservation = ref('');

const reservationOptions = props.activeReservations.map((r) => ({
    value: r.id,
    label: `Dhoma ${r.room?.room_number} — ${r.guest?.first_name} ${r.guest?.last_name}`,
}));

const paymentOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Karte' },
    { value: 'room_charge', label: 'Room Charge' },
];
const paymentMethod = ref('');

const cartTotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.qty, 0)
);

function addToCart(menuItem) {
    const existing = cart.value.find((c) => c.id === menuItem.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({ id: menuItem.id, name: menuItem.name, price: parseFloat(menuItem.price), qty: 1 });
    }
}

function removeFromCart(index) {
    cart.value.splice(index, 1);
}

function updateQty(index, delta) {
    cart.value[index].qty += delta;
    if (cart.value[index].qty <= 0) cart.value.splice(index, 1);
}

function submitOrder() {
    const form = useForm({
        table_number: tableNumber.value || null,
        reservation_id: selectedReservation.value || null,
        items: cart.value.map((c) => ({ menu_item_id: c.id, quantity: c.qty })),
    });

    form.post(route('pos.store'), {
        onSuccess: () => {
            showNewOrder.value = false;
            cart.value = [];
            tableNumber.value = '';
            selectedReservation.value = '';
            toasts.value?.success('Porosia u krijua.');
        },
    });
}

function openPay(order) {
    selectedOrder.value = order;
    paymentMethod.value = '';
    showPayModal.value = true;
}

function submitPay() {
    router.post(route('pos.complete', selectedOrder.value.id), { payment_method: paymentMethod.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
            toasts.value?.success('Pagesa u regjistrua.');
        },
    });
}

function cancelOrder(order) {
    if (!confirm('Anulo porosine?')) return;
    router.post(route('pos.cancel', order.id), {}, {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success('Porosia u anulua.'),
    });
}

const statusBadge = {
    open: { variant: 'warning', label: 'E hapur' },
    completed: { variant: 'success', label: 'Paguar' },
    cancelled: { variant: 'error', label: 'Anulluar' },
};

const payLabel = { cash: 'Cash', card: 'Karte', room_charge: 'Room Charge' };

function formatTime(d) {
    return new Date(d).toLocaleTimeString('sq-AL', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            title="POS Bar/Restaurant"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'POS' }]"
        >
            <template #actions>
                <Button variant="primary" @click="showNewOrder = true">+ Porosi e re</Button>
            </template>
        </PageHeader>

        <!-- Stats -->
        <div class="mt-6 grid grid-cols-3 gap-3">
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-warning-600">{{ stats.open }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Te hapura</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-success-600">{{ stats.today_completed }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Sot perfunduar</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-accent-600">€{{ Number(stats.today_revenue).toFixed(2) }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Te ardhura sot</p>
                </div>
            </Card>
        </div>

        <!-- Orders list -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">#</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Ora</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Tavolina/Dhoma</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Artikuj</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Statusi</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Total</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Veprime</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-neutral-50">
                                <td class="px-5 py-3 text-body-sm text-neutral-500">#{{ order.id }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ formatTime(order.created_at) }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">
                                    {{ order.table_number ? `T${order.table_number}` : '' }}
                                    {{ order.reservation_id ? `Dhoma` : '' }}
                                    <span v-if="!order.table_number && !order.reservation_id" class="text-neutral-400">—</span>
                                </td>
                                <td class="px-5 py-3 text-body-sm text-neutral-600">
                                    {{ order.items?.map(i => i.menu_item?.name).join(', ') || '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <Badge :variant="statusBadge[order.status]?.variant" dot>
                                        {{ statusBadge[order.status]?.label }}
                                    </Badge>
                                    <Badge v-if="order.payment_method" variant="neutral" size="sm" class="ml-1">
                                        {{ payLabel[order.payment_method] }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">€{{ order.total_amount }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div v-if="order.status === 'open'" class="flex justify-end gap-1.5">
                                        <Button size="sm" variant="primary" @click="openPay(order)">Paguaj</Button>
                                        <Button size="sm" variant="ghost" class="text-error-600" @click="cancelOrder(order)">Anulo</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!orders.data?.length" class="px-6 py-12 text-center">
                    <p class="text-body-sm text-neutral-500">Nuk ka porosi.</p>
                </div>
            </Card>
        </div>

        <!-- New Order Modal -->
        <Modal :show="showNewOrder" title="Porosi e re" max-width="2xl" @close="showNewOrder = false">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Menu -->
                <div>
                    <div v-for="category in menu" :key="category.id" class="mb-4">
                        <h4 class="text-label text-neutral-500 uppercase tracking-wider mb-2">{{ category.name }}</h4>
                        <div class="space-y-1">
                            <button
                                v-for="item in category.items"
                                :key="item.id"
                                class="w-full flex items-center justify-between rounded-md px-3 py-2 text-left hover:bg-accent-50 transition-colors duration-100"
                                @click="addToCart(item)"
                            >
                                <span class="text-body-sm text-primary-900">{{ item.name }}</span>
                                <span class="text-body-sm text-accent-600 font-medium">€{{ item.price }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart -->
                <div>
                    <div class="flex gap-3 mb-4">
                        <TextInput v-model="tableNumber" placeholder="Tavolina..." class="w-24" />
                        <Select v-model="selectedReservation" :options="reservationOptions" placeholder="Room charge..." class="flex-1" />
                    </div>

                    <div v-if="cart.length" class="border border-neutral-200 rounded-lg divide-y divide-neutral-100">
                        <div v-for="(item, i) in cart" :key="i" class="flex items-center justify-between px-3 py-2">
                            <div>
                                <p class="text-body-sm text-primary-900">{{ item.name }}</p>
                                <p class="text-small text-neutral-500">€{{ item.price.toFixed(2) }} x {{ item.qty }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-body-sm font-medium text-primary-900">€{{ (item.price * item.qty).toFixed(2) }}</span>
                                <div class="flex items-center gap-1">
                                    <button class="h-6 w-6 rounded bg-neutral-100 text-neutral-600 hover:bg-neutral-200 text-small" @click="updateQty(i, -1)">−</button>
                                    <button class="h-6 w-6 rounded bg-neutral-100 text-neutral-600 hover:bg-neutral-200 text-small" @click="updateQty(i, 1)">+</button>
                                    <button class="h-6 w-6 rounded bg-error-50 text-error-600 hover:bg-error-100 text-small" @click="removeFromCart(i)">×</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-body-sm text-neutral-400">
                        Kliko artikujt per ti shtuar ne porosi
                    </div>

                    <div v-if="cart.length" class="mt-4 flex items-center justify-between">
                        <p class="text-h4 text-primary-900">Total: €{{ cartTotal.toFixed(2) }}</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="showNewOrder = false">Anulo</Button>
                <Button variant="primary" :disabled="!cart.length" @click="submitOrder">Krijo porosine</Button>
            </template>
        </Modal>

        <!-- Payment Modal -->
        <Modal :show="showPayModal" title="Perfundo pagesen" max-width="sm" @close="showPayModal = false">
            <div class="space-y-4">
                <p class="text-body-sm text-neutral-600">Porosia #{{ selectedOrder?.id }} — <strong>€{{ selectedOrder?.total_amount }}</strong></p>
                <FormGroup label="Metoda e pageses" required>
                    <Select v-model="paymentMethod" :options="paymentOptions" placeholder="Zgjidh..." />
                </FormGroup>
                <p v-if="paymentMethod === 'room_charge' && !selectedOrder?.reservation_id" class="text-small text-error-600">
                    Kjo porosi nuk ka rezervim te lidhur — room charge nuk eshte i mundur.
                </p>
            </div>
            <template #footer>
                <Button variant="outline" @click="showPayModal = false">Anulo</Button>
                <Button
                    variant="primary"
                    :disabled="!paymentMethod || (paymentMethod === 'room_charge' && !selectedOrder?.reservation_id)"
                    @click="submitPay"
                >
                    Paguaj
                </Button>
            </template>
        </Modal>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>

<script>
import FormGroup from '@/Components/UI/FormGroup.vue';
export default { components: { FormGroup } };
</script>
