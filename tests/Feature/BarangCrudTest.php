<?php

namespace Tests\Feature;

use App\Models\Barang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarangCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_barang_index_is_available(): void
    {
        Barang::factory()->create(['nama' => 'Kursi kerja']);

        $this->get(route('barang.index'))
            ->assertOk()
            ->assertSee('Kursi kerja');
    }

    public function test_barang_can_be_created(): void
    {
        $response = $this->post(route('barang.store'), [
            'nama' => 'Meja kantor',
            'sku' => 'BRG-0001',
            'harga' => 1250000,
            'stok' => 8,
            'deskripsi' => 'Meja kerja kayu.',
        ]);

        $barang = Barang::query()->first();

        $response->assertRedirect(route('barang.show', $barang));
        $this->assertDatabaseHas('barangs', ['sku' => 'BRG-0001', 'stok' => 8]);
    }

    public function test_barang_requires_valid_input(): void
    {
        $this->from(route('barang.create'))
            ->post(route('barang.store'), [])
            ->assertRedirect(route('barang.create'))
            ->assertSessionHasErrors(['nama', 'sku', 'harga', 'stok']);
    }

    public function test_barang_can_be_updated_and_deleted(): void
    {
        $barang = Barang::factory()->create();

        $this->put(route('barang.update', $barang), [
            'nama' => 'Nama baru',
            'sku' => $barang->sku,
            'harga' => 90000,
            'stok' => 4,
            'deskripsi' => null,
        ])->assertRedirect(route('barang.show', $barang));

        $this->assertDatabaseHas('barangs', ['id' => $barang->id, 'nama' => 'Nama baru']);

        $this->delete(route('barang.destroy', $barang))
            ->assertRedirect(route('barang.index'));

        $this->assertDatabaseMissing('barangs', ['id' => $barang->id]);
    }
}
