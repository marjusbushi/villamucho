<script setup>
import { getIntlLocale, translate } from '@/i18n';
import { ref } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Select from '@/Components/UI/Select.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';
import AuditTimeline from '@/Components/AuditTimeline.vue';
import { FileText, Image as ImageIcon, Trash2, Eye, Upload } from 'lucide-vue-next';
import { countryName } from '@/countries';

const props = defineProps({
    guest: Object,
    stays: Array,
    stats: Object,
    duplicates: Array,
    documents: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
});

const toasts = ref(null);
const perms = usePage().props.auth.user?.permissions || [];
const canUpdate = perms.includes('update_guests');

const docTypeLabel = { id_card: 'Karte identiteti', passport: 'Pasaporte', drivers_license: 'Patente', visa: 'Vize', other: 'Tjeter' };
const docTypeOptions = [
    { value: 'passport', label: translate('admin.generated.k_ca63c25697e4') },
    { value: 'id_card', label: translate('admin.generated.k_d372b4380834') },
    { value: 'drivers_license', label: translate('admin.generated.k_a54d735cfede') },
    { value: 'visa', label: translate('admin.generated.k_750050ce2de0') },
    { value: 'other', label: translate('admin.generated.k_ea6341d53716') },
];

const fileInputClass = 'block w-full text-small text-neutral-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent-600 file:text-white file:font-medium file:cursor-pointer hover:file:bg-accent-700';
const docInput = ref(null);
const uploadForm = useForm({ type: 'passport', file: null });

