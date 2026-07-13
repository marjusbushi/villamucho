<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestProfileWorkspace from '@/Components/Guests/GuestProfileWorkspace.vue';

const guest = {
    id: 11,
    first_name: 'Marjus',
    last_name: 'Bushi',
    email: 'marjusbushi@zeroabsolute.com',
    phone: '+355 69 203 0020',
    nationality: 'ALB',
    date_of_birth: null,
    document_type: null,
    document_number: null,
};

const stats = { total_stays: 5, total_nights: 21, lifetime_spend: '782.00', last_stay: '10 – 13 Jul 2026' };
const stays = [
    { id: 240, room: '205', room_type: 'Deluxe Sea View', check_in: '10 Jul 2026', check_out: '13 Jul 2026', status: 'checked_out', total: '301.00' },
    { id: 222, room: '302', room_type: 'Triple Room', check_in: '03 Jul 2026', check_out: '06 Jul 2026', status: 'checked_out', total: '281.00' },
    { id: 164, room: '203', room_type: 'Deluxe Sea View', check_in: '01 Jul 2026', check_out: '07 Jul 2026', status: 'cancelled', total: '200.00' },
];
const documents = [
    { id: 1, type: 'passport', name: 'passport_marjus_bushi.jpg', size: '1.8 MB', status: 'ready', uploaded_at: '14 Jul 2026' },
];
const activities = [
    { id: 1, title_key: 'documentUploaded', meta: '14 Jul 2026, 10:42 · Marjus Bushi', tone: 'info' },
    { id: 2, title_key: 'checkout', meta: '13 Jul 2026, 10:30 · Weda Muco · 205', tone: 'success' },
    { id: 3, title_key: 'payment', meta: '13 Jul 2026, 10:29 · Weda Muco · €301.00', tone: 'success' },
];
</script>

<template>
    <Head :title="$t('admin.guestProfileDesign.pageTitle')" />
    <AppLayout>
        <GuestProfileWorkspace demo :initial-guest="guest" :stats="stats" :stays="stays" :documents="documents" :activities="activities" />
    </AppLayout>
</template>
