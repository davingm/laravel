<script setup lang="ts">
const button = document.querySelector<HTMLButtonElement>('[data-home-counter]')
const output = document.querySelector<HTMLOutputElement>('[data-home-counter-value]')

let count = 0

button?.addEventListener('click', () => {
    count += 1

    if (output) {
        output.value = String(count)
        output.textContent = String(count)
    }
})
</script>

<style scoped>
.counter-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
}

.counter-label {
    margin: 0;
    color: #525252;
    font-size: 0.875rem;
}
</style>

<pages>
    <section class="crud-shell">
        <h1 class="crud-title">Home</h1>
        <p class="crud-description">Contoh halaman satu file dengan Blade dan TypeScript.</p>
        <div class="counter-row">
            <button class="button button-primary" type="button" data-home-counter>
                Klik saya
            </button>
            <p class="counter-label" aria-live="polite">
                Jumlah klik: <output data-home-counter-value>0</output>
            </p>
        </div>
    </section>
</pages>
