import './bootstrap';

Livewire.on('cart-updated', (event) => {
    alert(event.message); // Ganti dengan notifikasi sesuai keinginanmu
});
