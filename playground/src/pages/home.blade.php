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

<pages>
    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6">
        <h1 class="m-0 text-3xl font-semibold tracking-tight text-neutral-950">Home</h1>
        <p class="mt-2 text-sm text-neutral-600">Contoh halaman satu file dengan Blade dan TypeScript.</p>
        <div class="mt-6 flex items-center gap-4">
            <button class="inline-flex min-h-10 items-center justify-center gap-2 rounded border border-neutral-950 px-4 py-2 text-sm font-semibold no-underline transition hover:cursor-pointer bg-neutral-950 text-white hover:bg-neutral-700" type="button" data-home-counter>
                Klik saya
            </button>
            <p class="m-0 text-sm text-neutral-600" aria-live="polite">
                Jumlah klik: <output data-home-counter-value>0</output>
            </p>
        </div>
    </section>
</pages>
