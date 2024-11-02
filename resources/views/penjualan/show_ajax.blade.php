@empty($penjualan)
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Kesalahan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Kesalahan!!!</h5>
                    Data yang anda cari tidak ditemukan
                </div>
                <a href="{{ url('/penjualan/') }}" class="btn btn-warning">Kembali</a>
            </div>
        </div>
    </div>
@else
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Detail Data Penjualan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info-circle"></i> Informasi !!!</h5>
                    Berikut adalah detail data Penjualan:
                </div>
                <table class="table table-sm table-bordered table-striped">
                    <tr>
                        <th class="text-right col-3">Kode Penjualan :</th>
                        <td class="col-9">{{ $penjualan->penjualan_kode }}</td>
                    </tr>
                    <tr>
                        <th class="text-right col-3">Nama Pembuat :</th>
                        <td class="col-9">{{ $penjualan->user->nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-right col-3">Nama Pembeli :</th>
                        <td class="col-9">{{ $penjualan->pembeli }}</td>
                    </tr>
                    <tr>
                        <th class="text-right col-3">Tanggal Penjualan :</th>
                        <td class="col-9">{{ \Carbon\Carbon::parse($penjualan->penjualan_tanggal)->format('d-m-Y') }}</td>
                    </tr>

                </table>
                <div class="alert" style="background-color: #9BC4E2; color: #fff; border: 1px solid #7FAFC8;">
                    <h5><i class="icon fas fa-list"></i> Detail Barang</h5>
                </div>                
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp 
                        @foreach ($penjualan->penjualan_detail as $detail)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $detail->barang->barang_nama }}</td>
                                <td>{{ $detail->harga }}</td>
                                <td>{{ $detail->jumlah }}</td>
                                <td>{{ number_format($detail->harga * $detail->jumlah) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    

                </table>
            </div>
        </div>
    </div>
@endempty