function fileSize(b) {
    if (!b) return '';
    const kb = b / 1024;
    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`;
}
const isImage = (mime) => (mime || '').startsWith('image/');

function onFile(e) {
    uploadForm.file = e.target.files?.[0] || null;
}
function uploadDoc() {
    if (!uploadForm.file) return;
    uploadForm.post(route('guests.documents.store', props.guest.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset('file');
            if (docInput.value) docInput.value.value = '';
            toasts.value?.success(translate('admin.generated.k_c3d6f9110bca'));
        },
    });
}
function deleteDoc(doc) {
    if (!confirm(`Fshi dokumentin "${doc.original_name}"?`)) return;
    router.delete(route('guests.documents.destroy', doc.id), {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success(translate('admin.generated.k_f60e4076395c')),
    });
}

const statusBadge = {
    pending: { variant: 'warning', label: translate('admin.generated.k_b339da803704') },
    confirmed: { variant: 'info', label: translate('admin.generated.k_f4a675814c2c') },
    checked_in: { variant: 'success', label: translate('admin.generated.k_bbe3693d68cf') },
    checked_out: { variant: 'neutral', label: translate('admin.generated.k_0507d6e120ea') },
    cancelled: { variant: 'error', label: translate('admin.generated.k_ba7068a53e73') },
};

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(getIntlLocale(), { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            :title="`${guest.first_name} ${guest.last_name}`"
            :breadcrumbs="[{ label: $t('admin.generated.k_772755d900ba'), href: '/dashboard' }, { label: $t('admin.generated.k_00ea8d8f5167'), href: route('guests.index') }, { label: `${guest.first_name} ${guest.last_name}` }]"
        >
            <template #actions>
                <Link :href="route('guests.index')" class="no-underline">
                    <Button variant="outline">{{ $t('admin.generated.k_0bfcb504838f') }}</Button>
                </Link>
            </template>
        </PageHeader>

        <!-- Stats -->
        <div class="mt-6 grid grid-cols-3 gap-3">
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ stats.total_stays }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">{{ $t('admin.generated.k_8a93b2365487') }}</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ stats.total_nights }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">{{ $t('admin.generated.k_715b32b76245') }}</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-accent-600">€{{ Number(stats.lifetime_spend).toFixed(2) }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">{{ $t('admin.generated.k_b9ae61396628') }}</p>
                </div>
            </Card>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile details -->
            <Card class="lg:col-span-1">
                <h3 class="text-label text-neutral-600 uppercase tracking-wider mb-4">{{ $t('admin.generated.k_8739f3cde9e3') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_281a9dbd567e') }}</dt>
                        <dd class="text-body-sm text-neutral-700 text-right">{{ guest.email || '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_b57dff0ad3cd') }}</dt>
                        <dd class="text-body-sm text-neutral-700 text-right">{{ guest.phone || '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_a3cbc0ab52b3') }}</dt>
                        <dd class="text-body-sm text-neutral-700 text-right">{{ guest.nationality ? countryName(guest.nationality) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_8535dce6a8e0') }}</dt>
                        <dd class="text-body-sm text-neutral-700 text-right">
                            {{ guest.document_type ? docTypeLabel[guest.document_type] : '—' }}
                            <span v-if="guest.document_number" class="text-neutral-400">· {{ guest.document_number }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_11048877bf2e') }}</dt>
                        <dd class="text-body-sm text-neutral-700 text-right">{{ formatDate(guest.date_of_birth) }}</dd>
                    </div>
                    <div v-if="guest.notes" class="border-t border-neutral-100 pt-3">
                        <dt class="text-body-sm text-neutral-500 mb-1">{{ $t('admin.generated.k_d54fbab3f878') }}</dt>
                        <dd class="text-body-sm text-neutral-700">{{ guest.notes }}</dd>
                    </div>
                </dl>

                <!-- Possible duplicates -->
                <div v-if="duplicates.length" class="mt-5 rounded-lg bg-warning-50 border border-warning-200 px-4 py-3">
                    <p class="text-body-sm text-warning-800 font-medium mb-1">{{ $t('admin.generated.k_74cbcc741a6f') }}{{ duplicates.length }})</p>
                    <ul class="space-y-1">
                        <li v-for="d in duplicates" :key="d.id" class="text-small text-warning-700">
                            <Link :href="route('guests.show', d.id)" class="hover:underline">
                                {{ d.first_name }} {{ d.last_name }}<span v-if="d.email"> · {{ d.email }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </Card>

            <!-- Stay history -->
            <Card class="lg:col-span-2" :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_aef27a9337fc') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_8f563ac61d34') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_bc1257937e76') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_5c147fd37a14') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_8f391024eccc') }}</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_a8dedd05a0c4') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="s in stays" :key="s.id" class="hover:bg-neutral-50">
                                <td class="px-5 py-3 text-body-sm text-primary-900">
                                    <Link :href="route('reservations.show', s.id)" class="hover:underline font-medium">
                                        {{ s.room }} <span class="text-neutral-400">{{ s.room_type }}</span>
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ formatDate(s.check_in_date) }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ formatDate(s.check_out_date) }}</td>
                                <td class="px-5 py-3">
                                    <Badge :variant="statusBadge[s.status]?.variant" dot>{{ statusBadge[s.status]?.label }}</Badge>
                                </td>
                                <td class="px-5 py-3 text-right text-body-sm text-primary-900">€{{ Number(s.total_amount).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!stays.length" class="px-6 py-12 text-center">
                    <p class="text-body-sm text-neutral-500">{{ $t('admin.generated.k_cad2d6a4e1bf') }}</p>
                </div>
            </Card>
        </div>

        <!-- Documents (passport / ID / …) — stored privately -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_70e9497afab3') }}</h3>
                    <p class="text-tiny text-neutral-400 mt-0.5">{{ $t('admin.generated.k_9a0f78ee7061') }}</p>
                </div>

                <ul v-if="documents.length" class="divide-y divide-neutral-100">
                    <li v-for="d in documents" :key="d.id" class="px-5 py-3 flex items-center gap-3 hover:bg-neutral-50">
                        <component :is="isImage(d.mime) ? ImageIcon : FileText" class="h-5 w-5 text-neutral-400 shrink-0" :stroke-width="1.75" />
                        <div class="min-w-0 flex-1">
                            <p class="text-body-sm text-primary-900 font-medium truncate">{{ docTypeLabel[d.type] || d.type }} <span class="text-neutral-400 font-normal">· {{ d.original_name }}</span></p>
                            <p class="text-tiny text-neutral-400">{{ fileSize(d.size) }}<span v-if="d.uploaded_by"> · {{ d.uploaded_by }}</span><span v-if="d.created_at"> · {{ d.created_at }}</span></p>
                        </div>
                        <a :href="d.url" target="_blank" rel="noopener" class="no-underline shrink-0">
                            <Button size="sm" variant="ghost"><Eye class="h-4 w-4 mr-1" :stroke-width="1.75" /> {{ $t('admin.generated.k_47e293dfe768') }}</Button>
                        </a>
                        <Button v-if="canUpdate" size="sm" variant="ghost" class="text-error-600 shrink-0" @click="deleteDoc(d)"><Trash2 class="h-4 w-4" :stroke-width="1.75" /></Button>
                    </li>
                </ul>
                <div v-else class="px-6 py-8 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_6b4190a02db1') }}</div>

                <!-- Upload -->
                <div v-if="canUpdate" class="px-5 py-4 border-t border-neutral-200 bg-neutral-50/50">
                    <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr_auto] gap-3 sm:items-end">
                        <FormGroup :label="$t('admin.generated.k_4f6605101ebb')">
                            <Select v-model="uploadForm.type" :options="docTypeOptions" />
                        </FormGroup>
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">{{ $t('admin.generated.k_b9c680976460') }}</label>
                            <input ref="docInput" type="file" accept="image/*,.pdf,.doc,.docx" :class="fileInputClass" @change="onFile" />
                            <p v-if="uploadForm.errors.file" class="text-small text-error-600 mt-1">{{ uploadForm.errors.file }}</p>
                        </div>
                        <Button variant="primary" :loading="uploadForm.processing" :disabled="!uploadForm.file" @click="uploadDoc"><Upload class="h-4 w-4 mr-1.5" :stroke-width="1.75" /> {{ $t('admin.generated.k_51c1d9fd94e5') }}</Button>
                    </div>
                </div>
            </Card>
        </div>

        <Card class="mt-6" :padding="false">
            <div class="border-b border-neutral-200 px-5 py-4">
                <h3 class="text-label uppercase tracking-wider text-neutral-600">{{ $t('admin.generated.k_141b7909f6a4') }}</h3>
                <p class="mt-0.5 text-tiny text-neutral-400">{{ $t('admin.generated.k_645195410169') }}</p>
            </div>
            <AuditTimeline :entries="history" />
        </Card>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
