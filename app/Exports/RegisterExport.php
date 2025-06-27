<?php

namespace App\Exports;

use App\Models\RegisTraining;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RegisterExport implements FromCollection, WithHeadings
{
  protected $id;

  public function __construct($id)
  {
    $this->id = $id;
  }

  public function collection()
  {
    return RegisTraining::with('user')
      ->where('id_training', $this->id)
      ->where('approved', 'Y')
      ->get()
      ->map(function ($item, $key) {
        return [
          'No' => $key + 1,
          'Nama' => $item->user->name,
          'Email' => $item->user->email,
          'tanggal_daftar' => Carbon::parse($item->created_at)->format('d-m-Y'),
        ];
      });
  }

  public function headings(): array
  {
    return ['No', 'Nama', 'Email', 'Tanggal Daftar'];
  }
}
