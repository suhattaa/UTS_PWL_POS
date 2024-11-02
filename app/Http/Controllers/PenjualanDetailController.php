<?php

namespace App\Http\Controllers;

use App\Models\PenjualanDetailModel;
use App\Models\PenjualanModel;
use App\Models\BarangModel;
use App\Models\StokModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yajra\DataTables\DataTables;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $breadcrumb = (object)[
            'title' => 'Daftar Penjualan',
            'list' => ['Home', 'Penjualan']
        ];
        $page = (object)[
            'title' => 'Daftar penjualan yang terdaftar dalam sistem'
        ];
        $activeMenu = 'penjualan';
        $users = UserModel::all(); // Mengambil semua pengguna

        // Mengembalikan tampilan dengan data yang diperlukan
        return view('penjualan.index', compact('breadcrumb', 'page', 'activeMenu', 'users'));
    }

    public function list(Request $request)
    {
        // Prepare the query for sales data
        $penjualan = PenjualanModel::with(['user'])
            ->select('penjualan_id', 'user_id', 'pembeli', 'penjualan_kode', 'penjualan_tanggal');

        // Filter based on User ID
        if ($request->user_id) {
            $penjualan->where('user_id', $request->user_id);
        }

        return DataTables::of($penjualan)
            ->addIndexColumn()
            ->addColumn('aksi', function ($penjualan) {
                $btn = '<button onclick="modalAction(\'' . url('/penjualan/' . $penjualan->penjualan_id . '/show_ajax') . '\')" class="btn btn-info btn-sm">Detail</button> ';
                $btn .= '<button onclick="modalAction(\'' . url('/penjualan/' . $penjualan->penjualan_id . '/delete_ajax') . '\')" class="btn btn-danger btn-sm">Hapus</button> ';
                return $btn;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create_ajax()
    {
        // Fetch data for barang and user dropdowns
        $barang = BarangModel::select('barang_id', 'barang_nama', 'harga_jual')->get();
        $user = UserModel::select('user_id', 'nama')->get();

        // Send data to the view for penjualan creation form
        return view('penjualan.create_ajax')
            ->with('barang', $barang)
            ->with('user', $user);
    }

    // Function to handle the AJAX-based storing of penjualan data
    public function store_ajax(Request $request)
    {
        // Check if the request is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            // Validation rules for the penjualan and details
            $rules = [
                'penjualan_kode' => 'required|string|max:255|unique:t_penjualan,penjualan_kode',
                'penjualan_tanggal' => 'required|date',
                'user_id' => 'required|integer|exists:m_user,user_id',
                'pembeli' => 'required|string|max:255',
                'barang_id.*' => 'required|integer|exists:m_barang,barang_id',
                'jumlah.*' => 'required|integer|min:1'
            ];

            // Validate the input data
            $validator = Validator::make($request->all(), $rules);

            // If validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi Gagal',
                    'msgField' => $validator->errors()
                ]);
            }

            // Create the penjualan record
            $penjualan = PenjualanModel::create([
                'penjualan_kode' => $request->penjualan_kode,
                'penjualan_tanggal' => $request->penjualan_tanggal,
                'user_id' => $request->user_id,
                'pembeli' => $request->pembeli,
            ]);

            // Store each penjualan detail (for each item sold)
            foreach ($request->barang_id as $index => $barangId) {
                PenjualanDetailModel::create([
                    'penjualan_id' => $penjualan->penjualan_id,
                    'barang_id' => $barangId,
                    'jumlah' => $request->jumlah[$index],
                    'harga' => BarangModel::find($barangId)->harga_jual * $request->jumlah[$index],
                ]);
            }

            // Return JSON response if successful
            return response()->json([
                'status' => true,
                'message' => 'Data penjualan berhasil disimpan'
            ]);
        }

        // If it's not an AJAX request, redirect to another page
        return redirect('/');
    }
    public function show_ajax(string $id)
    {

        $penjualan = PenjualanModel::with(['user', 'penjualan_detail.barang'])->find($id);


        if ($penjualan) {

            return view('penjualan.show_ajax', [
                'penjualan' => $penjualan
            ]);
        } else {

            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ]);
        }
    }


    public function confirm_ajax(string $penjualan_id)
    {
        // Ambil data penjualan berdasarkan ID
        $penjualan = PenjualanModel::find($penjualan_id);

        // Cek apakah data penjualan ditemukan
        if (!$penjualan) {
            return response()->json([
                'status' => false,
                'message' => 'Data penjualan tidak ditemukan.'
            ], 404);
        }

        // Ambil detail penjualan terkait
        $penjualanDetail = PenjualanDetailModel::where('penjualan_id', $penjualan_id)->get();

        return view('penjualan.confirm_ajax', [
            'penjualan' => $penjualan,
            'penjualanDetail' => $penjualanDetail
        ]);
    }

    public function delete_ajax(Request $request, string $penjualan_id)
    {
        if ($request->ajax() || $request->wantsJson()) {
            // Cari data penjualan
            $penjualan = PenjualanModel::findOrFail($penjualan_id);

            if ($penjualan) {
                try {
                    // Hapus semua detail penjualan yang terkait
                    PenjualanDetailModel::where('penjualan_id', $penjualan_id)->delete();

                    // Hapus data penjualan
                    $penjualan->delete();

                    return response()->json([
                        'status' => true,
                        'message' => 'Data penjualan berhasil dihapus'
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data gagal dihapus karena masih terkait dengan data lain'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data penjualan tidak ditemukan'
                ]);
            }
        }

        return redirect('/');  // Jika bukan ajax request, redirect ke halaman utama
    }
    public function import()
    {
        return view('penjualan.import');
    }

    // Function untuk proses import data melalui AJAX
    public function import_ajax(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            // Validasi file
            $rules = [
                'file_penjualan' => ['required', 'mimes:xlsx', 'max:1024']
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'msgField' => $validator->errors()
                ]);
            }

            // Mengambil file dari request
            $file = $request->file('file_penjualan');
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            // Mengubah data sheet menjadi array
            $data = $sheet->toArray(null, false, true, true);

            $insertPenjualan = [];
            $insertPenjualanDetail = [];
            $penjualanKodeMap = [];

            if (count($data) > 1) {
                foreach ($data as $baris => $value) {
                    if ($baris > 1) {
                        // Cek apakah penjualan_kode sudah ada di array penjualan yang akan dimasukkan
                        if (!isset($penjualanKodeMap[$value['C']])) {
                            // Jika belum ada, tambahkan ke dalam array dan siapkan untuk insert ke t_penjualan
                            $penjualan_tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value['D'])->format('Y-m-d H:i:s');

                            // Masukkan data ke t_penjualan
                            $penjualan = PenjualanModel::create([
                                'user_id' => $value['A'],
                                'pembeli' => $value['B'],
                                'penjualan_kode' => $value['C'],
                                'penjualan_tanggal' => $penjualan_tanggal,
                            ]);

                            // Simpan penjualan_id yang di-generate oleh database
                            $penjualanKodeMap[$value['C']] = $penjualan->penjualan_id;
                        }

                        // Masukkan data ke t_penjualan_detail dengan menghubungkan penjualan_id
                        $insertPenjualanDetail[] = [
                            'penjualan_id' => $penjualanKodeMap[$value['C']],
                            'barang_id' => $value['E'],
                            'harga' => $value['G'],
                            'jumlah' => $value['F'],
                            'created_at' => now(),
                        ];
                    }
                }

                // Insert ke t_penjualan_detail secara batch
                if (count($insertPenjualanDetail) > 0) {
                    PenjualanDetailModel::insert($insertPenjualanDetail);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Data penjualan berhasil diimport'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data yang diimport'
                ]);
            }
        }
        return redirect('/');
    }
    // Function untuk export data penjualan ke Excel
    public function export_excel()
    {
        // Ambil data penjualan yang akan diexport
        $penjualan = PenjualanModel::select('penjualan_id', 'user_id', 'pembeli', 'penjualan_kode', 'penjualan_tanggal')
            ->with(['user', 'penjualan_detail.barang']) // Gunakan relasi 'penjualanDetail' sesuai model
            ->orderBy('penjualan_tanggal')
            ->get();

        // Load library excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(); // Ambil sheet yang aktif

        // Set header untuk penjualan
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Tanggal Penjualan');
        $sheet->setCellValue('C1', 'User ID');
        $sheet->setCellValue('D1', 'Nama Pembeli');
        $sheet->setCellValue('E1', 'Kode Penjualan');
        $sheet->setCellValue('F1', 'Barang ID');
        $sheet->setCellValue('G1', 'Nama Barang');
        $sheet->setCellValue('H1', 'Jumlah');
        $sheet->setCellValue('I1', 'Harga');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true); // Bold header

        $no = 1;  // Nomor data dimulai dari 1
        $baris = 2; // Baris data dimulai dari baris ke 2

        // Loop untuk setiap penjualan
        foreach ($penjualan as $penj) {
            // Loop untuk setiap detail penjualan
            foreach ($penj->penjualan_detail as $detail) {
                $sheet->setCellValue('A' . $baris, $no);
                $sheet->setCellValue('B' . $baris, $penj->penjualan_tanggal); // Tanggal penjualan
                $sheet->setCellValue('C' . $baris, $penj->user->nama); // Nama user
                $sheet->setCellValue('D' . $baris, $penj->pembeli); // Nama pembeli
                $sheet->setCellValue('E' . $baris, $penj->penjualan_kode); // Kode penjualan
                $sheet->setCellValue('F' . $baris, $detail->barang_id); // Barang ID
                $sheet->setCellValue('G' . $baris, $detail->barang->barang_nama); // Nama barang
                $sheet->setCellValue('H' . $baris, $detail->jumlah); // Jumlah barang
                $sheet->setCellValue('I' . $baris, $detail->harga); // Harga per barang

                $baris++;
                $no++;
            }
        }

        // Set auto size untuk kolom
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Set title sheet
        $sheet->setTitle('Data Penjualan');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'Data Penjualan ' . date('Y-m-d H:i:s') . '.xlsx';

        // Pengaturan header untuk download file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer->save('php://output');
        exit;
    }
    public function export_pdf()
    {
        // Ambil data penjualan yang akan diexport
        $penjualan = PenjualanModel::select('penjualan_id', 'penjualan_kode', 'penjualan_tanggal', 'user_id', 'pembeli')
            ->with(['Penjualan_detail.barang', 'user']) // Pastikan relasi sudah terdefinisi
            ->orderBy('penjualan_tanggal')
            ->get();

        // Load view untuk PDF
        $pdf = Pdf::loadView('penjualan.export_pdf', ['penjualan' => $penjualan]);

        $pdf->setPaper('a4', 'potrait'); // Set ukuran kertas dan orientasi
        $pdf->setOption("isRemoteEnabled", true); // Set true jika ada gambar dari URL
        $pdf->render();

        return $pdf->stream('Data Penjualan ' . date('Y-m-d H:i:s') . '.pdf');
    }

}
